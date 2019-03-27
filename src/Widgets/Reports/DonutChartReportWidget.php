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

class DonutChartReportWidget extends BaseReportGraphs
{
    public $type = 'donutChart';

    public $requiredFields = ['query', 'columns'];

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
        $label = Hash::extract($data, '{n}.' . $columns[1]);
        $data = (array)Hash::extract($data, '{n}.' . $columns[0]);

        $colors = $this->getChartColors(count($data), $this->getContainerId(), false);

        $newChart = [
            "type" => "doughnut",
            "data" =>
            [
                "labels" => $label,
                "datasets" => [[
                                    "backgroundColor" => $colors,
                                    "borderColor" => '#c0c0c0',
                                    "data" => $data
                                ]]
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
