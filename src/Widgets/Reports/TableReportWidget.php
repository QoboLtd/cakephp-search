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
                        'https://cdn.datatables.net/buttons/1.3.1/css/buttons.dataTables.min.css'
                    ],
                    'block' => 'css',
                ],
                'javascript' => [
                    'type' => 'script',
                    'content' => [
                        'AdminLTE./plugins/datatables/jquery.dataTables.min',
                        'AdminLTE./plugins/datatables/dataTables.bootstrap.min',
                        'https://cdn.datatables.net/buttons/1.3.1/js/dataTables.buttons.min.js',
                        'https://cdn.datatables.net/buttons/1.3.1/js/buttons.flash.min.js',
                        'https://cdn.datatables.net/buttons/1.3.1/js/buttons.print.min.js',
                        'https://cdn.rawgit.com/bpampuch/pdfmake/0.1.27/build/pdfmake.min.js',
                        'https://cdn.datatables.net/buttons/1.3.1/js/buttons.html5.min.js',
                        'https://cdn.rawgit.com/bpampuch/pdfmake/0.1.27/build/vfs_fonts.js',
                        'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js',
                    ],
                    'block' => 'scriptBotton',
                ],
            ]
        ];
    }
}
