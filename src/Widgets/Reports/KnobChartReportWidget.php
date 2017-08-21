<?php
namespace Search\Widgets\Reports;

use Search\Widgets\Reports\BaseReportGraphs;

class KnobChartReportWidget extends BaseReportGraphs
{
    public $type = 'knobChart';

    public $requiredFields = ['query', 'max', 'value', 'label', 'columns'];

    /**
     * getChartData method
     *
     * Assembles graphs data from the reports config and data.
     *
     * @param array $data containing report configs and data.
     * @return array $chartData with defined chart information.
     */
    public function getChartData(array $data = [])
    {
        $report = $this->config;

        $chartData = [
            'chart' => $this->type,
            'options' => [
                'element' => $this->getContainerId(),
                'resize' => true,
                'data' => [],
            ],
        ];

        $options['data'] = [];

        if (isset($report['info']['max']) && isset($report['info']['value'])) {
            foreach ($data as $item) {
                array_push($options['data'], [
                    'max' => $item[$report['info']['max']],
                    'value' => $item[$report['info']['value']],
                    'label' => $item[$report['info']['label']],
                ]);
            }
        }

        $chartData['options'] = array_merge($chartData['options'], $options);

        if (!empty($options['data'])) {
            $this->setData($chartData);
        }

        return $chartData;
    }

    /**
     * prepareChartOptions method
     *
     * Specifies JS/CSS libs for the content loading
     *
     * @param array $data passed from the widgetHandler.
     * @return array $content with the libs.
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
                        'AdminLTE./plugins/knob/jquery.knob',
                    ],
                    'block' => 'scriptBottom',
                ],
            ]
        ];
    }
}
