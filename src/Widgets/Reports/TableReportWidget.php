<?php
namespace Search\Widgets\Reports;

use Cake\Log\LogTrait;
use Cake\Utility\Inflector;
use Search\Widgets\Reports\BaseReportGraphs;

class TableReportWidget extends BaseReportGraphs
{
    use LogTrait;

    public $type = 'table';

    public $requiredFields = ['query'];

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
        $labels = [];
        $report = $this->config;

        $chartData = [
            'chart' => $this->type,
            'options' => [
                'element' => $this->getContainerId(),
                'resize' => true,
            ],
        ];

        $options = [
            'data' => $data,
        ];

        $chartData['options'] = array_merge($chartData['options'], $options);

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
                        'AdminLTE./plugins/datatables/dataTables.bootstrap',
                        'Qobo/Utils.buttons.dataTables.min',
                        'Search.grid'
                    ],
                    'block' => 'css',
                ],
                'javascript' => [
                    'type' => 'script',
                    'content' => [
                        'AdminLTE./plugins/datatables/jquery.dataTables.min',
                        'AdminLTE./plugins/datatables/dataTables.bootstrap.min',
                        'Qobo/Utils.dataTables.buttons.min',
                        'Qobo/Utils.buttons.flash.min',
                        'Qobo/Utils.buttons.print.min',
                        'Qobo/Utils.pdfmake.min',
                        'Qobo/Utils.buttons.html5.min',
                        'Qobo/Utils.vfs_fonts',
                        'Qobo/Utils.jszip.min',
                    ],
                    'block' => 'scriptBotton',
                ],
            ]
        ];
    }
}
