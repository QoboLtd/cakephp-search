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
    public function getChartData(array $data = []) : array
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
     * @param mixed[] $data passed from the widgetHandler.
     * @return mixed[] $content with the libs.
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
                        'AdminLTE./bower_components/jquery-knob/dist/jquery.knob.min',
                    ],
                    'block' => 'scriptBottom',
                ],
            ]
        ];
    }
}
