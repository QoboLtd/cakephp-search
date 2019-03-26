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
    public $type = 'barChart';

    public $requiredFields = ['query', 'columns', 'x_axis', 'y_axis'];

    /**
     * getChartData method
     *
     * Assembles graphs data from the reports config and data.
     *
     * @param array $data containing report configs and data.
     * @return array $chartData with defined chart information.
     */
    public function getChartData(array $data = []) : array
    {
        $report = $this->config;

        // We suppose that in the x_axis are the values with labels
        $label_column_name = $report['info']['x_axis'];
        $label = Hash::extract($data, '{n}.' . $label_column_name);

        $columns = explode(',', $report['info']['columns']);

        // Check if is a multiple set of data.
        $datasets = [];
        $num_col = count($columns);
        for ($i = 0; $i < $num_col - 1; $i++) {
            // If the chart is multiple bar or stackbar is better to not have shaded colors.
            $colors = $this->getChartColors(count($data), $this->getContainerId() . $i, $num_col > 2 ? false : true);
            $datasets[] = [
                "label" => Inflector::humanize($columns[$i]),
                "backgroundColor" => $num_col > 2 ? $colors[0] : $colors,
                "data" => (array)Hash::extract($data, '{n}.' . $columns[$i])
            ];
        }

        $newChart = [
            "type" => "bar",
            "data" =>
            [
                "labels" => $label,
                "datasets" => $datasets
            ],
            "options" =>
            [
                "legend" => [
                    "display" => true,
                ],
                "scales" =>
                [
                    "yAxes" => [[
                        "stacked" => $num_col > 2 ? true : false,
                        "ticks" =>
                        [
                            "beginAtZero" => true
                        ]
                    ]],
                    "xAxes" => [[
                        "stacked" => $num_col > 2 ? true : false
                    ]]
                ]
            ]
        ];

        $chartData = [
            'chart' => $this->type,
            'id' => $this->getContainerId(),
            'options' => [
                'resize' => true,
                'hideHover' => true,
                'dataChart' => $newChart,
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
    public function getScripts(array $data = []) : array
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
