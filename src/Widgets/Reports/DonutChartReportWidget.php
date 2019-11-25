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

class DonutChartReportWidget extends BaseReportGraphs
{
    public $type = 'doughnut';

    public $requiredFields = ['query', 'columns', 'label'];

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

        $columns = explode(',', $report['info']['columns']);

        // Check which index colums is the label and the data
        $label_index = array_key_exists(0, $data) && is_numeric($data[0][$columns[0]]) ? $columns[1] : $columns[0];
        $data_index = array_key_exists(0, $data) && is_numeric($data[0][$columns[0]]) ? $columns[0] : $columns[1];

        $label = Hash::extract($data, '{n}.' . $label_index);
        $data = (array)Hash::extract($data, '{n}.' . $data_index);

        $colors = $this->getChartColors(count($data), $this->getContainerId(), false);

        $chartjs = [
            "type" => $this->type,
            "data" =>
            [
                "labels" => $label,
                "datasets" => [
                    [
                        "backgroundColor" => $colors,
                        "data" => $data
                    ]
                ]
            ]
        ];

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
