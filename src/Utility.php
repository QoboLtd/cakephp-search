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
namespace Search;

use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\ORM\ResultSet;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\View\View;
use Search\Event\EventName;

class Utility
{
    /**
     * Property name for menu items
     */
    const MENU_PROPERTY_NAME = 'actions_column';

    /**
     * The globally available instance of Search Utility.
     *
     * @var \Cake\Event\EventManager
     */
    protected static $instance;

    /**
     * Target table searchable fields.
     *
     * @var array
     */
    protected $searchableFields = [];

    /**
     * Searchable associations list.
     *
     * @var array
     */
    protected $searchableAssociations = ['manyToOne'];

    /**
     * Returns the globally available instance of a Search\Utility.
     *
     * @param \Search\Utility|null $utility Utility instance
     * @return static The global search utility
     */
    public static function instance(Utility $utility = null)
    {
        if ($utility instanceof Utility) {
            static::$instance = $utility;
        }

        if (empty(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Return Table's searchable fields.
     *
     * @param \Cake\ORM\Table $table Table object
     * @param array $user User info
     * @return array
     */
    public function getSearchableFields(Table $table, array $user)
    {
        $alias = $table->alias();

        if (!empty($this->searchableFields[$alias])) {
            return $this->searchableFields[$alias];
        }

        $this->searchableFields[$alias] = array_merge(
            $this->_getSearchableFields($table, $user),
            $this->_getAssociatedSearchableFields($table, $user)
        );

        return $this->searchableFields[$alias];
    }

    /**
     * Get and return searchable fields using Event.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @param array $user User info
     * @return array
     */
    protected function _getSearchableFields(Table $table, array $user)
    {
        $event = new Event((string)EventName::MODEL_SEARCH_SEARCHABLE_FIELDS(), $this, [
            'table' => $table,
            'user' => $user
        ]);
        EventManager::instance()->dispatch($event);

        return $event->result ? $event->result : [];
    }

    /**
     * Get associated tables searchable fields.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @param array $user User info
     * @return array
     */
    protected function _getAssociatedSearchableFields(Table $table, array $user)
    {
        $result = [];
        foreach ($table->associations() as $association) {
            // skip non-supported associations
            if (!in_array($association->type(), $this->searchableAssociations)) {
                continue;
            }

            $targetTable = $association->getTarget();

            // skip associations with itself
            if ($targetTable->getTable() === $table->getTable()) {
                continue;
            }

            // fetch associated model searchable fields
            $searchableFields = $this->_getSearchableFields($targetTable, $user);
            if (empty($searchableFields)) {
                continue;
            }

            $result = array_merge($result, $searchableFields);
        }

        return $result;
    }

    /**
     * Method that formats resultset.
     *
     * @param \Cake\ORM\ResultSet $resultSet ResultSet
     * @param array $fields Display fields
     * @param \Cake\ORM\Table $table Table instance
     * @param array $user User info
     * @return array
     */
    public function formatter(ResultSet $resultSet, array $fields, Table $table, array $user)
    {
        $result = [];

        if ($resultSet->isEmpty()) {
            return $result;
        }

        $cakeView = new View();
        $registryAlias = $table->getRegistryAlias();
        $alias = $table->getAlias();

        foreach ($resultSet as $key => $entity) {
            foreach ($fields as $field) {
                list($tableName, $fieldName) = explode('.', $field);
                // current table field
                if ($alias === $tableName) {
                    $result[$key][$field] = $entity->get($fieldName);
                    continue;
                }

                if (!$entity->get('_matchingData')) {
                    continue;
                }

                if (!isset($entity->_matchingData[$tableName])) {
                    continue;
                }
                // associated table field
                $result[$key][$field] = $entity->_matchingData[$tableName]->get($fieldName);
            }

            $result[$key][static::MENU_PROPERTY_NAME] = $cakeView->element('Search.Menu/search-view-actions', [
                'entity' => $entity,
                'model' => $registryAlias,
                'user' => $user
            ]);
        }

        return $result;
    }

    /**
     * Method that re-formats entities to Datatables supported format.
     *
     * @param \Cake\ORM\ResultSet $resultSet ResultSet
     * @param array $fields Display fields
     * @param \Cake\ORM\Table $table Table instance
     * @return array
     */
    public function toCsv(ResultSet $resultSet, array $fields, Table $table)
    {
        $result = [];

        if ($resultSet->isEmpty()) {
            return $result;
        }

        $registryAlias = $table->getRegistryAlias();
        $alias = $table->getAlias();

        foreach ($resultSet as $key => $entity) {
            foreach ($fields as $field) {
                list($tableName, $fieldName) = explode('.', $field);
                // current table field
                if ($alias === $tableName) {
                    $result[$key][$field] = $entity->get($fieldName);
                    continue;
                }

                if (!$entity->get('_matchingData')) {
                    continue;
                }

                if (!isset($entity->_matchingData[$tableName])) {
                    continue;
                }
                // associated table field
                $result[$key][$field] = $entity->_matchingData[$tableName]->get($fieldName);
            }
        }

        return $result;
    }

    /**
     * Associations labels getter.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @return array
     */
    public function getAssociationLabels(Table $table)
    {
        $result = [];
        foreach ($table->associations() as $association) {
            if (!in_array($association->type(), $this->searchableAssociations)) {
                continue;
            }

            $result[$association->getName()] = Inflector::humanize($association->getForeignKey());
        }

        return $result;
    }
}
