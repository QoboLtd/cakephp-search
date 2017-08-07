<?php
namespace Search\Model\Table;

use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use InvalidArgumentException;
use Qobo\Utils\ModuleConfig\ModuleConfig;
use Search\Model\Entity\SavedSearch;
use Search\Utility;

/**
 * SavedSearches Model
 *
 * @property \Cake\ORM\Association\BelongsTo $Users
 */
class SavedSearchesTable extends Table
{
    /**
     * Private shared status value
     */
    const SHARED_STATUS_PRIVATE = 'private';

    /**
     * Delete older than value
     */
    const DELETE_OLDER_THAN = '-3 hours';

    /**
     * Default sql order by direction
     */
    const DEFAULT_SORT_BY_ORDER = 'desc';

    /**
     * Default sql aggregator
     */
    const DEFAULT_AGGREGATOR = 'AND';

    /**
     * Search sort by order options.
     *
     * @var array
     */
    protected $sortByOrderOptions = [
        'asc' => 'Ascending',
        'desc' => 'Descending'
    ];

    /**
     * Search aggregator options.
     *
     * @var array
     */
    protected $aggregatorOptions = [
        'AND' => 'Match all filters',
        'OR' => 'Match any filter'
    ];

    /**
     * List of display fields to be skipped.
     *
     * @var array
     */
    protected $_skipDisplayFields = ['id'];

    /**
     * Filter basic search allowed field types
     *
     * @var array
     */
    protected $basicSearchFieldTypes = ['string', 'text', 'textarea', 'related', 'email', 'url', 'phone'];

    /**
     * Basic search default fields
     *
     * @var array
     */
    protected $defaultDisplayFields = ['modified', 'created'];

    /**
     * Searchable associations list
     *
     * @var array
     */
    protected $searchableAssociations = ['manyToOne'];

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('saved_searches');
        $this->displayField('name');
        $this->primaryKey('id');
        $this->addBehavior('Timestamp');
        $this->addBehavior('Muffin/Trash.Trash');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'className' => 'Search.Users'
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->uuid('id')
            ->allowEmpty('id', 'create');

        $validator
            ->requirePresence('name', 'create')
            ->notEmpty('name', 'update')
            ->allowEmpty('name', 'create');

        $validator
            ->requirePresence('type', 'create')
            ->notEmpty('type', 'update')
            ->allowEmpty('name', 'create');

        $validator
            ->requirePresence('model', 'create')
            ->notEmpty('model');

        $validator
            ->requirePresence('shared', 'create')
            ->notEmpty('shared');

        $validator
            ->requirePresence('content', 'create')
            ->notEmpty('content');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        # TODO : Temporary disabled
        #$rules->add($rules->existsIn(['user_id'], 'Users'));

