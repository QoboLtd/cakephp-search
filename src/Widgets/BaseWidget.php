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

use Search\Widgets\WidgetInterface;

abstract class BaseWidget implements WidgetInterface
{
    const WIDGET_INTERFACE = 'WidgetInterface';

    const WIDGET_SUFFIX = 'Widget';

    public $containerId = 'default-widget-container';

    /**
     * Widget's title.
     *
     * @var string
     */
    protected $title = 'App';

    /**
     * Widget's icon.
     *
     * @var string
     */
    protected $icon = 'cube';

    /**
     * Widget's color.
     *
     * @var string
     */
    protected $color = 'warning';

    /**
     * getType method
     *
     * Widget $type specifies the type of handler
     * we're dealing with - whether it's a barChart/SavedSearch.
     *
     * @return string $type of the WidgetHandler.
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * getRenderElement
     *
     * renderElement variable is used to specify CakePHP
     * element that should be used for rendering the widget.
     *
     * @return string $renderElement name.
     */
    public function getRenderElement()
    {
        return $this->renderElement;
    }

    /**
     * getContainerId method.
     * @return string $containerId of the widget.
     */
    public function getContainerId()
    {
        return $this->containerId;
    }

    /**
     * {@inheritDoc}
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * {@inheritDoc}
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * {@inheritDoc}
     */
    public function getColor()
    {
        return $this->color;
    }
}
