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
    /**
     * Required Cell parameters.
     *
     * @var array
     */
    private $requiredOptions = [
        'entity',
        'searchData',
        'searchableFields',
        'associationLabels',
        'batch',
        'preSaveId',
        'action'
    ];

    /**
     * Charts list.
     *
     * @var array
     */
    private $charts = [
        ['type' => 'funnelChart', 'icon' => 'filter'],
        ['type' => 'doughnut', 'icon' => 'pie-chart'],
        ['type' => 'bar', 'icon' => 'bar-chart']
    ];

    /**
     * @var string[] $displayColumns
     */
    private $displayColumns = [];

    /**
     * @var array
     */
    private $searchableFields = [];

    /**
     * @var \Search\Model\Entity\SavedSearch
     */
    private $entity;

    /**
     * @var array
     */
    private $searchData = [];

    /**
     * @var string
     */
    private $tableId = '';

    /**
     * @var bool
     */
    private $batch = false;

    /**
     * @var bool
     */
    private $export = false;

    /**
     * @var string
     */
    private $groupByField = '';

    /**
     * @var array
     */
    private $associationLabels = [];

    private $preSaveId = '';

    /**
     * Cell display method.
     *
     * @param mixed[] $options Search options
     * @param \Cake\View\View $view View instance
     * @return void
     */
    public function display(array $options, View $view): void
    {
        $this->validateOptions($options);

        $this->set('cakeView', $view);
        $this->set('isBatch', $this->batch);
        $this->set('isGroup', (bool)$this->getGroupByField());
        $this->set('isSystem', (bool)$this->entity->get('system'));
        $this->set('isExport', $this->getExport());
        $this->set('viewOptions', $this->getViewOptions());
        $this->set('tableOptions', [
            'id' => $this->getTableId(),
            'headers' => $this->getTableHeaders()
        ]);
        $this->set('dtOptions', $this->getDatatableOptions());
        $this->set('chartOptions', $this->getChartOptions());
    }

    /**
     * Validates required options and sets them as class properties.
     *
     * @param mixed[] $options Search options
     * @return void
     */
    private function validateOptions(array $options): void
    {
        foreach ($this->requiredOptions as $name) {
            if (!array_key_exists($name, $options)) {
                throw new InvalidArgumentException(sprintf('Required parameter "%s" is missing.', $name));
            }

            $this->{$name} = $options[$name];
        }
    }

    /**
     * Html table id getter.
     *
     * @return string
     */
    private function getTableId(): string
    {
        if ('' === $this->tableId) {
            $this->tableId = 'table-datatable-' . md5($this->preSaveId);
        }

        return $this->tableId;
    }

    /**
     * Group field getter.
     *
     * @return string
     */
    private function getGroupByField(): string
    {
        if ('' === $this->groupByField) {
            $this->groupByField = ! empty($this->searchData['group_by']) ? $this->searchData['group_by'] : '';
        }

        return $this->groupByField;
    }

    /**
     * Export status getter.
     *
     * @return bool
     */
    private function getExport(): bool
    {
        if (false === $this->export) {
            $this->export = (bool)Configure::read('Search.dashboardExport');
        }

        return $this->export;
    }

    /**
     * View options getter.
     *
     * @return mixed[]
     */
    private function getViewOptions(): array
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

        return $result;
    }

    /**
     * Html table headers getter.
     *
     * @return mixed[]
     */
    private function getTableHeaders(): array
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
     * DataTable options getter.
     *
     * @return mixed[]
     */
    private function getDatatableOptions(): array
    {
        list($plugin, $controller) = pluginSplit($this->entity->get('model'));

        $result = [
            'table_id' => '#' . $this->getTableId(),
            'order' => [$this->getOrderField(), $this->getOrderDirection()],
            'ajax' => [
                'url' => Router::url([
                    'plugin' => $plugin,
                    'controller' => $controller,
                    'action' => $this->action,
                    $this->preSaveId
                ]),
                'columns' => $this->getDatatableColumns(),
                'extras' => ['format' => 'pretty']
            ],
        ];

        if (! $this->getGroupByField() && $this->batch) {
            $result['batch'] = ['id' => Configure::read('Search.batch.button_id')];
        }

        return $result;
    }

    /**
     * Chart options getter.
     *
     * @return mixed[]
     */
    private function getChartOptions(): array
    {
        $groupByField = $this->getGroupByField();
        if (!$groupByField) {
            return [];
        }

        list($plugin, $controller) = pluginSplit($this->entity->get('model'));
        list($prefix, $fieldName) = pluginSplit($groupByField);

        $result = [];
        foreach ($this->charts as $chart) {
            $result[] = [
                'chart' => $chart['type'],
                'icon' => $chart['icon'],
                'id' => Inflector::delimit($chart['type']) . '_' . $this->getTableId(),
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
                    'resize' => true,
                    'hideHover' => true,
                    'labels' => [Inflector::humanize(Search::GROUP_BY_FIELD), Inflector::humanize($fieldName)],
                    'xkey' => [$groupByField],
                    'ykeys' => [$prefix . '.' . Search::GROUP_BY_FIELD]
                ]
            ];
        }

        return $result;
    }

    /**
     * Sort column getter.
     *
     * @return int
     */
    private function getOrderField(): int
    {
        $result = (int)array_search($this->searchData['sort_by_field'], $this->getDisplayColumns());

        if ($this->batch && !$this->getGroupByField()) {
            $result += 1;
        }

        return $result;
    }

    /**
     * Sort direction getter.
     *
     * @return string
     */
    private function getOrderDirection(): string
    {
        $result = !empty($this->searchData['sort_by_order']) ?
            $this->searchData['sort_by_order'] :
            Options::DEFAULT_SORT_BY_ORDER;

        return $result;
    }

    /**
     * DataTable columns getter.
     *
     * @return mixed[]
     */
    private function getDatatableColumns(): array
    {
        $result = $this->getDisplayColumns();

        if (!$this->getGroupByField()) {
            $result[] = Utility::MENU_PROPERTY_NAME;
        }

        if (!$this->getGroupByField() && $this->batch) {
            $table = TableRegistry::get($this->entity->get('model'));
            // add primary key in FIRST position
            foreach ((array)$table->getPrimaryKey() as $primaryKey) {
                array_unshift($result, $table->aliasField($primaryKey));
            }
        }

        return $result;
    }

    /**
     * Display columns getter.
     *
     * @return string[]
     */
    private function getDisplayColumns(): array
    {
        if (! empty($this->displayColumns)) {
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
