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
     * setOptions
     *
     * Setting report options
     *
     * @param mixed[] $data of report.
     * @return void
     */
    public function setOptions(array $data = []) : void
    {
        $this->options = $data;
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

    /**
     * Generate an array whit the colors for the chars.
     *
     * @param  int     $count    How many element in the array.
     * @param  string  $myString The color have to be calculated on a
     *                           fix string to be constant in every refresh.
     * @param  string  $color    Define a fix color.
     * @param  bool    $shade    Shade the default color with another
     *                           generated one. If false, all the element will have the some color.
     * @return string[]
     */
    public function getChartColors(int $count, string $myString, string $color = "", bool $shade = true) : array
    {
        $grad = [];
        // Generate first color
        $color = empty($color && !preg_match('/^[a-f0-9]{6}$/i', $color)) ? substr(dechex(crc32($myString)), 0, 6) : $color;
        if ($shade) {
            list($r, $g, $b) = array_map(function ($n) {
                return hexdec($n);
            }, str_split($color, 2));

            // Generate second color
            $color2 = substr(dechex(crc32($myString . ' ')), 0, 6);
            list($r2, $g2, $b2) = array_map(function ($n) {
                return hexdec($n);
            }, str_split($color2, 2));

            $rl = ( $r2 - $r) / $count - 1;
            $gl = ( $g2 - $g) / $count - 1;
            $bl = ( $b2 - $b) / $count - 1;

            for ($i = 0; $i < $count; $i++) {
                $grad[] = '#' . str_pad(dechex($r + $rl * $i), 2, "0", 0) . str_pad(dechex($g + $gl * $i), 2, "0", 0) . str_pad(dechex($b + $bl * $i), 2, "0", 0);
            }

            return $grad;
        }

        for ($i = 0; $i < $count; $i++) {
            $grad[] = '#' . $color;
        }

        return $grad;
    }
}
