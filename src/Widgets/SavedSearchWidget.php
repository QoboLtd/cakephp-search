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

        $this->options['scripts'] = $this->getScripts(['data' => $entity]);
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
     * Retrieve the list of scripts needed to render the widget
     *
     * @param array $options passed
     * @return array $content with CSS/JS libs.
     */
    public function getScripts(array $options = [])
    {
        $searchData = $options['data']->content['saved'];

        list($plugin, $controller) = pluginSplit($options['data']->model);

        // DataTables options
        $config = [
            'table_id' => '#' . $this->getContainerId(),
            'order' => [
                (int)array_search($searchData['sort_by_field'], $searchData['display_columns']),
                $searchData['sort_by_order']
            ],
            'ajax' => [
                'token' => Configure::read('Search.api.token'),
                'url' => Router::url([
                    'plugin' => $plugin, 'controller' => $controller, 'action' => 'search', $options['data']->id
                ]),
                'extras' => ['format' => 'datatables']
            ],
        ];

        $content = [
            'post' => [
                'css' => [
                    'type' => 'css',
                    'content' => [
                        'Qobo/Utils./plugins/datatables/css/dataTables.bootstrap.min',
                        'Qobo/Utils./plugins/datatables/extensions/Select/css/select.bootstrap.min',
                        'Qobo/Utils./css/dataTables.batch'
                    ],
                    'block' => 'css',
                ],
                'javascript' => [
                    'type' => 'script',
                    'content' => [
                        'Qobo/Utils./plugins/datatables/datatables.min',
                        'Qobo/Utils./plugins/datatables/js/dataTables.bootstrap.min',
                        'Qobo/Utils./plugins/datatables/extensions/Select/js/dataTables.select.min',
                        'Qobo/Utils.dataTables.init',
                    ],
                    'block' => 'scriptBottom',
                ],
                'scriptBlock' => [
                    'type' => 'scriptBlock',
                    'content' => 'datatables_init.init(' . json_encode($config) . ');',
                    'block' => 'scriptBottom',
                ],
            ]
        ];

        return $content;
    }

    /**
     * @return array $errors in case of validation
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
