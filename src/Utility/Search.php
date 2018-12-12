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

deprecationWarning(
    '"Search\Utility\Search" class is deprecated. Use "Search\Model\Behavior\SearchableBehavior" instead'
);

use Cake\Database\Expression\Comparison;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Datasource\QueryInterface;
use Cake\Datasource\RepositoryInterface;
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

class Search
{
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
     * @var \Cake\Datasource\RepositoryInterface
     */
    protected $searchTable;

    /**
     * Constructor.
     *
     * @param \Cake\Datasource\RepositoryInterface $table Searchable table
     * @param mixed[] $user User info
     * @return void
     */
    public function __construct(RepositoryInterface $table, array $user)
    {
        /** @var \Cake\ORM\Table */
        $table = $table;
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
     * @param mixed[] $data Request data
     * @return \Cake\Datasource\QueryInterface
     */
    public function execute(array $data) : QueryInterface
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
     * @param mixed[] $searchData Request data
     * @return string
     */
    public function create(array $searchData): string
    {
        $searchData = Validator::validateData($this->table, $searchData, $this->user);

        // pre-save search
        return $this->preSave($searchData);
    }

    /**
     * Update search.
     *
     * @param mixed[] $searchData Request data
     * @param string $id Existing search id
     * @return \Cake\Datasource\EntityInterface|false
     */
    public function update(array $searchData, string $id)
    {
        /**
         * @var \Search\Model\Entity\SavedSearch
         */
        $entity = $this->searchTable->get($id);
        $entity = $this->normalize($entity, $entity->get('content'), $searchData);

        return $this->searchTable->save($entity);
    }

    /**
     * Get search.
     *
     * @param string $id Existing search id
     * @return \Search\Model\Entity\SavedSearch
     */
    public function get(string $id): SavedSearch
    {
        $id = !empty($id) ? $id : $this->create([]);
        /**
         * @var \Search\Model\Entity\SavedSearch
         */
        $entity = $this->searchTable->get($id);
        $entity = $this->normalize($entity, $entity->get('content'), $entity->get('content'));

        return $entity;
    }

    /**
     * Reset search.
     *
     * @param \Search\Model\Entity\SavedSearch $entity Search entity
     * @return \Search\Model\Entity\SavedSearch|null
     */
    public function reset(SavedSearch $entity) : ?SavedSearch
    {
        $content = $entity->get('content');

        // for backward compatibility
        $saved = isset($content['saved']) ? $content['saved'] : $content;
        $entity = $this->normalize($entity, $saved, $saved);

        return $this->searchTable->save($entity) ? $entity : null;
    }

    /**
     * Prepare search data from request data.
     *
     * @param \Cake\Http\ServerRequest $request Request object
     * @return mixed[]
     */
    public function prepareData(ServerRequest $request): array
    {
        $result = $request->getData();

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
     * @param mixed[] $data Search data
     * @return mixed[]
     */
    protected function byAssociations(array $data): array
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

            $primaryKeys = [];
            foreach ((array)$targetTable->getPrimaryKey() as $primaryKey) {
                $primaryKeys[] = $targetTable->aliasField($primaryKey);
            }

            // instantiate Search on related table
            $search = new Search($targetTable, $this->user);

            $select = array_diff($search->getSelectClause($data), $primaryKeys);
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
     * @param mixed[] $data request data
     * @return mixed[]
     */
    protected function getWhereClause(array $data): array
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
     * @param mixed[] $fields Search fields
     * @return mixed[]
     */
    protected function filterFields(array $fields): array
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
     * @param mixed[] $criteria Criteria properties
     * @return mixed[]
     */
    protected function getWhereCondition(string $field, array $criteria): array
    {
        $value = is_array($criteria['value']) ? $criteria['value'] : trim($criteria['value']);

        if ('' === $value) {
            return $this->getEmptyWhereCondition($field, $criteria);
        }

        $value = $this->handleMagicValue($value);
        $operator = $this->searchFields[$field]['operators'][$criteria['operator']];

        if (isset($operator['pattern'])) {
            $pattern = $operator['pattern'];
            $value = str_replace('{{value}}', $value, $pattern);
        }

        $type = 'string';
        if (!empty($this->searchFields[$field]['type']) && $this->searchFields[$field]['type'] !== 'related') {
            $type = $this->searchFields[$field]['type'];
        }

        if (in_array($operator['operator'], ['IN', 'NOT IN'])) {
            $type .= '[]';
            $value = (array)$value;
        }

        return [ new Comparison(new IdentifierExpression($field), $value, $type, $operator['operator']) ];
    }

    /**
     * Magic value handler.
     *
     * @param mixed $value Field value
     * @return mixed
     */
    protected function handleMagicValue($value)
    {
        switch (gettype($value)) {
            case 'string':
                $value = (new MagicValue($value, $this->user))->get();
                break;

            case 'array':
                foreach ($value as $key => $val) {
                    $value[$key] = (new MagicValue($val, $this->user))->get();
                }
                break;
        }

        return $value;
    }

    /**
     * Prepare and return where statement condition for empty value.
     *
     * @param string $field Field name
     * @param mixed[] $criteria Criteria properties
     * @return mixed[]
     */
    protected function getEmptyWhereCondition(string $field, array $criteria): array
    {
        $emptyCriteria = $this->searchFields[$field]['operators'][$criteria['operator']]['emptyCriteria'];

        $result = [];
        foreach ($emptyCriteria['values'] as $value) {
            $result[$emptyCriteria['aggregator']][] = $field . ' ' . trim($value);
        }

        return $result;
    }

    /**
     * Get fields for Query's select statement.
     *
     * @param  mixed[] $data request data
     * @return string[]
     */
    public function getSelectClause(array $data): array
    {
        if (empty($data['display_columns'])) {
            return [];
        }

        $result = (array)$data['display_columns'];
        foreach ((array)$this->table->getPrimaryKey() as $primaryKey) {
            $primaryKey = $this->table->aliasField($primaryKey);
            if (! in_array($primaryKey, $result)) {
                array_unshift($result, $primaryKey);
            }
        }

        $result = $this->filterFields($result);

        return $result;
    }

    /**
     * Group by clause getter method.
     *
     * @param mixed[] $data Search data
     * @return mixed[]
     */
    protected function getGroupByClause(array $data): array
    {
        return empty($data['group_by']) ? [] : (array)$data['group_by'];
    }

    /**
     * Method that pre-saves search and returns saved record id.
     *
     * @param mixed[] $data Search data
     * @return string
     */
    protected function preSave(array $data): string
    {
        /**
         * @var \Search\Model\Entity\SavedSearch
         */
        $entity = $this->searchTable->newEntity();

        $entity = $this->normalize($entity, $data, $data);
        $this->searchTable->save($entity);

        return $entity->id;
    }

    /**
     * Normalize search.
     *
     * @param \Search\Model\Entity\SavedSearch $entity Search entity
     * @param mixed[] $saved Saved search data
     * @param mixed[] $latest Latest search data
     * @return \Search\Model\Entity\SavedSearch
     */
    protected function normalize(SavedSearch $entity, array $saved, array $latest) : SavedSearch
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

        $entity->set('user_id', $this->user['id']);
        $entity->set('model', $this->table->getRegistryAlias());
        $entity->set('content', ['saved' => $saved, 'latest' => $latest]);

        return $entity;
    }
}
