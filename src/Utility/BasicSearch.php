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
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use InvalidArgumentException;
use Search\Event\EventName;
use Search\Utility;
use Search\Utility\Options;
use Search\Utility\Search;

class BasicSearch
{
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

        $this->searchFields = Utility::instance()->getSearchableFields($this->table, $this->user);
    }

    /**
     * Prepare basic search query's where statement
     *
     * @param string $value Search query value
     * @return array
     */
    public function getCriteria($value)
    {
        $fields = $this->getFields();
        if (empty($fields)) {
            return [];
        }

        $result = [];
        foreach ($fields as $field) {
            $val = $this->getFieldValue($field, $value);
            if (empty($val)) {
                continue;
            }
            $result[$field] = $val;
        }

        return $result;
    }

    /**
     * Method that broadcasts an Event to generate the basic search fields.
     * If the Event result is empty then it falls back to using the display field.
     * If the display field is a virtual one then if falls back to searchable fields,
     * using the ones that their type matches the basicSearchFieldTypes list.
     *
     * @return array
     */
    protected function getFields()
    {
        if (empty($this->searchFields)) {
            return [];
        }

        $event = new Event((string)EventName::MODEL_SEARCH_BASIC_SEARCH_FIELDS(), $this, [
            'table' => $this->table
        ]);
        EventManager::instance()->dispatch($event);

        $result = (array)$event->result;

        if (empty($result)) {
            $result = (array)$this->table->aliasField($this->table->displayField());
        }

        $result = $this->filterFields($result);

        if (!empty($result)) {
            return $result;
        }

        $result = $this->getDefaultFields();

        return $result;
    }

    /**
     * Filters basic search fields by removing virtual ones.
     *
     * @param array $fields Basic search fields
     * @return array
     */
    protected function filterFields(array $fields)
    {
        // get table columns, aliased
        $columns = $this->table->schema()->columns();
        foreach ($columns as $index => $column) {
            $columns[$index] = $this->table->aliasField($column);
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
     * @return array
     */
    protected function getDefaultFields()
    {
        $result = [];
        $types = Options::getBasicSearchFieldTypes();

        foreach ($this->searchFields as $field => $properties) {
            if (in_array($properties['type'], $types)) {
                $result[] = $field;
            }
        }

        return $result;
    }

    /**
     * Field value getter for basic search criteria.
     *
     * @param string $field Field name
     * @param string $value Search query value
     * @return array
     */
    protected function getFieldValue($field, $value)
    {
        if (!array_key_exists($field, $this->searchFields)) {
            return [];
        }

        $type = $this->searchFields[$field]['type'];
        if (!in_array($type, Options::getBasicSearchFieldTypes())) {
            return [];
        }

        if ('related' === $type) {
            $sourceTable = TableRegistry::get($this->searchFields[$field]['source']);
            $value = $this->getRelatedValues($sourceTable, $value);
        }

        if (empty($value)) {
            return [];
        }

        $result = [];
        foreach ((array)$value as $val) {
            $result[] = [
                'type' => $type,
                'operator' => key($this->searchFields[$field]['operators']),
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
     * run a search in the related module (recursively) to fetch and
     * return the entities IDs matching the search string.
     *
     * @param \Cake\ORM\Table $table Related table instance
     * @param string $value Search query value
     * @return array
     */
    protected function getRelatedValues(Table $table, $value)
    {
        $search = new Search($table, $this->user);
        $basicSearch = new BasicSearch($table, $this->user);

        $criteria = $basicSearch->getCriteria($value);
        if (empty($criteria)) {
            return [];
        }

        $data = [
            'aggregator' => 'OR',
            'criteria' => $criteria
        ];

        $query = $search->execute($data);
        if ($query->isEmpty()) {
            return [];
        }

        $result = [];
        foreach ($query->all() as $entity) {
            $result[] = $entity->id;
        }

        return $result;
    }
}
