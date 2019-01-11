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
namespace Search\Widgets\Reports;

use Cake\Utility\Inflector;

class LineChartReportWidget extends BaseReportGraphs
{
    public $type = 'lineChart';

    public $requiredFields = ['query', 'columns', 'x_axis', 'y_axis'];

    /**
     * getChartData method
     *
     * Assembles the chart data for the LineChart widget
     *
     * @param array $data with report config and data.
     * @return array $chartData.
     */
    public function getChartData(array $data = []) : array
    {
        $labels = [];
        $report = $this->config;

        $chartData = [
            'chart' => $this->type,
            'options' => [
                'element' => $this->getContainerId(),
                'resize' => true,
                'hideHover' => true
            ],
        ];

        $columns = explode(',', $report['info']['columns']);

        foreach ($columns as $column) {
            array_push($labels, Inflector::humanize($column));
        }
        $options = [
            'data' => $data,
            'lineColors' => $this->getChartColors(),
            'labels' => $labels,
            'xkey' => explode(',', $report['info']['x_axis']),
            'ykeys' => explode(',', $report['info']['y_axis'])
        ];

        $chartData['options'] = array_merge($chartData['options'], $options);

        if (!empty($data)) {
            $this->setData($chartData);
        }

        return $chartData;
    }

    /**
     * getScripts method
     *
     * Specifies required JS/CSS libs for given chart
     *
     * @param mixed[] $data passed in the method.
     * @return mixed[] $content with JS/CSS libs.
     */
    public function getScripts(array $data = []) : array
    {
        return [
            'post' => [
                'css' => [
                    'type' => 'css',
                    'content' => [
                        'AdminLTE./bower_components/morris.js/morris',
                    ],
                    'block' => 'css',
                ],
                'javascript' => [
                    'type' => 'script',
                    'content' => [
                        'https://cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js',
                        'AdminLTE./bower_components/morris.js/morris.min',
                    ],
                    'block' => 'scriptBottom',
                ],
            ]
        ];
    }
}
