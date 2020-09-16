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

use Cake\Core\Configure;
use Cake\Utility\Hash;
use Qobo\Utils\ModuleConfig\ConfigType;
use Qobo\Utils\ModuleConfig\ModuleConfig;

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
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return mixed[] $_config of the reports.
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return mixed[] $options
     */
    public function getOptions(): array
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
    public function setContainerId(array $data = []): void
    {
        $config = empty($data) ? $this->getConfig() : $data;

        $this->containerId = self::GRAPH_PREFIX . $config['slug'];
    }

    /**
     * @return string $containerId property of the widget.
     */
    public function getContainerId(): string
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
    public function setConfig(array $data = []): void
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
    public function setOptions(array $data = []): void
    {
        $this->options = $data;
    }

    /**
     * setData method.
     *
     * @param mixed[] $data for the report widget.
     * @return void
     */
    public function setData(array $data = []): void
    {
        $this->data = $data;
    }

    /**
     * @return mixed[] $data of the widget.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return string[] $errors in case any exists.
     */
    public function getErrors(): array
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
    public function validate(array $data = []): array
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
    public function getScripts(array $data = []): array
    {
        return [];
    }

    /**
     * Generate an array whit the colors for the chars.
     *
     * @param  int     $count    How many element in the array.
     * @param  string  $myString The color have to be calculated on a
     *                           fix string to be constant in every refresh.
     * @param  bool    $shade    Shade the default color with another
     *                           generated one. If false, the colors will be from a pre-set palette.
     * @return string[]
     */
    public function getChartColors(int $count, string $myString, bool $shade = true): array
    {
        $grad = [];

        if ($shade && $count > 0) {
            $my_palette = Configure::read("Widget.gradation");
            $my_colors = crc32($myString) % count($my_palette);

            // First color
            $color1 = empty($my_palette[$my_colors]) ? substr(dechex(crc32($myString)), 0, 6) : $my_palette[$my_colors][0];
            // Second color
            $color2 = empty($my_palette[$my_colors]) ? substr(dechex(crc32($myString . ' ')), 0, 6) : $my_palette[$my_colors][1];

            list($r, $g, $b) = array_map(function ($n) {
                return hexdec($n);
            }, str_split($color1, 2));

            list($r2, $g2, $b2) = array_map(function ($n) {
                return hexdec($n);
            }, str_split($color2, 2));

            $rl = ( $r2 - $r) / $count - 1;
            $gl = ( $g2 - $g) / $count - 1;
            $bl = ( $b2 - $b) / $count - 1;

            // Create a shade from the first color to the second
            for ($i = 0; $i < $count; $i++) {
                $result_r = str_pad(dechex($r + $rl * $i), 2, "0", 0);
                $result_g = str_pad(dechex($g + $gl * $i), 2, "0", 0);
                $result_b = str_pad(dechex($b + $bl * $i), 2, "0", 0);
                $grad[] = '#' . $result_r . $result_g . $result_b;
            }

            return $grad;
        }

        $my_palette = Configure::read("Widget.colors");

        for ($i = 0; $i < $count; $i++) {
            $grad[] = $my_palette[(crc32($myString) + $i) % count($my_palette)];
        }

        return $grad;
    }

    /**
     * Check if the field of the model is a list type.
     * In that case, will return the items of the list.
     *
     * @param  string $model Model name
     * @param  string $field Field name
     * @return mixed[] Items of the list
     */
    public function getList(string $model, string $field): array
    {
        $type = !empty((new ModuleConfig(ConfigType::MIGRATION(), $model))->parseToArray()[$field]['type']) ? (new ModuleConfig(ConfigType::MIGRATION(), $model))->parseToArray()[$field]['type'] : '';
        $is_list = preg_match("/^list\(([^\)]+)\)/", $type, $matchedList);
        if (!$is_list) {
            return [];
        }

        $list = $matchedList[1];
        if (strpos($list, '.') === false) {
            return (new ModuleConfig(ConfigType::LISTS(), $model, $list))->parseToArray()['items'];
        }

        list($model, $list) = explode('.', $list, 2);

        return (new ModuleConfig(ConfigType::LISTS(), $model, $list))->parseToArray()['items'];
    }

    /**
     * The results can be sorted by a custom list.
     *
     * @param  mixed[]  $results Data to sort.
     * @param  mixed[]  $list Items list from the model.
     * @param  string $label Which key of the elements of the $results is the pivot for the sort.
     * @return mixed[] Sorted data.
     */
    public function sortListByLabel(array $results, array $list, string $label): array
    {
        $data = [];
        // $index is use to make easier find of the position
        // of the element in the $results.
        $index = Hash::extract($results, '{n}.' . $label);
        $i = 0;
        // $list has the right order of the items.
        foreach ($list as $key => $value) {
            if ($value['inactive']) {
                continue;
            }
            $position = array_search($value['label'], (array)$index);

            if (is_numeric($position)) {
                $data[$i] = $results[$position];
                unset($results[$position]);
                $i++;
            }
        }

        // In case that are other items (IE. not in model list, inactive labels, etc.)
        // will be added in the end.
        foreach ($results as $key => $value) {
            $data[] = $value;
        }

        return $data;
    }
}
