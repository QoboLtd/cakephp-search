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
     * Widget type
     * @var string
     */
    public $type = '';

    /**
     * Widget renderable element
     *
     * @var string
     */
    public $renderElement = '';

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
     * Widget options.
     *
     * @var mixed[]
     */
    protected $options = [];

    /**
     * getType method
     *
     * Widget $type specifies the type of handler
     * we're dealing with - whether it's a barChart/SavedSearch.
     *
     * @return string $type of the WidgetHandler.
     */
    public function getType(): string
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
    public function getRenderElement(): string
    {
        return $this->renderElement;
    }

    /**
     * getContainerId method.
     *
     * @return string $containerId of the widget.
     */
    public function getContainerId(): string
    {
        return $this->containerId;
    }

    /**
     * {@inheritDoc}
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * {@inheritDoc}
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * {@inheritDoc}
     */
    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
