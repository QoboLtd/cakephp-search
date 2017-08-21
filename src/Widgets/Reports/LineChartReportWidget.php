<?php
namespace Search\Widgets\Reports;

use Cake\Utility\Inflector;
use Search\Widgets\Reports\BaseReportGraphs;

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
    public function getChartData(array $data = [])
    {
        $labels = [];
        $report = $this->config;

        $chartData = [
            'chart' => $this->type,
            'options' => [
                'element' => $this->getContainerId(),
                'resize' => true,
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
     * @param array $data passed in the method.
     * @return array $content with JS/CSS libs.
     */
    public function getScripts(array $data = [])
    {
        return [
            'post' => [
                'css' => [
                    'type' => 'css',
                    'content' => [
                        'AdminLTE./plugins/morris/morris',
                    ],
                    'block' => 'css',
                ],
                'javascript' => [
                    'type' => 'script',
                    'content' => [
                        'https://cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js',
                        'AdminLTE./plugins/morris/morris.min',
                    ],
                    'block' => 'scriptBottom',
                ],
            ]
        ];
    }
}
