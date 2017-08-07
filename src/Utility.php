<?php
namespace Search;

use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\ORM\ResultSet;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\View\View;

class Utility
{
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
        $event = new Event('Search.Model.Search.searchabeFields', $this, [
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
     * Method that re-formats entities to Datatables supported format.
     *
     * @param \Cake\ORM\ResultSet $resultSet ResultSet
     * @param array $fields Display fields
     * @param \Cake\ORM\Table $table Table instance
     * @return array
     */
    public function toDatatables(ResultSet $resultSet, array $fields, Table $table)
    {
        $result = [];

        if ($resultSet->isEmpty()) {
            return $result;
        }

        $registryAlias = $table->getRegistryAlias();
        $alias = $table->getAlias();

        foreach ($resultSet as $key => $entity) {
            foreach ($fields as $field) {
                list($tableName, $field) = explode('.', $field);
                // current table field
                if ($alias === $tableName) {
                    $result[$key][] = $entity->get($field);
                    continue;
                }

                if (!$entity->get('_matchingData')) {
                    continue;
                }

                if (!isset($entity->_matchingData[$tableName])) {
                    continue;
                }
                // associated table field
                $result[$key][] = $entity->_matchingData[$tableName]->get($field);
            }

            $event = new Event('Search.View.View.Menu.Actions', new View(), [
                'entity' => $entity,
                'model' => $registryAlias
            ]);
            EventManager::instance()->dispatch($event);

            $result[$key][] = '<div class="btn-group btn-group-xs" role="group">' . $event->result . '</div>';
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
