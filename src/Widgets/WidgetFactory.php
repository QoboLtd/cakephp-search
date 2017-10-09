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

use Cake\Utility\Inflector;

class WidgetFactory
{
    const WIDGET_SUFFIX = 'Widget';
    const WIDGET_INTERFACE = 'WidgetInterface';
    const APP_NAMESPACE = 'App\\Widget';

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

        $interface = __NAMESPACE__ . '\\' . self::WIDGET_INTERFACE;

        $namespaces = [static::APP_NAMESPACE, __NAMESPACE__];
        foreach ($namespaces as $namespace) {
            $className = $namespace . '\\' . $handlerName . self::WIDGET_SUFFIX;
            if (!class_exists($className)) {
                $className = null;
                continue;
            }

            break;
        }

        if (!$className) {
            throw new \RuntimeException("Class [$type] doesn't exist");
        }

        if (!in_array($interface, class_implements($className))) {
            throw new \RuntimeException("Class [$type] doesn't implement " . self::WIDGET_INTERFACE);
        }

        $widget = new $className($options);

        return $widget;
    }
}
