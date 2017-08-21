<?php
namespace Search\Widgets\Reports;

use Cake\Utility\Inflector;
use Search\Widgets\Reports\BaseReportGraphs;

class DonutChartReportWidget extends BaseReportGraphs
{
    public $type = 'donutChart';

    public $requiredFields = ['query', 'label', 'value', 'columns'];

    /**
     * getChartData method
     *
     * Specifies chart data/config of the DonutChart.
     *
     * @param array $data containing configs.
     * @return array $chartData for graph rendering.
     */
    public function getChartData(array $data = [])
    {
        $report = $this->config;

        $chartData = [
            'chart' => $this->type,
            'options' => [
                'element' => $this->getContainerId(),
                'resize' => true,
            ],
        ];

        $options = [
            'data' => []
        ];

        foreach ($data as $item) {
            array_push($options['data'], [
                'label' => $item[$report['info']['label']],
                'value' => $item[$report['info']['value']],
            ]);
        }

        $chartData['options'] = array_merge($chartData['options'], $options);

        if (!empty($options['data'])) {
            $this->setData($chartData);
        }

        return $chartData;
    }

    /**
     * getScripts method
     *
     * Assembles JS/CSS libs for the graph rendering.
     *
     * @param array $data containing widgetHandler info.
     * @return array $content with the scripts.
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
