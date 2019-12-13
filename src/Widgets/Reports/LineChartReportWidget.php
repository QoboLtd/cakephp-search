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

class LineChartReportWidget extends BaseReportGraphs
{
    public $type = 'line';

    public $requiredFields = ['query', 'x_axis', 'columns'];

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
        $label = Hash::extract($data, '{n}.' . $label_column_name);

        $columns = explode(',', $report['info']['columns']);
        $columns = array_diff($columns, [$label_column_name]);

        // Check if is a multiple set of data.
        $datasets = [];

        foreach ($columns as $key => $value) {
            $colors = $this->getChartColors(1, $this->getContainerId() . (string)$key, false);
            $datasets[] = [
                "label" => Inflector::humanize($value),
                "data" => (array)Hash::extract($data, '{n}.' . $value),
                "borderColor" => $colors[0],
                "fill" => false,
            ];
        }

        $chartjs = [
            "type" => $this->type,
            "data" =>
            [
                "labels" => $label,
                "datasets" => $datasets,
            ],
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
            ],
        ];
    }
}
