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
namespace Search\View\Cell;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use Cake\View\Cell;
use Cake\View\View;
use InvalidArgumentException;
use Search\Utility;
use Search\Utility\Options;
use Search\Utility\Search;

final class ResultCell extends Cell
{
    private $requiredOptions = ['entity', 'searchData', 'searchableFields', 'associationLabels', 'preSaveId'];

    private $charts = [
        ['type' => 'funnelChart', 'icon' => 'filter'],
        ['type' => 'donutChart', 'icon' => 'pie-chart'],
        ['type' => 'barChart', 'icon' => 'bar-chart']
    ];

    /**
     * Cell display method.
     *
     * @param array $options Search options
     * @param \Cake\View\View $view View instance
     * @return void
     */
    public function display(array $options, View $view)
    {
        $this->validateOptions($options);
        $this->setView($view);
        $this->setIsBatch();
        $this->setIsGroup();
        $this->setIsExport();
        $this->setViewOptions();
        $this->setTableOptions();
        $this->setDatatableOptions();
        $this->setChartOptions();
    }

    /**
     * Validates required options and sets them as class properties.
     *
     * @param array $options Search options
     * @return void
     */
    private function validateOptions(array $options)
    {
        foreach ($this->requiredOptions as $name) {
            if (!array_key_exists($name, $options)) {
                throw new InvalidArgumentException(sprintf('Required parameter "%s" is missing.', $name));
            }

            $this->{$name} = $options[$name];
        }
    }

    /**
     * View instance setter.
     *
     * @param \Cake\View\View $view View instance
     * @return void
     */
    private function setView(View $view)
    {
        $this->set('cakeView', $view);
    }

    /**
     * Html table id getter.
     *
     * @return string
     */
    private function getTableId()
    {
        if (property_exists($this, 'tableId')) {
            return $this->tableId;
        }

        $this->tableId = 'table-datatable-' . uniqid();

        return $this->tableId;
    }

    /**
     * Batch flag setter.
     *
     * @return void
     */
    private function setIsBatch()
    {
        $this->set('isBatch', (bool)$this->getBatch());
    }

    /**
     * Batch getter.
     *
     * @return bool
     */
    private function getBatch()
    {
        if (property_exists($this, 'batch')) {
            return $this->batch;
        }

        $this->batch = (bool)Configure::read('Search.batch.active');

        return $this->batch;
    }

    /**
     * Group flag setter.
     *
     * @return void
     */
    private function setIsGroup()
    {
        $this->set('isGroup', (bool)$this->getGroupByField());
    }

    /**
     * Group field getter.
     *
     * @return string
     */
    private function getGroupByField()
    {
        if (property_exists($this, 'groupByField')) {
            return $this->groupByField;
        }

        $this->groupByField = !empty($this->searchData['group_by']) ? $this->searchData['group_by'] : '';

        return $this->groupByField;
    }

    /**
     * Export flag setter.
     *
     * @return void
     */
    private function setIsExport()
    {
        $this->set('isExport', (bool)$this->getExport());
    }

    /**
     * Export status getter.
     *
     * @return bool
     */
    private function getExport()
    {
        if (property_exists($this, 'export')) {
            return $this->export;
        }

        $this->export = (bool)Configure::read('Search.dashboardExport');

        return $this->export;
    }

    /**
     * View options setter.
     *
     * @return void
     */
    private function setViewOptions()
    {
        // search url if is a saved one
        list($plugin, $controller) = pluginSplit($this->entity->get('model'));

        $title = $this->entity->has('name') ? $this->entity->get('name') : $controller;
        $url = ['plugin' => $plugin, 'controller' => $controller, 'action' => 'search', $this->entity->get('id')];

        $result = ['title' => $title, 'url' => $url];

        if ($this->getExport()) {
            $result['exportUrl'] = Router::url([
                'plugin' => $plugin,
                'controller' => $controller,
                'action' => 'export-search',
                $this->entity->get('id'),
                $this->entity->get('name')
            ]);
        }

        $this->set('viewOptions', $result);
    }

    /**
     * Html table options setter.
     *
     * @return void
     */
    private function setTableOptions()
    {
        $result = [
            'id' => $this->getTableId(),
            'headers' => $this->getTableHeaders()
        ];

        $this->set('tableOptions', $result);
    }

