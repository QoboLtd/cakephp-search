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
    protected $searchTable;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->searchTable = TableRegistry::get('Search.SavedSearches');
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

        $where = $this->getWhereClause($data, $table, $user);
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
        $entity = $this->searchTable->get($id);
        $content = json_decode($entity->content, true);
        $entity = $this->normalize($entity, $table, $user, $content, $searchData);

        return $this->searchTable->save($entity);
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
        $entity = $this->searchTable->get($id);
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

        return $this->searchTable->save($entity);
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

        $value = Hash::get($result, 'criteria.query');

        // advanced search
        if (!$value) {
            return $result;
        }

        // basic search query, converted to search criteria
        $result['aggregator'] = 'OR';
        $result['criteria'] = $this->getBasicCriteria($value, $table, $user);

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

            $where = $this->getWhereClause($data, $targetTable, $user);
            if (!empty($where)) {
                $result[$association->getName()]['where'] = $where;
            }
        }

        return $result;
    }

    /**
     * Prepare basic search query's where statement
     *
     * @param string $value Search query value
     * @param \Cake\ORM\Table $table Table object
     * @param array $user User info
     * @return array
     */
    protected function getBasicCriteria($value, Table $table, array $user)
    {
        $searchableFields = Utility::instance()->getSearchableFields($table, $user);

        $fields = $this->getBasicFields($table, $searchableFields);
        if (empty($fields)) {
            return [];
        }

        $result = [];
        foreach ($fields as $field) {
            $val = $this->getBasicFieldValue($field, $value, $searchableFields);
            if (empty($val)) {
                continue;
            }
            $result[$field] = $val;
        }

        return $result;
    }

    /**
     * Field value getter for basic search criteria.
     *
     * @param string $field Field name
     * @param string $value Search query value
     * @param array $searchFields Searchable fields
     * @return array
     */
    protected function getBasicFieldValue($field, $value, $searchFields)
    {
        if (!array_key_exists($field, $searchFields)) {
            return [];
        }

        $type = $searchFields[$field]['type'];
        if (!in_array($type, Options::getBasicSearchFieldTypes())) {
            return [];
        }

        if ('related' === $type) {
            $sourceTable = TableRegistry::get($searchFields[$field]['source']);
            $value = $this->getRelatedModuleValues($sourceTable, $value, $user);
        }

        if (empty($value)) {
            continue;
        }

        $result = [];
        foreach ((array)$value as $val) {
            $result[] = [
                'type' => $type,
                'operator' => key($searchFields[$field]['operators']),
                'value' => $val
            ];
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
     * @param string $value Search query value
     * @param array $user User info
     * @return array
     */
    protected function getRelatedModuleValues(Table $table, string $value, array $user)
    {
        $result = [];
        if (empty($user)) {
            return $result;
        }

        $data = [
            'aggregator' => 'OR',
            'criteria' => $this->getBasicCriteria($value, $table, $user)
        ];

        $query = $this->execute($table, $user, $data);
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

        $result = (array)$event->result;

        if (empty($result)) {
            $result = (array)$table->aliasField($table->displayField());
        }

        $result = $this->filterBasicFields($table, $result);

        if (!empty($result)) {
            return $result;
        }

        $result = $this->getDefaultBasicFields($searchableFields);

        return $result;
    }

    /**
     * Filters basic search fields by removing virtual ones.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @param array $fields Basic search fields
     * @return array
     */
    protected function filterBasicFields(Table $table, array $fields)
    {
        // get table columns, aliased
        $columns = $table->schema()->columns();
        foreach ($columns as $index => $column) {
            $columns[$index] = $table->aliasField($column);
        }

        // filter out virtual fields
        foreach ($fields as $index => $field) {
            if (!in_array($field, $columns)) {
                unset($fields[$index]);
            }
        }

        return $fields;
    }

    /**
     * Default basic search fields getter.
     *
     * @param array $searchFields Searchable fields
     * @return array
     */
    protected function getDefaultBasicFields($searchFields)
    {
        $result = [];
        $types = Options::getBasicSearchFieldTypes();

        foreach ($searchFields as $field => $properties) {
            if (in_array($properties['type'], $types)) {
                $result[] = $field;
            }
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
    protected function getWhereClause(array $data, Table $table, array $user)
    {
        $result = [];

        if (empty($data['criteria'])) {
            return $result;
        }

        $searchableFields = Utility::instance()->getSearchableFields($table, $user);
        // get current module searchable fields.
        $moduleFields = $this->filterModuleFields($table, array_keys($searchableFields));

        foreach ($data['criteria'] as $fieldName => $criterias) {
            if (empty($criterias)) {
                continue;
            }

            $fieldName = $table->aliasField($fieldName);
            if (!in_array($fieldName, $moduleFields)) {
                continue;
            }

            foreach ($criterias as $criteria) {
                $condition = $this->getWhereCondition($fieldName, $criteria, $searchableFields);
                if (empty($condition)) {
                    continue;
                }
                $result[] = $condition;
            }
        }

        return $result;
    }

    /**
     * Prepare and return where statement condition.
     *
     * @param string $field Field name
     * @param array $criteria Criteria properties
     * @param array $searchFields Searchable fields
     * @return array
     */
    protected function getWhereCondition($field, array $criteria, array $searchFields)
    {
        $result = [];

        $value = trim($criteria['value']);
        if (empty($value)) {
            return $result;
        }

        if (isset($searchFields[$field]['operators'][$criteria['operator']]['pattern'])) {
            $pattern = $searchFields[$field]['operators'][$criteria['operator']]['pattern'];
            $value = str_replace('{{value}}', $value, $pattern);
        }

        $key = $field . ' ' . $searchFields[$field]['operators'][$criteria['operator']]['operator'];

        $result[$key] = $value;

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

        $entity = $this->searchTable->newEntity();

        $entity = $this->normalize($entity, $table, $user, $data, $data);
        $this->searchTable->save($entity);

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
        $this->searchTable->deleteAll([
            'modified <' => new \DateTime(static::DELETE_OLDER_THAN),
            'name IS' => null
        ]);
    }
}
