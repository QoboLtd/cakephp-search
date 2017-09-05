<?php
namespace Search\Utility;

use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Http\ServerRequest;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Search\Event\EventName;
use Search\Model\Entity\SavedSearch;
use Search\Utility;
use Search\Utility\Options;
use Search\Utility\Validator;

final class Search
{
    /**
     * Delete older than value
     */
    const DELETE_OLDER_THAN = '-3 hours';

    /**
     * Saved searches table.
     *
     * @var \Search\Model\Table\SavedSearchesTable
     */
    protected $table;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->table = TableRegistry::get('Search.SavedSearches');
    }

    /**
     * Search method.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @param array $user User info
     * @param array $data Request data
     * @return null|\Cake\ORM\Query
     */
    public function execute(Table $table, array $user, array $data)
    {
        $result = null;

        $data = Validator::validateData($table, $data, $user);

        if (empty($data['criteria'])) {
            return $result;
        }

        // initialize query
        $query = $table->find('all');

        $where = $this->prepareWhereStatement($data, $table, $user);
        $select = $this->getQueryFields($data, $table);
        $order = [$table->aliasField($data['sort_by_field']) => $data['sort_by_order']];

        $joins = $this->byAssociations($table, $data, $user);
        // set joins and append to where and select parameters
        foreach ($joins as $name => $params) {
            $query->leftJoinWith($name);

            if (!empty($params['where'])) {
                $where = array_merge($where, $params['where']);
            }

            if (!empty($params['select'])) {
                $select = array_merge($select, $params['select']);
            }
        }

        // add query clauses
        $query->select($select)->where([$data['aggregator'] => $where])->order($order);

        return $query;
    }

    /**
     * Create search.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @param array $user User info
     * @param array $searchData Request data
     * @return string
     */
    public function create(Table $table, array $user, array $searchData)
    {
        $searchData = Validator::validateData($table, $searchData, $user);

        // pre-save search
        return $this->preSave($table, $user, $searchData);
    }

    /**
     * Update search.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @param array $user User info
     * @param array $searchData Request data
     * @param string $id Existing search id
     * @return bool
     */
    public function update(Table $table, array $user, array $searchData, $id)
    {
        $entity = $this->table->get($id);
        $content = json_decode($entity->content, true);
        $entity = $this->normalize($entity, $table, $user, $content, $searchData);

        return $this->table->save($entity);
    }

    /**
     * Get search.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @param array $user User info
     * @param string $id Existing search id
     * @return \Search\Model\Entity\SavedSearch
     */
    public function get(Table $table, array $user, $id)
    {
        $id = !empty($id) ? $id : $this->create($table, $user, []);
        $entity = $this->table->get($id);
        $content = json_decode($entity->content, true);
        $entity = $this->normalize($entity, $table, $user, $content, $content);

        return $entity;
    }

    /**
     * Reset search.
     *
     * @param \Search\Model\Entity\SavedSearch $entity Search entity
     * @param \Cake\ORM\Table $table Table instance
     * @param array $user User info
     * @return bool
     */
    public function reset(SavedSearch $entity, Table $table, array $user)
    {
        $content = json_decode($entity->content, true);

        // skip reset on non-saved searches as it is unnecessary and for performance reasons.
        if (!$entity->get('name')) {
            return false;
        }

        // for backward compatibility
        $saved = isset($content['saved']) ? $content['saved'] : $content;
        $entity = $this->normalize($entity, $table, $user, $saved, $saved);

        return $this->table->save($entity);
    }

    /**
     * Prepare search data from request data.
     *
     * @param \Cake\Http\ServerRequest $request Request object
     * @param \Cake\ORM\Table $table Table instance
     * @param array $user User info
     * @return array
     */
    public function prepareData(ServerRequest $request, Table $table, array $user)
    {
        $result = $request->getData();

        // advanced search
        if (!Hash::get($result, 'criteria.query')) {
            return $result;
        }

        // basic search query, converted to search criteria
        $result['aggregator'] = 'OR';
        $result['criteria'] = $this->getBasicCriteria(Hash::get($result, 'criteria'), $table, $user);

        return $result;
    }

    /**
     * Search by current Table associations.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @param array $data Search data
     * @param array $user User info
     * @return array
     */
    protected function byAssociations(Table $table, array $data, array $user)
    {
        $result = [];
        foreach ($table->associations() as $association) {
            // skip non-supported associations
            if (!in_array($association->type(), Options::getSearchableAssociations())) {
                continue;
            }

            $targetTable = $association->getTarget();

            // skip associations with itself
            if ($targetTable->getTable() === $table->getTable()) {
                continue;
            }

            $primaryKey = $targetTable->aliasField($targetTable->getPrimaryKey());

            $select = array_diff($this->getQueryFields($data, $targetTable), [$primaryKey]);
            if (!empty($select)) {
                $result[$association->getName()]['select'] = $select;
            }

            $where = $this->prepareWhereStatement($data, $targetTable, $user);
            if (!empty($where)) {
                $result[$association->getName()]['where'] = $where;
            }
        }

        return $result;
    }

    /**
     * Prepare basic search query's where statement
     *
     * @param array $data search fields
     * @param \Cake\ORM\Table $table Table object
     * @param array $user User info
     * @return array
     */
    protected function getBasicCriteria(array $data, Table $table, array $user)
    {
        $result = [];
        if (empty($data['query'])) {
            return $result;
        }

        $searchableFields = Utility::instance()->getSearchableFields($table, $user);

        $fields = $this->getBasicFields($table, $searchableFields);
        if (empty($fields)) {
            return $result;
        }

        foreach ($fields as $field) {
            if (!array_key_exists($field, $searchableFields)) {
                continue;
            }

            if (!in_array($searchableFields[$field]['type'], Options::getBasicSearchFieldTypes())) {
                continue;
            }

            $type = $searchableFields[$field]['type'];
            $operator = key($searchableFields[$field]['operators']);
            $value = $data['query'];

            if ('related' === $type) {
                $sourceTable = TableRegistry::get($searchableFields[$field]['source']);
                $value = $this->getRelatedModuleValues($sourceTable, $data, $user);
            }

            if (!is_array($value)) {
                $value = (array)$value;
            }

            if (empty($value)) {
                continue;
            }

            foreach ($value as $val) {
                $result[$field][] = [
                    'type' => $type,
                    'operator' => $operator,
                    'value' => $val
                ];
            }
        }

        return $result;
    }

    /**
     * Gets basic search values from Related module.
     *
     * This method is useful when you do a basic search on a related field,
     * in which the values are always uuid's. What this method will do is
     * run a basic search in the related module (recursively) to fetch and
     * return the entities IDs matching the search string.
     *
     * @param \Cake\ORM\Table $table Related table instance
     * @param array $data Search string
     * @param array $user User info
     * @return array
     */
    protected function getRelatedModuleValues(Table $table, array $data, array $user)
    {
        $result = [];
        if (empty($data) || empty($user)) {
            return $result;
        }

        $data = [
            'aggregator' => 'OR',
            'criteria' => $this->getBasicCriteria($data, $table, $user)
        ];

        $query = $this->search($table, $user, $data);
        if (!$query) {
            return $result;
        }

        foreach ($query->all() as $entity) {
            $result[] = $entity->id;
        }

        return $result;
    }

    /**
     * Method that broadcasts an Event to generate the basic search fields.
     * If the Event result is empty then it falls back to using the display field.
     * If the display field is a virtual one then if falls back to searchable fields,
     * using the ones that their type matches the basicSearchFieldTypes list.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @param array $searchableFields Searchable fields
     * @return array
     */
    protected function getBasicFields(Table $table, array $searchableFields)
    {
        if (empty($searchableFields)) {
            return [];
        }

        $event = new Event((string)EventName::MODEL_SEARCH_BASIC_SEARCH_FIELDS(), $this, [
            'table' => $table
        ]);
        EventManager::instance()->dispatch($event);

        $result = $event->result;

        if (empty($result)) {
            $result = $table->aliasField($table->displayField());
        }

        if (!is_array($result)) {
            $result = (array)$result;
        }

        $columns = $table->schema()->columns();
        foreach ($columns as &$column) {
            $column = $table->aliasField($column);
        }

        // remove non-existing database fields (virtual field for example)
        foreach ($result as $key => $field) {
            if (in_array($field, $columns)) {
                continue;
            }
            unset($result[$key]);
        }

        if (!empty($result)) {
            return $result;
        }

        foreach ($searchableFields as $field => $properties) {
            if (!in_array($properties['type'], Options::getBasicSearchFieldTypes())) {
                continue;
            }

            $result[] = $field;
        }

        return $result;
    }

    /**
     * Filter only current module fields.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @param array $fields Search fields
     * @return array
     */
    protected function filterModuleFields(Table $table, array $fields)
    {
        if (empty($fields)) {
            return [];
        }

        foreach ($fields as $k => $v) {
            if (false !== strpos($v, $table->getAlias() . '.')) {
                continue;
            }

            unset($fields[$k]);
        }

        return $fields;
    }

    /**
     * Prepare search query's where statement
     *
     * @param array $data request data
     * @param \Cake\ORM\Table $table Table instance
     * @param array $user User info
     * @return array
     */
    protected function prepareWhereStatement(array $data, Table $table, array $user)
    {
        $result = [];

        if (empty($data['criteria'])) {
            return $result;
        }

        // get searchable fields and filter out the ones
        // which do not belong to the current module.
        $searchableFields = Utility::instance()->getSearchableFields($table, $user);
        $moduleFields = $this->filterModuleFields($table, array_keys($searchableFields));

        foreach (array_keys($searchableFields) as $field) {
            if (in_array($field, $moduleFields)) {
                continue;
            }
            unset($searchableFields[$field]);
        }

        foreach ($data['criteria'] as $fieldName => $criterias) {
            if (empty($criterias)) {
                continue;
            }
            $fieldName = $table->aliasField($fieldName);

            if (!isset($searchableFields[$fieldName])) {
                continue;
            }

            foreach ($criterias as $criteria) {
                $type = $criteria['type'];
                $value = $criteria['value'];
                if ('' === trim($value)) {
                    continue;
                }
                $operator = $criteria['operator'];
                if (isset($searchableFields[$fieldName]['operators'][$operator]['pattern'])) {
                    $value = str_replace(
                        '{{value}}',
                        $value,
                        $searchableFields[$fieldName]['operators'][$operator]['pattern']
                    );
                }
                $sqlOperator = $searchableFields[$fieldName]['operators'][$operator]['operator'];
                $key = $fieldName . ' ' . $sqlOperator;

                $result[] = [$key => $value];
            }
        }

        return $result;
    }

    /**
     * Get fields for Query's select statement.
     *
     * @param  array $data request data
     * @param  \Cake\ORM\Table $table Table instance
     * @return array
     */
    protected function getQueryFields(array $data, Table $table)
    {
        $result = [];
        if (empty($data['display_columns'])) {
            return $result;
        }

        $result = $data['display_columns'];

        if (!is_array($result)) {
            $result = (array)$result;
        }

        $primaryKey = $table->aliasField($table->getPrimaryKey());
        if (!in_array($primaryKey, $result)) {
            array_unshift($result, $primaryKey);
        }

        $result = $this->filterModuleFields($table, $result);

        return $result;
    }

    /**
     * Method that pre-saves search and returns saved record id.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @param array $user User info
     * @param array $data Search data
     * @return string
     */
    protected function preSave(Table $table, array $user, array $data)
    {
        // delete old pre-saved searches
        $this->deletePreSaved();

        $entity = $this->table->newEntity();

        $entity = $this->normalize($entity, $table, $user, $data, $data);
        $this->table->save($entity);

        return $entity->id;
    }

    /**
     * Normalize search.
     *
     * @param \Search\Model\Entity\SavedSearch $entity Search entity
     * @param \Cake\ORM\Table $table Table instance
     * @param array $user User info
     * @param array $saved Saved search data
     * @param array $latest Latest search data
     * @return \Search\Model\Entity\SavedSearch
     */
    protected function normalize(SavedSearch $entity, Table $table, array $user, array $saved, array $latest)
    {
        // Backward compatibility: search content must always contain 'saved' and 'latest' keys.
        $saved = isset($saved['saved']) ? $saved['saved'] : $saved;
        $latest = isset($latest['latest']) ?
            $latest['latest'] :
            (isset($latest['saved']) ? $latest['saved'] : $latest);

        // Backward compatibility: always prefix search criteria, display columns and sort by fields with table name.
        $filterFunc = function ($data) use ($table) {
            if (array_key_exists('criteria', $data)) {
                foreach ($data['criteria'] as $field => $option) {
                    unset($data['criteria'][$field]);
                    $data['criteria'][$table->aliasField($field)] = $option;
                }
            }

            if (array_key_exists('display_columns', $data)) {
                $data['display_columns'] = array_values($data['display_columns']);
                foreach ($data['display_columns'] as &$field) {
                    $field = $table->aliasField($field);
                }
            }

            if (array_key_exists('sort_by_field', $data)) {
                $data['sort_by_field'] = $table->aliasField($data['sort_by_field']);
            }

            return $data;
        };

        $saved = $filterFunc($saved);
        $latest = $filterFunc($latest);

        $entity->user_id = $user['id'];
        $entity->model = $table->getRegistryAlias();
        $entity->shared = Options::getPrivateSharedStatus();
        $entity->content = json_encode(['saved' => $saved, 'latest' => $latest]);

        return $entity;
    }

    /**
     * Method that deletes old pre-save search records.
     *
     * @return void
     */
    protected function deletePreSaved()
    {
        $this->table->deleteAll([
            'modified <' => new \DateTime(static::DELETE_OLDER_THAN),
            'name IS' => null
        ]);
    }
}
