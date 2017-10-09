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

use Search\Widgets\Reports\ReportGraphsInterface;

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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array $_config of the reports.
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return array $options
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Get Chart Colors for the graph
     *
     * @throws RuntimeException Color doesn't match HEX notation.
     * @return array $result with colors in hex.
     */
    public function getChartColors()
    {
        $valid = true;
        $result = $this->chartColors;

        if (empty($this->config) || !isset($this->config['info']['colors'])) {
            return $result;
        }

        $colors = array_filter(explode(',', $this->config['info']['colors']));
        if (empty($colors)) {
            return $result;
        }

        foreach ($colors as $color) {
            if (!preg_match('/^#[a-f0-9]{6}$/i', $color)) {
                $valid = false;
                throw new \RuntimeException("Color {$color} doesn't match HEX notation");
            }
        }

        if ($valid) {
            $result = $colors;
        }

        return $result;
    }

    /**
     * setContainerId method.
     * Sets the placeholder unique identifier for
     * the widget.
     * @param array $data of the config.
     * @return string $containerId of the object.
     */
    public function setContainerId($data = [])
    {
        $config = empty($data) ? $this->getConfig() : $data;

        $this->containerId = self::GRAPH_PREFIX . $config['slug'];

        return $this->containerId;
    }

    /**
     * @return string $containerId property of the widget.
     */
    public function getContainerId()
    {
        return $this->containerId;
    }

    /**
     * setConfig.
     *
     * Setting report configurations
     *
     * @param array $data of report.
     * @return void
     */
    public function setConfig($data = [])
    {
        $this->config = $data;
    }

    /**
     * setData method.
     *
     * @param array $data for the report widget.
     * @return void
     */
    public function setData($data = [])
    {
        $this->data = $data;
    }

    /**
     * @return array $data of the widget.
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return array $errors in case any exists.
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * validate method.
     *
     * Checks all the required fields of the report if any.
     *
     * @param array $data with report configuration
     * @return mixed result of validation
     */
    public function validate(array $data = [])
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
}
