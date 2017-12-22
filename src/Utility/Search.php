<?php
/**
 * Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Search\Utility;

use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Http\ServerRequest;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use InvalidArgumentException;
use Search\Event\EventName;
use Search\Model\Entity\SavedSearch;
use Search\Utility;
use Search\Utility\BasicSearch;
use Search\Utility\Options;
use Search\Utility\Validator;

class Search
{
    /**
     * Delete older than value
     */
    const DELETE_OLDER_THAN = '-3 hours';

    /**
     * Group by count field
     */
    const GROUP_BY_FIELD = 'total';

    /**
     * Searchable table.
     *
     * @var \Cake\ORM\Table
     */
    protected $table;

    /**
     * Current user info.
     *
     * @var array
     */
    protected $user = [];

    /**
     * Searchable fields.
     *
     * @var array
     */
    protected $searchFields = [];

    /**
     * Saved searches table.
     *
     * @var \Search\Model\Table\SavedSearchesTable
     */
    protected $searchTable;

    /**
     * Constructor.
     *
     * @param \Cake\ORM\Table $table Searchable table
     * @param array $user User info
     * @return void
     */
    public function __construct(Table $table, array $user)
    {
        // $this->table = $table;
        if (empty($user)) {
            throw new InvalidArgumentException('Empty user info is not allowed.');
        }

        $this->user = $user;
        $this->table = $table;
        $this->searchTable = TableRegistry::get('Search.SavedSearches');

        $this->searchFields = Utility::instance()->getSearchableFields($this->table, $this->user);
    }

    /**
     * Search method.
     *
     * @param array $data Request data
     * @return null|\Cake\ORM\Query
     */
    public function execute(array $data)
    {
        $data = Validator::validateData($this->table, $data, $this->user);

        // initialize query
        $query = $this->table->find('all');

        $where = $this->getWhereClause($data);
        $group = $this->getGroupByClause($data);
        $select = $this->getSelectClause($data);
        $order = [$this->table->aliasField($data['sort_by_field']) => $data['sort_by_order']];

        $joins = $this->byAssociations($data);
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

        $select = !empty($group) ? $group : $select;
        if (!empty($group)) {
            $select = $group;
            $select[static::GROUP_BY_FIELD] = $query->func()->count($group[0]);
        }

        // add query clauses
        $query->select($select)->where([$data['aggregator'] => $where])->order($order)->group($group);

        return $query;
    }

    /**
     * Create search.
     *
     * @param array $searchData Request data
     * @return string
     */
    public function create(array $searchData)
    {
        $searchData = Validator::validateData($this->table, $searchData, $this->user);

        // pre-save search
        return $this->preSave($searchData);
    }

    /**
     * Update search.
     *
     * @param array $searchData Request data
     * @param string $id Existing search id
     * @return bool
     */
    public function update(array $searchData, $id)
    {
        $entity = $this->searchTable->get($id);
        $content = json_decode($entity->content, true);
        $entity = $this->normalize($entity, $content, $searchData);

        return $this->searchTable->save($entity);
    }

    /**
     * Get search.
     *
     * @param string $id Existing search id
     * @return \Search\Model\Entity\SavedSearch
     */
    public function get($id)
    {
        $id = !empty($id) ? $id : $this->create([]);
        $entity = $this->searchTable->get($id);
        $content = json_decode($entity->content, true);
        $entity = $this->normalize($entity, $content, $content);

        return $entity;
    }

    /**
     * Reset search.
     *
     * @param \Search\Model\Entity\SavedSearch $entity Search entity
     * @return bool
     */
    public function reset(SavedSearch $entity)
    {
        $content = json_decode($entity->content, true);

        // skip reset on non-saved searches as it is unnecessary and for performance reasons.
        if (!$entity->get('name')) {
            return false;
        }

        // for backward compatibility
        $saved = isset($content['saved']) ? $content['saved'] : $content;
        $entity = $this->normalize($entity, $saved, $saved);

        return $this->searchTable->save($entity);
    }

    /**
     * Prepare search data from request data.
     *
     * @param \Cake\Http\ServerRequest $request Request object
     * @return array
     */
    public function prepareData(ServerRequest $request)
    {
        $result = $request->getData();

        //$result = $this->processSearchCriteria($result);
        $event = new Event((string)EventName::MODEL_SEARCH_CHILD_ITEMS(), $this, [
            'criteria' => $result
        ]);
        EventManager::instance()->dispatch($event);

        $result = $event->result ? $event->result : $result;

        $value = Hash::get($result, 'criteria.query');

        // advanced search
        if (!$value) {
            return $result;
        }

        // basic search query, converted to search criteria
        $result['aggregator'] = 'OR';
        $basicSearch = new BasicSearch($this->table, $this->user);
        $result['criteria'] = $basicSearch->getCriteria($value);

        return $result;
    }

    /**
     * Search by current Table associations.
     *
     * @param array $data Search data
     * @return array
     */
    protected function byAssociations(array $data)
    {
        $result = [];
        foreach ($this->table->associations() as $association) {
            // skip non-supported associations
            if (!in_array($association->type(), Options::getSearchableAssociations())) {
                continue;
            }

            $targetTable = $association->getTarget();

            // skip associations with itself
            if ($targetTable->getTable() === $this->table->getTable()) {
                continue;
            }

            $primaryKey = $targetTable->aliasField($targetTable->getPrimaryKey());

            // instantiate Search on related table
            $search = new Search($targetTable, $this->user);

            $select = array_diff($search->getSelectClause($data), [$primaryKey]);
            if (!empty($select)) {
                $result[$association->getName()]['select'] = $select;
            }

            $where = $search->getWhereClause($data);
            if (!empty($where)) {
                $result[$association->getName()]['where'] = $where;
            }
        }

        return $result;
    }

    /**
     * Prepare search query's where statement
     *
     * @param array $data request data
     * @return array
     */
    protected function getWhereClause(array $data)
    {
        $result = [];

        if (empty($data['criteria'])) {
            return $result;
        }

        // get current module searchable fields.
        $moduleFields = $this->filterFields(array_keys($this->searchFields));

        foreach ($data['criteria'] as $fieldName => $criterias) {
            if (empty($criterias)) {
                continue;
            }

            $fieldName = $this->table->aliasField($fieldName);
            if (!in_array($fieldName, $moduleFields)) {
                continue;
            }

            foreach ($criterias as $criteria) {
                $condition = $this->getWhereCondition($fieldName, $criteria);

                if (empty($condition)) {
                    continue;
                }
                $result[] = $condition;
            }
        }

        return $result;
    }

    /**
     * Filter only current module fields.
     *
     * @param array $fields Search fields
     * @return array
     */
    protected function filterFields(array $fields)
    {
        if (empty($fields)) {
            return [];
        }

        foreach ($fields as $k => $v) {
            if (false !== strpos($v, $this->table->getAlias() . '.')) {
                continue;
            }

            unset($fields[$k]);
        }

        return $fields;
    }

    /**
     * Prepare and return where statement condition.
     *
     * @param string $field Field name
     * @param array $criteria Criteria properties
     * @return array
     */
    protected function getWhereCondition($field, array $criteria)
    {
        $value = is_array($criteria['value']) ? $criteria['value'] : trim($criteria['value']);

        if ('' === $value) {
            return $this->getEmptyWhereCondition($field, $criteria);
        }

        $operator = $this->searchFields[$field]['operators'][$criteria['operator']];

        $key = $field . ' ' . $operator['operator'];

        if (isset($operator['pattern'])) {
            $pattern = $operator['pattern'];
            $value = str_replace('{{value}}', $value, $pattern);
        }

        $result = [$key => $value];

        return $result;
    }

    /**
     * Prepare and return where statement condition for empty value.
     *
     * @param string $field Field name
     * @param array $criteria Criteria properties
     * @return array
     */
    protected function getEmptyWhereCondition($field, array $criteria)
    {
        $emptyCriteria = $this->searchFields[$field]['operators'][$criteria['operator']]['emptyCriteria'];

        foreach ($emptyCriteria['values'] as $value) {
            $result[$emptyCriteria['aggregator']][] = $field . ' ' . trim($value);
        }

        return $result;
    }

    /**
     * Get fields for Query's select statement.
     *
     * @param  array $data request data
     * @return array
     */
    public function getSelectClause(array $data)
    {
        $result = [];
        if (empty($data['display_columns'])) {
            return $result;
        }

        $result = $data['display_columns'];

        if (!is_array($result)) {
            $result = (array)$result;
        }

        $primaryKey = $this->table->aliasField($this->table->getPrimaryKey());
        if (!in_array($primaryKey, $result)) {
            array_unshift($result, $primaryKey);
        }

        $result = $this->filterFields($result);

        return $result;
    }

    /**
     * Group by clause getter method.
     *
     * @param array $data Search data
     * @return array
     */
    protected function getGroupByClause(array $data)
    {
        return empty($data['group_by']) ? [] : (array)$data['group_by'];
    }

    /**
     * Method that pre-saves search and returns saved record id.
     *
     * @param array $data Search data
     * @return string
     */
    protected function preSave(array $data)
    {
        // delete old pre-saved searches
        $this->deletePreSaved();

        $entity = $this->searchTable->newEntity();

        $entity = $this->normalize($entity, $data, $data);
        $this->searchTable->save($entity);

        return $entity->id;
    }

    /**
     * Normalize search.
     *
     * @param \Search\Model\Entity\SavedSearch $entity Search entity
     * @param array $saved Saved search data
     * @param array $latest Latest search data
     * @return \Search\Model\Entity\SavedSearch
     */
    protected function normalize(SavedSearch $entity, array $saved, array $latest)
    {
        // Backward compatibility: search content must always contain 'saved' and 'latest' keys.
        $saved = isset($saved['saved']) ? $saved['saved'] : $saved;
        $latest = isset($latest['latest']) ?
            $latest['latest'] :
            (isset($latest['saved']) ? $latest['saved'] : $latest);

        // Backward compatibility: always prefix search criteria, display columns and sort by fields with table name.
        $filterFunc = function ($data) {
            if (array_key_exists('criteria', $data)) {
                foreach ($data['criteria'] as $field => $option) {
                    unset($data['criteria'][$field]);
                    $data['criteria'][$this->table->aliasField($field)] = $option;
                }
            }

            if (array_key_exists('display_columns', $data)) {
                $data['display_columns'] = array_values($data['display_columns']);
                foreach ($data['display_columns'] as &$field) {
                    $field = $this->table->aliasField($field);
                }
            }

            if (array_key_exists('sort_by_field', $data)) {
                $data['sort_by_field'] = $this->table->aliasField($data['sort_by_field']);
            }

            return $data;
        };

        $saved = $filterFunc($saved);
        $latest = $filterFunc($latest);

        $entity->user_id = $this->user['id'];
        $entity->model = $this->table->getRegistryAlias();
        $entity->shared = Options::SHARED_STATUS_PRIVATE;
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
