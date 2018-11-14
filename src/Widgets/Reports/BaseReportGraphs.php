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

use RuntimeException;

abstract class BaseReportGraphs implements ReportGraphsInterface
{
    const GRAPH_PREFIX = 'graph_';

    public $containerId = '';
    public $type = null;
    public $config = [];
    public $options = [];
    public $data = [];
    public $errors = [];
    public $requiredFields = [];

    public $chartColors = [
        '#0874c7',
        '#04645e',
        '#5661f8',
        '#8298c1',
        '#c6ba08',
        '#07ada3',
    ];
    /**
     * getType
     *
     * Returns Chart type
     * @return string $type of the report instance.
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @return mixed[] $_config of the reports.
     */
    public function getConfig() : array
    {
        return $this->config;
    }

    /**
     * @return mixed[] $options
     */
    public function getOptions() : array
    {
        return $this->options;
    }

    /**
     * Get Chart Colors for the graph
     *
     * @throws \RuntimeException Color doesn't match HEX notation.
     * @return mixed[] $result with colors in hex.
     */
    public function getChartColors() : array
    {
        if (! isset($this->config['info']['colors'])) {
            return $this->chartColors;
        }

        $colors = array_filter(explode(',', $this->config['info']['colors']));
        if (empty($colors)) {
            return $this->chartColors;
        }

        // validate provided colors
        foreach ($colors as $color) {
            if (! preg_match('/^#[a-f0-9]{6}$/i', $color)) {
                throw new RuntimeException("Color {$color} doesn't match HEX notation");
            }
        }

        return $colors;
    }

    /**
     * setContainerId method.
     * Sets the placeholder unique identifier for
     * the widget.
     * @param mixed[] $data of the config.
     * @return void
     */
    public function setContainerId(array $data = []) : void
    {
        $config = empty($data) ? $this->getConfig() : $data;

        $this->containerId = self::GRAPH_PREFIX . $config['slug'];
    }

    /**
     * @return string $containerId property of the widget.
     */
    public function getContainerId() : string
    {
        return $this->containerId;
    }

    /**
     * setConfig.
     *
     * Setting report configurations
     *
     * @param mixed[] $data of report.
     * @return void
     */
    public function setConfig(array $data = []) : void
    {
        $this->config = $data;
    }

    /**
     * setData method.
     *
     * @param mixed[] $data for the report widget.
     * @return void
     */
    public function setData(array $data = []) : void
    {
        $this->data = $data;
    }

    /**
     * @return mixed[] $data of the widget.
     */
    public function getData() : array
    {
        return $this->data;
    }

    /**
     * @return string[] $errors in case any exists.
     */
    public function getErrors() : array
    {
        return $this->errors;
    }

    /**
     * validate method.
     *
     * Checks all the required fields of the report if any.
     *
     * @param array $data with report configuration
     * @return mixed[] result of validation
     */
    public function validate(array $data = []) : array
    {
        $validated = false;
        $errors = [];

        if (empty($this->requiredFields)) {
            $errors[] = "Missing requiredFields in the report object";
        }

        foreach ($this->requiredFields as $field) {
            if (!isset($data['info'][$field])) {
                $errors[] = "Required field [$field] must be set";
            }

            if (empty($data['info'][$field])) {
                $errors[] = "Required Field [$field] cannot be empty";
            }
        }

        if (empty($errors)) {
            $validated = true;
            $this->errors = [];
        } else {
            $this->errors = $errors;
        }

        return ['status' => $validated, 'messages' => $errors];
    }

    /**
     * getScripts method
     *
     * Specifies required JS/CSS libs for given chart
     *
     * @param mixed[] $data passed in the method.
     * @return mixed[] JS/CSS libs paths.
     */
    public function getScripts(array $data = []) : array
    {
        return [];
    }
}
