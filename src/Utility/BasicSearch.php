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
     * @param mixed[] $user User info
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
     * @return mixed[]
     */
    public function getCriteria(string $value): array
    {
        if ('' === trim($value)) {
            return [];
        }

        $fields = $this->getFields();
        if (empty($fields)) {
            return [];
        }

        $result = [];
        foreach ($fields as $field) {
            $criteria = $this->getFieldCriteria($field, $value);
            if (empty($criteria)) {
                continue;
            }

            $result[$field][] = $criteria;
        }

        return $result;
    }

    /**
     * Method that broadcasts an Event to generate the basic search fields.
     * If the Event result is empty then it falls back to using the display field.
     * If the display field is a virtual one then if falls back to searchable fields,
     * using the ones that their type matches the basicSearchFieldTypes list.
     *
     * @return mixed[]
     */
    protected function getFields(): array
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
            $result = (array)$this->table->aliasField($this->table->getDisplayField());
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
     * @param mixed[] $fields Basic search fields
     * @return mixed[]
     */
    protected function filterFields(array $fields): array
    {
        // get table columns, aliased
        $columns = $this->table->getSchema()->columns();
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
     * @return mixed[]
     */
    protected function getDefaultFields(): array
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
     * Field criteria getter for basic search field.
     *
     * @param string $field Field name
     * @param string $value Search query value
     * @return mixed[]
     */
    protected function getFieldCriteria(string $field, string $value): array
    {
        // not a searchable field
        if (!array_key_exists($field, $this->searchFields)) {
            return [];
        }

        // unsupported field type for basic search
        $type = $this->searchFields[$field]['type'];
        if (!in_array($type, Options::getBasicSearchFieldTypes())) {
            return [];
        }

        $result = [];
        switch ($type) {
            case 'related':
                $result = $this->getRelatedFieldValue($field, $value);
                break;

            default:
                $result = $this->getFieldValue($field, $value);
                break;
        }

        return $result;
    }

    /**
     * Field value getter for basic search criteria.
     *
     * @param string $field Field name
     * @param string $value Search query value
     * @return mixed[]
     */
    protected function getFieldValue(string $field, string $value): array
    {
        return [
            'type' => $this->searchFields[$field]['type'],
            'operator' => key($this->searchFields[$field]['operators']),
            'value' => $value
        ];
    }

    /**
     * Gets basic search values from Related module.
     *
     * This method is useful when you do a basic search on a related field,
     * in which the values are always uuid's. What this method will do is
     * run a search in the related module (recursively) to fetch and
     * return the entities IDs matching the search string.
     *
     * @param string $field Field name
     * @param string $value Search query value
     * @return mixed[]
     */
    protected function getRelatedFieldValue(string $field, string $value): array
    {
        $table = TableRegistry::get($this->searchFields[$field]['source']);

        // avoid infinite recursion
        if ($this->table->getAlias() === $table->getAlias()) {
            return [];
        }

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

        $resultSet = $search->execute($data)->all();
        if ($resultSet->isEmpty()) {
            return [];
        }

        $result = [];
        foreach ($resultSet as $entity) {
            $result[] = $entity->id;
        }

        return [
            'type' => $this->searchFields[$field]['type'],
            'operator' => key($this->searchFields[$field]['operators']),
            'value' => $result
        ];
    }
}
