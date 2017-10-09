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

interface ReportGraphsInterface
{
    /**
     * getChartData method.
     * Retrieves required data to draw
     * the graph from the JS side.
     *
     * @param array $data with extra settings.
     * @return array $chartData with all required info.
     */
    public function getChartData(array $data = []);

    /**
     * validate method.
     * Validates report configuration
     * to make sure all required fields are set
     * @param array $data with report config
     * @return bool $validated on the check.
     */
    public function validate(array $data = []);
}