        return $rules;
    }

    /**
     * Returns private shared status.
     *
     * @return string
     */
    public function getPrivateSharedStatus()
    {
        return static::SHARED_STATUS_PRIVATE;
    }

    /**
     * Returns a list of display fields to be skipped.
     *
     * @return array
     */
    public function getSkippedDisplayFields()
    {
        return $this->_skipDisplayFields;
    }

    /**
     * Returns a list of default display fields.
     *
     * @return array
     */
    public function getDefaultDisplayFields()
    {
        return $this->defaultDisplayFields;
    }

    /**
     * Getter method for default sql sort by order.
     *
     * @return string
     */
    public function getDefaultSortByOrder()
    {
        return static::DEFAULT_SORT_BY_ORDER;
    }

    /**
     * Getter method for sql sort by order options.
     *
     * @return string
     */
    public function getSortByOrderOptions()
    {
        return $this->sortByOrderOptions;
    }

    /**
     * Getter method for default sql aggragator.
     *
     * @return string
     */
    public function getDefaultAggregator()
    {
        return static::DEFAULT_AGGREGATOR;
    }

    /**
     * Getter method for sql aggregator options.
     *
     * @return string
     */
    public function getAggregatorOptions()
    {
        return $this->aggregatorOptions;
    }

    /**
     * Basic search allowed field types getter.
     *
     * @return array
     */
    public function getBasicSearchFieldTypes()
    {
        return $this->basicSearchFieldTypes;
    }

    /**
     * Searchable associations getter.
     *
     * @return array
     */
    public function getSearchableAssociations()
    {
        return $this->searchableAssociations;
    }

    /**
     * Search method.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @param array $user User info
     * @param array $data Request data
     * @return array
     */
    public function search(Table $table, array $user, array $data)
    {
        $data = $this->validateData($table, $data, $user);

        // initialize query
        $query = $table->find('all');

        $where = $this->_prepareWhereStatement($data, $table, $user);
        $select = $this->_getQueryFields($data, $table);
        $order = [$table->aliasField($data['sort_by_field']) => $data['sort_by_order']];

        $joins = $this->_searchByAssociations($table, $data, $user);
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
     * Search by current Table associations.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @param array $data Search data
     * @param array $user User info
     * @return array
     */
    protected function _searchByAssociations(Table $table, array $data, array $user)
    {
        $result = [];
        foreach ($table->associations() as $association) {
            // skip non-supported associations
            if (!in_array($association->type(), $this->getSearchableAssociations())) {
                continue;
            }

            $targetTable = $association->getTarget();

            // skip associations with itself
            if ($targetTable->getTable() === $table->getTable()) {
                continue;
            }

            $primaryKey = $targetTable->aliasField($targetTable->getPrimaryKey());

            $select = array_diff($this->_getQueryFields($data, $targetTable), [$primaryKey]);
            if (!empty($select)) {
                $result[$association->getName()]['select'] = $select;
            }

            $where = $this->_prepareWhereStatement($data, $targetTable, $user);
            if (!empty($where)) {
                $result[$association->getName()]['where'] = $where;
            }
        }

        return $result;
    }

    /**
     * Create search.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @param array $user User info
     * @param array $searchData Request data
     * @return string
     */
    public function createSearch(Table $table, array $user, array $searchData)
    {
        $searchData = $this->validateData($table, $searchData, $user);

        // pre-save search
        return $this->_preSave($table, $user, $searchData);
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
    public function updateSearch(Table $table, array $user, array $searchData, $id)
    {
        $entity = $this->get($id);
        $content = json_decode($entity->content, true);
        $entity = $this->_normalizeSearch($entity, $table, $user, $content, $searchData);

        return $this->save($entity);
    }

    /**
     * Get search.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @param array $user User info
     * @param string $id Existing search id
     * @return \Search\Model\Entity\SavedSearch
     */
    public function getSearch(Table $table, array $user, $id)
    {
        $id = !empty($id) ? $id : $this->createSearch($table, $user, []);
        $entity = $this->get($id);
        $content = json_decode($entity->content, true);
        $entity = $this->_normalizeSearch($entity, $table, $user, $content, $content);

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
    public function resetSearch(SavedSearch $entity, Table $table, array $user)
    {
        $content = json_decode($entity->content, true);

        // skip reset on non-saved searches as it is unnecessary and for performance reasons.
        if (!$entity->get('name')) {
            return false;
        }

        // for backward compatibility
        $saved = isset($content['saved']) ? $content['saved'] : $content;
        $entity = $this->_normalizeSearch($entity, $table, $user, $saved, $saved);

        return $this->save($entity);
    }

    /**
     * Default search options.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @return array
     */
    public function getDefaultOptions(Table $table)
    {
        $result['display_columns'] = $this->getListingFields($table);
        $result['sort_by_field'] = current($result['display_columns']);
        $result['sort_by_order'] = $this->getDefaultSortByOrder();
        $result['aggregator'] = $this->getDefaultAggregator();

        return $result;
    }

    /**
     * Search options getter.
     *
     * @return array
     */
    public function getSearchOptions()
    {
        $result = [
            'sortByOrder' => $this->getSortByOrderOptions(),
            'aggregators' => $this->getAggregatorOptions()
        ];

        return $result;
    }

    /**
     * Returns saved searches filtered by users and models.
     *
     * @param  array  $users  users ids
     * @param  array  $models models names
     * @return Cake\ORM\ResultSet
     */
    public function getSavedSearches(array $users = [], array $models = [])
    {
        $conditions = [
            'SavedSearches.name IS NOT' => null
        ];

        if (!empty($users)) {
            $conditions['SavedSearches.user_id IN'] = $users;
        }

        if (!empty($models)) {
            $conditions['SavedSearches.model IN'] = $models;
        }

        $query = $this->find('all', [
            'conditions' => $conditions
        ]);

        return $query->toArray();
    }

    /**
     * Return Table's listing fields.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @return array
     */
    public function getListingFields(Table $table)
    {
        $result = [];

        if (method_exists($table, 'getListingFields') && is_callable([$table, 'getListingFields'])) {
            $result = $table->getListingFields();
        }

        if (empty($result)) {
            $event = new Event('Search.Model.Search.displayFields', $this, [
                'table' => $table
            ]);
            $this->eventManager()->dispatch($event);

            $result = $event->result;
        }

        if (empty($result)) {
            $result[] = $table->getPrimaryKey();
            $result[] = $table->getDisplayField();
            foreach ($this->getDefaultDisplayFields() as $field) {
                $result[] = $field;
            }

            foreach ($result as $k => $field) {
                if ($table->hasField($field)) {
                    $result[$k] = $table->aliasField($field);
                    continue;
                }

                unset($result[$k]);
            }
        }

        if (!is_array($result)) {
            $result = (array)$result;
        }

        $skippedDisplayFields = [];
        foreach ($this->getSkippedDisplayFields() as $field) {
            $skippedDisplayFields[] = $table->aliasField($field);
        }

        // skip display fields
        $result = array_diff($result, $skippedDisplayFields);

        // reset numeric indexes
        $result = array_values($result);

        return $result;
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

        // non-basic search
        if (!Hash::get($result, 'criteria.query')) {
            return $result;
        }

        // basic search query, converted to search criteria
        $result['aggregator'] = 'OR';
        $result['criteria'] = $this->_getBasicSearchCriteria(Hash::get($result, 'criteria'), $table, $user);

        return $result;
    }

    /**
     * Validate if search is editable.
     *
     * @param \Search\Model\Entity\SavedSearch $entity Search entity
     * @return bool
     */
    public function isEditable(SavedSearch $entity)
    {
        return (bool)$entity->get('name');
    }

    /**
     * Returns true if table is searchable, false otherwise.
     *
     * @param  string $tableName Table name.
     * @return bool
     */
    public function isSearchable($tableName)
    {
        if (!is_string($tableName)) {
            throw new InvalidArgumentException('Provided variable [tableName] must be a string.');
        }

        list(, $tableName) = pluginSplit($tableName);

        $config = new ModuleConfig(ModuleConfig::CONFIG_TYPE_MODULE, $tableName);

        $result = (bool)$config->parse()->table->searchable;

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
    protected function _getBasicSearchCriteria(array $data, Table $table, array $user)
    {
        $result = [];
        if (empty($data['query'])) {
            return $result;
        }

        $searchableFields = Utility::instance()->getSearchableFields($table, $user);

        $fields = $this->_getBasicSearchFields($table, $searchableFields);
        if (empty($fields)) {
            return $result;
        }

        foreach ($fields as $field) {
            if (!array_key_exists($field, $searchableFields)) {
                continue;
            }

            if (!in_array($searchableFields[$field]['type'], $this->getBasicSearchFieldTypes())) {
                continue;
            }

            $type = $searchableFields[$field]['type'];
            $operator = key($searchableFields[$field]['operators']);
            $value = $data['query'];

            if ('related' === $type) {
                $sourceTable = TableRegistry::get($searchableFields[$field]['source']);
                $value = $this->_getRelatedModuleValues($sourceTable, $data, $user);
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
    protected function _getRelatedModuleValues(Table $table, array $data, array $user)
    {
        $result = [];
        if (empty($data) || empty($user)) {
            return $result;
        }

        $data = [
            'aggregator' => 'OR',
            'criteria' => $this->_getBasicSearchCriteria($data, $table, $user)
        ];

        $query = $this->search($table, $user, $data);

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
    protected function _getBasicSearchFields(Table $table, array $searchableFields)
    {
        if (empty($searchableFields)) {
            $msg = 'Searchable fields for table [' . $table->getAlias() . '] cannot be empty.';
            throw new InvalidArgumentException($msg);
        }

        $event = new Event('Search.Model.Search.basicSearchFields', $this, [
            'table' => $table
        ]);
        $this->eventManager()->dispatch($event);

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
            if (!in_array($properties['type'], $this->getBasicSearchFieldTypes())) {
                continue;
            }

            $result[] = $field;
        }

        return $result;
    }

    /**
     * Base search data validation method.
     *
     * Retrieves current searchable table columns, validates and filters criteria, display columns
     * and sort by field against them. Then validates sort by order againt available options
     * and sets it to the default option if they fail validation.
     *
     * @param \Cake\ORM\Table $table Table instace
     * @param array $data Search data
     * @param array $user User info
     * @return array
     */
    public function validateData(Table $table, array $data, array $user)
    {
        $fields = Utility::instance()->getSearchableFields($table, $user);
        $fields = array_keys($fields);

        // merge default options
        $data += $this->getDefaultOptions($table);

        if (!empty($data['criteria'])) {
            $data['criteria'] = $this->_validateCriteria($data['criteria'], $fields);
        }

        $data['display_columns'] = $this->_validateDisplayColumns($data['display_columns'], $fields);
        $data['sort_by_field'] = $this->_validateSortByField($data['sort_by_field'], $fields, $table);
        $data['sort_by_order'] = $this->_validateSortByOrder($data['sort_by_order'], $table);
        $data['aggregator'] = $this->_validateAggregator($data['aggregator']);

        return $data;
    }

    /**
     * Validate search criteria.
     *
     * @param array $data Criteria values
     * @param array $fields Searchable fields
     * @return array
     */
    protected function _validateCriteria(array $data, array $fields)
    {
        foreach ($data as $k => $v) {
            if (in_array($k, $fields)) {
                continue;
            }
            unset($data[$k]);
        }

        return $data;
    }

    /**
     * Validate search display field(s).
     *
     * @param array $data Display field(s) values
     * @param array $fields Searchable fields
     * @return array
     */
    protected function _validateDisplayColumns(array $data, array $fields)
    {
        foreach ($data as $k => $v) {
            if (in_array($v, $fields)) {
                continue;
            }
            unset($data[$k]);
        }

        return $data;
    }

    /**
     * Validate search sort by field.
     *
     * @param string $data Sort by field value
     * @param array $fields Searchable fields
     * @param \Cake\ORM\Table $table Table instance
     * @return string
     */
    protected function _validateSortByField($data, array $fields, Table $table)
    {
        if (!in_array($data, $fields)) {
            $data = $table->displayField();
        }

        return $data;
    }

    /**
     * Validate search sort by order.
     *
     * @param string $data Sort by order value
     * @param \Cake\ORM\Table $table Table instance
     * @return string
     */
    protected function _validateSortByOrder($data, Table $table)
    {
        $options = array_keys($this->getSortByOrderOptions());
        if (!in_array($data, $options)) {
            $data = $this->getDefaultSortByOrder();
        }

        return $data;
    }

    /**
     * Validate search aggregator.
     *
     * @param string $data Aggregator value
     * @return string
     */
    protected function _validateAggregator($data)
    {
        $options = array_keys($this->getAggregatorOptions());
        if (!in_array($data, $options)) {
            $data = $this->getDefaultAggregator();
        }

        return $data;
    }

    /**
     * Filter only current module fields.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @param array $fields Search fields
     * @return array
     */
    protected function _filterModuleFields(Table $table, array $fields)
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
    protected function _prepareWhereStatement(array $data, Table $table, array $user)
    {
        $result = [];

        if (empty($data['criteria'])) {
            return $result;
        }

        // get searchable fields and filter out the ones
        // which do not belong to the current module.
        $searchableFields = Utility::instance()->getSearchableFields($table, $user);
        $moduleFields = $this->_filterModuleFields($table, array_keys($searchableFields));

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
    protected function _getQueryFields(array $data, Table $table)
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

        $result = $this->_filterModuleFields($table, $result);

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
    protected function _preSave(Table $table, array $user, array $data)
    {
        // delete old pre-saved searches
        $this->_deletePreSaved();

        $entity = $this->newEntity();

        $entity = $this->_normalizeSearch($entity, $table, $user, $data, $data);
        $this->save($entity);

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
    protected function _normalizeSearch(SavedSearch $entity, Table $table, array $user, array $saved, array $latest)
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
        $entity->shared = $this->getPrivateSharedStatus();
        $entity->content = json_encode(['saved' => $saved, 'latest' => $latest]);

        return $entity;
    }

    /**
     * Method that deletes old pre-save search records.
     *
     * @return void
     */
    protected function _deletePreSaved()
    {
        $this->deleteAll([
            'modified <' => new \DateTime(static::DELETE_OLDER_THAN),
            'name IS' => null
        ]);
    }
}
