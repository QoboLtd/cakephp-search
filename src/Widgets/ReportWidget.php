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
use Cake\Event\EventManager;
use Cake\Utility\Inflector;
use RuntimeException;
use Search\Event\EventName;
use Search\Widgets\Reports\ReportGraphsInterface;

class ReportWidget extends BaseWidget
{
    public $renderElement = 'Search.Widgets/graph';

    /** @const WIDGET_REPORT_SUFFIX file naming suffix of widget files */
    const WIDGET_REPORT_SUFFIX = 'ReportWidget';

    /**
     * {@inheritDoc}
     */
    protected $title = 'Report';

    /**
     * {@inheritDoc}
     */
    protected $icon = 'area-chart';

    /**
     * {@inheritDoc}
     */
    protected $color = 'primary';

    protected $_instance;

    /**
     * @return mixed[] $report configuration.
     */
    public function getConfig() : array
    {
        return $this->_instance->getConfig();
    }

    /**
     * @param mixed[] $data for extra setup
     * @return mixed[] $data of the report.
     */
    public function getChartData(array $data = []) : array
    {
        return $this->_instance->getChartData($data);
    }

    /**
     * @param mixed[] $data for extra settings
     * @return mixed[] $validated with errors and validation check.
     */
    public function validate(array $data = []) : array
    {
        return $this->_instance->validate($data);
    }

    /**
     * @return mixed[] $options of widget instance.
     */
    public function getOptions() : array
    {
        return $this->_instance->getOptions();
    }

    /**
     * @return string $type of the Report widget.
     */
    public function getType() : string
    {
        return $this->_instance->getType();
    }

    /**
     * Setting report configuration to the report instance.
     *
     * @param mixed[] $config to be set for config property.
     * @return void
     */
    public function setConfig(array $config) : void
    {
        $this->_instance->setConfig($config);
    }

    /**
     * Setting report options to the report instance.
     *
     * @param mixed[] $options to be set for config property.
     * @return void
     */
    public function setOptions(array $options) : void
    {
        $this->_instance->setOptions($options);
    }

    /**
     * Method retrieves all reports from ini files
     *
     * Basic reports getter that uses Events
     * to get reports application-wise.
     *
     * @return mixed[] $result with reports array.
     */
    public function getReports() : array
    {
        $event = new Event((string)EventName::MODEL_DASHBOARDS_GET_REPORTS());
        EventManager::instance()->dispatch($event);

        return (array)$event->getResult();
    }

    /**
     * Parses the config of the report for widgetHandler
     *
     * @param mixed[] $options with entity data.
     * @return mixed[]
     */
    public function getReport(array $options = []) : array
    {
        if (empty($options['entity'])) {
            return [];
        }

        if (empty($options['reports'])) {
            $options['reports'] = $this->getReports();
        }

        $widgetId = $options['entity']->widget_id;

        if (empty($options['reports'])) {
            return [];
        }

        $result = [];
        foreach ($options['reports'] as $modelName => $reports) {
            foreach ($reports as $slug => $reportInfo) {
                if ($reportInfo['id'] !== $widgetId) {
                    continue;
                }

                $result = ['modelName' => $modelName, 'slug' => $slug, 'info' => $reportInfo];
            }
        }

        return $result;
    }

    /**
     * Initialize Report instance
     *
     * ReportWidgetHandler operates via $_instance variable
     * that we set based on the renderAs parameter of the report.
     *
     * @param mixed[] $options containing reports
     * @return \Search\Widgets\Reports\ReportGraphsInterface|null
     */
    public function getReportInstance(array $options = []) : ?ReportGraphsInterface
    {
        if (empty($options['config'])) {
            $options['config'] = $this->getReport($options);
        }

        if (empty($options['config'])) {
            return null;
        }

        $renderAs = $options['config']['info']['renderAs'];
        if (empty($renderAs)) {
            return null;
        }

        $className = __NAMESPACE__ . '\\Reports\\' . Inflector::camelize($renderAs) . self::WIDGET_REPORT_SUFFIX;
        if (! class_exists($className)) {
            return null;
        }

        if (! in_array(__NAMESPACE__ . '\\Reports\\' . 'ReportGraphsInterface', class_implements($className))) {
            return null;
        }

        return new $className($options);
    }

    /**
     * Assembles results data for the report
     *
     * Establish report data for the widgetHandler.
     *
     * @param mixed[] $options with entity and view data.
     * @throws \RuntimeException
     * @return mixed[] $result containing $_data.
     */
    public function getResults(array $options = []) : array
    {
        $this->_instance = $this->getReportInstance($options);
        if (null === $this->_instance) {
            return [];
        }

        $config = $this->getReport($options);
        if (empty($config)) {
            return [];
        }

        $this->setConfig($config);
        $this->setContainerId($config);

        $validated = $this->validate($config);

        if (! $validated['status']) {
            throw new RuntimeException("Report validation failed");
        }

        $result = $this->getQueryData($config);

        if (!empty($result)) {
            $this->_instance->getChartData($result);
            $this->setOptions(['scripts' => $this->_instance->getScripts()]);
        }

        return $result;
    }

    /**
     * Retrieve Query data for the report
     *
     * Executes Query statement from the report.ini
     * to retrieve actual report resultSet.
     *
     * @param mixed[] $config of the report.ini
     * @return mixed[] $result containing required resultset fields.
     */
    public function getQueryData(array $config = []) : array
    {
        if (empty($config)) {
            return [];
        }

        $resultSet = [];

        if (!empty($config['info']['finders'])) {
            $table = $config['info']['model'];

            $finder = $config['info']['finders']['name'];
            $options = !empty($config['info']['finders']['options']) ? $config['info']['finders']['options'] : [];

            $resultSet = TableRegistry::get($table)->find($finder, $options);
        }

        if (empty($config['info']['finders']) && !empty($config['info']['query'])) {
            $resultSet = ConnectionManager::get('default')
                ->execute($config['info']['query'])
                ->fetchAll('assoc');
        }

        $columns = explode(',', $config['info']['columns']);

        $result = [];
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
     * @return mixed[] $data of the report widget instance.
     */
    public function getData() : array
    {
        return $this->_instance->getData();
    }

    /**
     * setData for the widget.
     *
     * @param mixed[] $data with information related
     * @return void
     */
    public function setData(array $data = []) : void
    {
        $this->_instance->setData($data);
    }

    /**
     * @return string $containerId of the widget instance.
     */
    public function getContainerId() : string
    {
        return $this->_instance->getContainerId();
    }

    /**
     * Setup widget container identifier
     *
     * Setting unique identifier of the Widget object.
     *
     * @param mixed[] $config of the widget.
     * @return void
     */
    public function setContainerId(array $config = []) : void
    {
        $this->_instance->setContainerId($config);
    }

    /**
     * @return string[] $errors in case validation failed
     */
    public function getErrors() : array
    {
        return $this->_instance->getErrors();
    }
}
