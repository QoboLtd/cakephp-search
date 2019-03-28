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

class PieChartReportWidget extends DonutChartReportWidget
{
    public $type = 'pie';

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
        $chartData = parent::getChartData($data);

        $chartData["options"]["dataChart"]["type"] = $this->type;
        $this->setData($chartData);

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
         return parent::getScripts($data);
    }
}
