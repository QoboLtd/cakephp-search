<?php
namespace Search\Widgets;

use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Search\Widgets\BaseWidget;

class ReportWidget extends BaseWidget
{
    public $renderElement = 'graph';

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
     * @return array $data of the report.
     */
    public function getData()
    {
        return $this->_instance->_data;
    }

    /**
     * @return array $_dataOptions of the widget for rendering.
     */
    public function getDataOptions()
    {
        return $this->_instance->_dataOptions;
    }

    /**
     * @return string $type of the Report widget.
     */
    public function getType()
    {
        return $this->_instance->_type;
    }

    /**
     * getScripts method.
     *
     * @param array $options with data.
     * @return array $_dataOptions.
     */
    public function getScripts(array $options = [])
    {
        return $this->_instance->getScripts(['data' => $options]);
    }

    /**
     * @return array $chartData of the instance.
     */
    public function getChartData(array $data = [])
    {
        return $this->_instance->getChartData($data);
    }

    /**
     * Setting report configuration to the report instance.
     *
     * @param array $report to be set for _config property.
     * @retrun array $report config of the widget.
     */
    public function setConfig($config)
    {
        $this->_instance->setConfig($config);
    }

    /**
     * setData method
     * @param array $data containing widget data.
     * @return array $_data after being set.
     */
    public function setData($data = [])
    {
        $this->_instance->_data = $data;

        return $this->_instance->_data;
    }

    /**
     * setDataOptions method
     * Setting up report JS/CSS libs.
     * @param array $data for being set.
     * @return array $_dataOptions property.
     */
    public function setDataOptions($data = [])
    {
        $this->_instance->_dataOptions = $data;

        return $this->_instance->_dataOptions;
    }

    /**
     * getReportConfig method
     * Parses the config of the report for widgetHandler.
     * @param array $options with entity data.
     * @return array $config of the $_config.
     */
    public function getReportConfig($options = [])
    {
        $config = [];

        if (empty($options['rootView'])) {
            return $config;
        }
        $rootView = $options['rootView'];
        $event = new Event('Search.Report.getReports', $rootView->request);
        $rootView->EventManager()->dispatch($event);

        $widgetId = $options['entity']->widget_id;

        if (!empty($event->result)) {
            foreach ($event->result as $modelName => $reports) {
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
        }

        return $config;
    }

    /**
     * ReportWidgetHandler operates via $_instance variable
     * that we set based on the renderAs parameter of the report.
     * @param array $options containing reports
     * @return mixed $className of the $_instance.
     */
    public function getReportInstance($options = [])
    {
        $result = null;

        $options['report'] = $this->getReportConfig($options);

        if (empty($options['report'])) {
            return $result;
        }
        $renderAs = $options['report']['info']['renderAs'];

        if (!empty($renderAs)) {
            $handlerName = Inflector::camelize($renderAs);

            $className = __NAMESPACE__ . '\\Reports\\' . $handlerName . self::WIDGET_REPORT_SUFFIX;
            $interface = __NAMESPACE__ . '\\Reports\\' . 'ReportGraphsInterface';

            if (class_exists($className) && in_array($interface, class_implements($className))) {
                return new $className($options);
            }
        }
    }

    /**
     * getResults method
     *
     * Establish report data for the widgetHandler.
     *
     * @param array $options with entity and view data.
     * @return array $result containing $_data.
     */
    public function getResults(array $options = [])
    {
        $result = [];
        $this->_instance = $this->getReportInstance($options);

        $config = $this->getReportConfig($options);

        $this->containerId = $this->setContainerId($config);

        $this->setConfig($config);

        $columns = explode(',', $config['info']['columns']);

        $dbh = ConnectionManager::get('default');
        $sth = $dbh->execute($config['info']['query']);
        $resultSet = $sth->fetchAll('assoc');

        if (!empty($resultSet)) {
            foreach ($resultSet as $row) {
                $renderRow = [];
                foreach ($row as $column => $value) {
                    if (in_array($column, $columns)) {
                        $renderRow[$column] = $value;
                    }
                }
                array_push($result, $renderRow);
            }
        }

        if (!empty($result)) {
            $data = $this->getChartData($result);
            $dataOptions = $this->getScripts(['data' => $data]);

            $this->setData($data);
            $this->setDataOptions($dataOptions);
        }

        return $this->getData();
    }

    /**
     * @return string $containerId of the widget instance.
     */
    public function getContainerId()
    {
        return $this->_instance->getContainerId();
    }

    /**
     * setContainerId method.
     * Setting unique identifier of the Widget object.
     * @param array $config of the widget.
     * @return string $containerId property of widget instance.
     */
    public function setContainerId($config = [])
    {
        return $this->_instance->setContainerId($config);
    }
}