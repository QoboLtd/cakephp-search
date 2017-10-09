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
namespace Search\Widgets;

use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Search\Event\EventName;
use Search\Widgets\BaseWidget;

class ReportWidget extends BaseWidget
{
    public $renderElement = 'Search.Widgets/graph';
    public $options = [];

    /** @const WIDGET_REPORT_SUFFIX file naming suffix of widget files */
    const WIDGET_REPORT_SUFFIX = 'ReportWidget';

    /**
     * @return array $report configuration.
     */
    public function getConfig()
    {
        return $this->_instance->getConfig();
    }

    /**
     * @param array $data for extra setup
     * @return array $data of the report.
     */
    public function getChartData($data = [])
    {
        return $this->_instance->getChartData($data);
    }

    /**
     * @param array $data for extra settings
     * @return array $validated with errors and validation check.
     */
    public function validate(array $data = [])
    {
        return $this->_instance->validate($data);
    }

    /**
     * @return array $options of widget instance.
     */
    public function getOptions()
    {
        return $this->_instance->getOptions();
    }

    /**
     * @return string $type of the Report widget.
     */
    public function getType()
    {
        return $this->_instance->getType();
    }

    /**
     * Setting report configuration to the report instance.
     *
     * @param array $config to be set for config property.
     * @return array $config config of the widget.
     */
    public function setConfig($config)
    {
        return $this->_instance->setConfig($config);
    }

    /**
     * Method retrieves all reports from ini files
     *
     * Basic reports getter that uses Events
     * to get reports application-wise.
     *
     * @param array $options containing \Cake\View\View.
     * @return array $result with reports array.
     */
    public function getReports($options = [])
    {
        $result = [];

        if (empty($options['rootView'])) {
            return $result;
        }

        $event = new Event((string)EventName::MODEL_DASHBOARDS_GET_REPORTS(), $options['rootView']->request);
        $options['rootView']->EventManager()->dispatch($event);

        $result = $event->result;

        return $result;
    }

    /**
     * Parses the config of the report for widgetHandler
     *
     * @param array $options with entity data.
     * @return array $config of the widget.
     */
    public function getReport($options = [])
    {
        $config = [];

        if (empty($options['entity'])) {
            return $config;
        }

        if (empty($options['reports'])) {
            $options['reports'] = $this->getReports($options);
        }

        $widgetId = $options['entity']->widget_id;

        if (empty($options['reports'])) {
            return $config;
        }

        foreach ($options['reports'] as $modelName => $reports) {
            foreach ($reports as $slug => $reportInfo) {
                if ($reportInfo['id'] == $widgetId) {
                    $config = [
                        'modelName' => $modelName,
                        'slug' => $slug,
                        'info' => $reportInfo
                    ];
                }
            }
        }

        return $config;
    }

    /**
     * Initialize Report instance
     *
     * ReportWidgetHandler operates via $_instance variable
     * that we set based on the renderAs parameter of the report.
     *
     * @param array $options containing reports
     * @return mixed $className of the $_instance.
     */
    public function getReportInstance($options = [])
    {
        $result = null;

        if (empty($options['config'])) {
            $options['config'] = $this->getReport($options);
        }

        if (empty($options['config'])) {
            return $result;
        }
        $renderAs = $options['config']['info']['renderAs'];

        if (!empty($renderAs)) {
            $handlerName = Inflector::camelize($renderAs);

            $className = __NAMESPACE__ . '\\Reports\\' . $handlerName . self::WIDGET_REPORT_SUFFIX;
            $interface = __NAMESPACE__ . '\\Reports\\' . 'ReportGraphsInterface';

            if (class_exists($className) && in_array($interface, class_implements($className))) {
                $result = new $className($options);
            }
        }

        return $result;
    }

    /**
     * Assembles results data for the report
     *
     * Establish report data for the widgetHandler.
     *
     * @param array $options with entity and view data.
     * @throws \RuntimeException
     * @return array $result containing $_data.
     */
    public function getResults(array $options = [])
    {
        $result = [];

        $this->_instance = $this->getReportInstance($options);
        $config = $this->getReport($options);

        if (empty($config)) {
            return $result;
        }

        $this->setConfig($config);
        $this->setContainerId($config);

        $validated = $this->validate($config);

        if (!$validated['status']) {
            $result = $validated;

            throw new \RuntimeException("Report validation failed");
        } else {
            $result = $this->getQueryData($config);

            if (!empty($result)) {
                $this->_instance->getChartData($result);
                $this->_instance->options['scripts'] = $this->_instance->getScripts();
            }
        }

        return $result;
    }

    /**
     * Retrieve Query data for the report
     *
     * Executes Query statement from the report.ini
     * to retrieve actual report resultSet.
     *
     * @param array $config of the report.ini
     * @return array $result containing required resultset fields.
     */
    public function getQueryData($config = [])
    {
        $result = [];

        if (empty($config)) {
            return $result;
        }

        $resultSet = ConnectionManager::get('default')
            ->execute($config['info']['query'])
            ->fetchAll('assoc');

        if (empty($resultSet)) {
            return $result;
        }

        $columns = explode(',', $config['info']['columns']);

        foreach ($resultSet as $item) {
            $row = [];
            foreach ($item as $column => $value) {
                if (in_array($column, $columns)) {
                    $row[$column] = $value;
                }
            }
            array_push($result, $row);
        }

        return $result;
    }

    /**
     * Wrapper of report widget data.
     *
     * @return array $data of the report widget instance.
     */
    public function getData()
    {
        return $this->_instance->getData();
    }

    /**
     * setData for the widget.
     *
     * @param array $data with information related
     * @return void.
     */
    public function setData($data = [])
    {
        return $this->_instance->setData($data);
    }

    /**
     * @return string $containerId of the widget instance.
     */
    public function getContainerId()
    {
        return $this->_instance->getContainerId();
    }

    /**
     * Setup widget container identifier
     *
     * Setting unique identifier of the Widget object.
     *
     * @param array $config of the widget.
     * @return string $containerId property of widget instance.
     */
    public function setContainerId($config = [])
    {
        return $this->_instance->setContainerId($config);
    }

    /**
     * @return array $errors in case validation failed
     */
    public function getErrors()
    {
        return $this->_instance->getErrors();
    }
}
