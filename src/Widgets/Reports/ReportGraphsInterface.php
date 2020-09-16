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
namespace Qobo\Search\Widgets\Reports;

interface ReportGraphsInterface
{
    /**
     * getChartData method.
     * Retrieves required data to draw
     * the graph from the JS side.
     *
     * @param mixed[] $data with extra settings.
     * @return mixed[] $chartData with all required info.
     */
    public function getChartData(array $data = []): array;

    /**
     * validate method.
     * Validates report configuration
     * to make sure all required fields are set
     * @param mixed[] $data with report config
     * @return mixed[] $validated on the check.
     */
    public function validate(array $data = []): array;

    /**
     * getScripts method
     *
     * Specifies required JS/CSS libs for given chart
     *
     * @param mixed[] $data passed in the method.
     * @return mixed[] JS/CSS libs paths.
     */
    public function getScripts(array $data = []): array;
}
