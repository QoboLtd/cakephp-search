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

use Cake\Utility\Hash;
use Cake\Utility\Inflector;

class BarChartReportWidget extends BaseReportGraphs
{
    public $type = 'bar';

    public $requiredFields = ['query', 'columns', 'x_axis', 'y_axis'];

    /**
     * getChartData method
     *
     * Assembles graphs data from the reports config and data.
     *
     * @param array $data containing report configs and data.
     * @return array $chartData with defined chart information.
     */
    public function getChartData(array $data = []): array
    {
        $report = $this->config;

        // We suppose that in the x_axis are the values with labels
        $label_column_name = $report['info']['x_axis'];
        $list = $this->getList($report['modelName'], $label_column_name);

        if ($list) {
            $data = $this->sortListByLabel($data, $list, $label_column_name);
        }

        $label = Hash::extract($data, '{n}.' . $label_column_name);
        $columns = explode(',', $report['info']['columns']);
        $columns = array_diff($columns, [$label_column_name]);

        // Check if is a multiple set of data.
        $datasets = [];
        $is_multicolums = count($columns) > 1;

        foreach ($columns as $key => $value) {
            $colors = $this->getChartColors(count($data), $this->getContainerId() . (string)$key, !$is_multicolums);
            $datasets[] = [
                "label" => Inflector::humanize($value),
                "backgroundColor" => $is_multicolums ? $colors[0] : $colors,
                "data" => (array)Hash::extract($data, '{n}.' . $value)
            ];
        }

        $chartjs = [
            "type" => $this->type,
            "data" =>
            [
                "labels" => $label,
                "datasets" => $datasets
            ],
            "options" =>
            [
                "legend" => [
                    "display" => false,
                ],
                "scales" =>
                [
                    "yAxes" => [
                        [
                            "ticks" =>
                            [
                                "beginAtZero" => true
                            ]
                        ]
                    ],
                    "xAxes" => [
                        [
                            "ticks" =>
                            [
                                "autoSkip" => false
                            ]
                        ]
                    ],
                ]
            ]
        ];

        $chartjs['options'] = !empty($this->config['info']['options']) ? Hash::merge($chartjs['options'], $this->config['info']['options']) : $chartjs['options'];

        $chartData = [
            'chart' => $this->type,
            'id' => $this->getContainerId(),
            'options' => [
                'resize' => true,
                'hideHover' => true,
                'dataChart' => $chartjs,
            ],
        ];

        if (!empty($data)) {
            $this->setData($chartData);
        }

        return $chartData;
    }

    /**
     * prepareChartOptions method
     *
     * Specifies JS/CSS libs for the content loading
     *
     * @param mixed[] $data passed from the widgetHandler.
     * @return mixed[] $content with the libs.
     */
    public function getScripts(array $data = []): array
    {
        return [
            'post' => [
                'javascript' => [
                    'type' => 'script',
                    'content' => [
                        'https://cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js',
                        'Search./plugins/Chart.min.js',
                    ],
                    'block' => 'scriptBottom',
                ],
            ]
        ];
    }
}
