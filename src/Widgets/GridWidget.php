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

class GridWidget extends ReportWidget
{
    public $renderElement = 'Search.Widgets/grid';

    /**
     * {@inheritDoc}
     */
    protected $title = 'Grid';

    /**
     * {@inheritDoc}
     */
    protected $icon = 'th';

    /**
     * {@inheritDoc}
     */
    protected $color = 'primary';
}
