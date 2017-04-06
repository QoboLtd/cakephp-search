<?php
namespace Search\Widgets;

use Cake\Utility\Inflector;

class WidgetFactory
{
    const WIDGET_SUFFIX = 'Widget';
    const WIDGET_INTERFACE = 'WidgetInterface';

    /**
     * create method
     *
     * Factory method to initialize widget handler instance
     * base on the widget type field.
     *
     * @param string $type containing the widget handler type.
     * @param array $options containing entity and view data.
     * @return mixed $className of the widgetHandler.
     */
    public static function create($type, array $options = [])
    {
        $widget = null;
        $handlerName = Inflector::camelize($type);

        $className = __NAMESPACE__ . '\\' . $handlerName . self::WIDGET_SUFFIX;
        $interface = __NAMESPACE__ . '\\' . self::WIDGET_INTERFACE;
        if (!class_exists($className)) {
            throw new \RuntimeException("Class [$type] doesn't exist");
        }

        if (!in_array($interface, class_implements($className))) {
            throw new \RuntimeException("Class [$type] doesn't implement " . self::WIDGET_INTERFACE);
        }

        $widget = new $className($options);

        return $widget;
    }

    /**
     *  getChartReportTypes() method
     *
     * @return array    list of available chart reports
     */
    public static function getChartReportTypes()
    {
        $result = [];
        $dh = opendir(__DIR__ . '/Reports');
        if ($dh) {
            while (($file = readdir($dh)) !== false) {
                if (preg_match('/(\w*)ReportWidget/', $file, $match)) {
                    $chartType = lcfirst($match[1]);
                    $result[$chartType] = $chartType;
                }
            }
        }

        return $result;
    }
}
