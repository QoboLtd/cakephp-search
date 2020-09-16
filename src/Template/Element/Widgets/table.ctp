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
$savedSearch = $widget->getData();
$widgetOptions = $widget->getOptions();

if (empty($savedSearch)) {
    return '';
}
?>
<p>The rendering part of this widget needs to be implemented on the application level, by copying this template in: src/Template/Plugin/Search/Element/Widgets/table.ctp</p>