    /**
     * Html table headers getter.
     *
     * @return array
     */
    private function getTableHeaders()
    {
        $result = [];
        foreach ($this->getDisplayColumns() as $column) {
            $label = $column;
            if (array_key_exists($label, $this->searchableFields)) {
                $label = $this->searchableFields[$label]['label'];
            }

            list($fieldModel, ) = pluginSplit($column);
            if (array_key_exists($fieldModel, $this->associationLabels)) {
                $label .= ' (' . $this->associationLabels[$fieldModel] . ')';
            }

            $result[] = $label;
        }

        return $result;
    }

    /**
     * DataTable options setter.
     *
     * @return void
     */
    private function setDatatableOptions()
    {
        list($plugin, $controller) = pluginSplit($this->entity->get('model'));

        $result = [
            'table_id' => '#' . $this->getTableId(),
            'order' => [$this->getOrderField(), $this->getOrderDirection()],
            'ajax' => [
                'url' => Router::url([
                    'plugin' => $plugin,
                    'controller' => $controller,
                    'action' => 'search',
                    $this->preSaveId
                ]),
                'columns' => $this->getDatatableColumns(),
                'extras' => ['format' => 'pretty']
            ],
        ];

        if (!$this->getGroupByField() && $this->getBatch()) {
            $result['batch'] = ['id' => Configure::read('Search.batch.button_id')];
        }

        $this->set('dtOptions', $result);
    }

    /**
     * Chart options setter.
     *
     * @return void
     */
    private function setChartOptions()
    {
        $groupByField = $this->getGroupByField();
        if (!$groupByField) {
            return;
        }

        list($plugin, $controller) = pluginSplit($this->entity->get('model'));
        list($prefix, $fieldName) = pluginSplit($groupByField);

        $result = [];
        foreach ($this->charts as $chart) {
            $result[] = [
                'chart' => $chart['type'],
                'icon' => $chart['icon'],
                'ajax' => [
                    'url' => Router::url([
                        'plugin' => $plugin,
                        'controller' => $controller,
                        'action' => 'search',
                        $this->entity->get('id')
                    ]),
                    'format' => 'pretty',
                ],
                'options' => [
                    'element' => Inflector::delimit($chart['type']) . '_' . $this->getTableId(),
                    'resize' => true,
                    'hideHover' => true,
                    'data' => [],
                    'barColors' => ['#0874c7', '#04645e', '#5661f8', '#8298c1', '#c6ba08', '#07ada3'],
                    'lineColors' => ['#0874c7', '#04645e', '#5661f8', '#8298c1', '#c6ba08', '#07ada3'],
                    'labels' => [Inflector::humanize(Search::GROUP_BY_FIELD), Inflector::humanize($fieldName)],
                    'xkey' => [$groupByField],
                    'ykeys' => [$prefix . '.' . Search::GROUP_BY_FIELD]
                ]
            ];
        }

        $this->set('chartOptions', $result);
    }

    /**
     * Sort column getter.
     *
     * @return int
     */
    private function getOrderField()
    {
        $result = (int)array_search($this->searchData['sort_by_field'], $this->getDisplayColumns());

        if ($this->getBatch() && !$this->getGroupByField()) {
            $result += 1;
        }

        return $result;
    }

    /**
     * Sort direction getter.
     *
     * @return string
     */
    private function getOrderDirection()
    {
        $result = !empty($this->searchData['sort_by_order']) ?
            $this->searchData['sort_by_order'] :
            Options::DEFAULT_SORT_BY_ORDER;

        return $result;
    }

    /**
     * DataTable columns getter.
     *
     * @return array
     */
    private function getDatatableColumns()
    {
        $result = $this->getDisplayColumns();

        if (!$this->getGroupByField()) {
            $result[] = Utility::MENU_PROPERTY_NAME;
        }

        if (!$this->getGroupByField() && $this->getBatch()) {
            $table = TableRegistry::get($this->entity->get('model'));
            // add primary key in FIRST position
            array_unshift($result, $table->aliasField($table->getPrimaryKey()));
        }

        return $result;
    }

    /**
     * Display columns getter.
     *
     * @return array
     */
    private function getDisplayColumns()
    {
        if (property_exists($this, 'displayColumns')) {
            return $this->displayColumns;
        }

        $this->displayColumns = $this->searchData['display_columns'];

        $groupByField = $this->getGroupByField();

        if ($groupByField) {
            list($prefix, ) = pluginSplit($groupByField);
            $countField = $prefix . '.' . Search::GROUP_BY_FIELD;

            $this->displayColumns = [$groupByField, $countField];
        }

        return $this->displayColumns;
    }
}
