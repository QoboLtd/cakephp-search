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
namespace Search\Widgets;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Search\Utility;
use Search\Utility\Search;
use Search\Utility\Validator;
use Search\Widgets\BaseWidget;

class SavedSearchWidget extends BaseWidget
{
    const TABLE_PREFIX = 'table-datatable-';

    protected $_entity = null;

    protected $_tableName = 'Search.SavedSearches';

    protected $_tableInstance = null;

    protected $_data = [];

    public $renderElement = 'Search.Widgets/table';

    public $options = [];

    public $type = 'saved_search';
    public $errors = [];

    /**
     * construct method
     *
     * @param array $options containing widget entity.
     * @return void.
     */
    public function __construct($options = [])
    {
        if (!empty($options['entity'])) {
            $this->_entity = $options['entity'];
        }
        $this->_tableInstance = TableRegistry::get($this->_tableName);
    }

    /**
     * getOptions method.
     *
     * @return array $options of the widget.
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return string $type of the widget.
     */
    public function getSavedSearchType()
    {
        return $this->getData()->type;
    }

    /**
     * @return array $_data of the widget.
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Retrieve SavedSearch results for the widget
     *
     * @param array $options containing entity and view params.
     * @return array $results from $this->_data.
     */
    public function getResults(array $options = [])
    {
        $this->setContainerId($options['entity']);

        $savedSearch = [];
        try {
            $query = $this->_tableInstance->findById($this->_entity->widget_id);
            if ($query->isEmpty()) {
                return $savedSearch;
            }
            $savedSearch = $query->first();
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        $table = TableRegistry::get($savedSearch->model);

        $search = new Search($table, $options['user']);
        // keeps backward compatibility
        $entity = $search->reset($savedSearch, $options['user']);
        $entity->content = json_decode($entity->content, true);
        $entity->content['saved'] = Validator::validateData($table, $entity->content['saved'], $options['user']);

        $this->options['fields'] = Utility::instance()->getSearchableFields($table, $options['user']);
        $this->options['associationLabels'] = Utility::instance()->getAssociationLabels($table);

        $this->_data = $entity;

        return $this->getData();
    }

    /**
     * setContainerId method.
     *
     * Setting unique identifier of the widget.
     *
     * @param array $entity used for setting id of widget.
     * @return string $containerId of the widget.
     */
    public function setContainerId($entity = null)
    {
        $this->containerId = self::TABLE_PREFIX . md5($entity->id);

        return $this->containerId;
    }

    /**
     * @return array $errors in case of validation
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
