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

deprecationWarning(
    '"Search\Utility" class is deprecated. To continue using it copy the ' .
    'file to your application and use it from there instead'
);

use Cake\Datasource\RepositoryInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;
use Cake\Event\EventManager;
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
     * @var \Search\Utility
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
     * @param \Cake\Datasource\RepositoryInterface $table Table object
     * @param mixed[] $user User info
     * @return mixed[]
     */
    public function getSearchableFields(RepositoryInterface $table, array $user) : array
    {
        $alias = $table->getAlias();

        if (!empty($this->searchableFields[$alias])) {
            return $this->searchableFields[$alias];
        }

        $event = new Event((string)EventName::MODEL_SEARCH_SEARCHABLE_FIELDS(), $this, [
            'table' => $table,
            'user' => $user
        ]);
        EventManager::instance()->dispatch($event);

        $this->searchableFields[$alias] = $event->result ? $event->result : [];

        return $this->searchableFields[$alias];
    }

    /**
     * Method that formats resultset.
     *
     * @param \Cake\Datasource\ResultSetInterface $resultSet ResultSet
     * @param string[] $fields Display fields
     * @param \Cake\Datasource\RepositoryInterface $table Table instance
     * @param mixed[] $user User info
     * @return mixed[]
     */
    public function formatter(ResultSetInterface $resultSet, array $fields, RepositoryInterface $table, array $user) : array
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
     * @param \Cake\Datasource\ResultSetInterface $resultSet ResultSet
     * @param string[] $fields Display fields
     * @param \Cake\ORM\Table $table Table instance
     * @return mixed[]
     */
    public function toCsv(ResultSetInterface $resultSet, array $fields, Table $table) : array
    {
        if ($resultSet->isEmpty()) {
            return [];
        }

        $alias = $table->getAlias();

        $result = [];
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
     * @param \Cake\Datasource\RepositoryInterface $table Table instance
     * @return mixed[]
     */
    public function getAssociationLabels(RepositoryInterface $table) : array
    {
        /** @var \Cake\ORM\Table */
        $table = $table;

        $result = [];
        foreach ($table->associations() as $association) {
            if (!in_array($association->type(), $this->searchableAssociations)) {
                continue;
            }

            $result[$association->getName()] = Inflector::humanize(current((array)$association->getForeignKey()));
        }

        return $result;
    }
}
