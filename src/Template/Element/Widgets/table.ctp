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

$args = [
    [
        'entity' => $savedSearch,
        'searchData' => $savedSearch->get('content')['saved'],
        'searchableFields' => $widgetOptions['fields'],
        'associationLabels' => $widgetOptions['associationLabels'],
        'preSaveId' => $savedSearch->get('id')
    ],
    $this
];

$cell = $this->cell('Search.Result', $args);
$cell->template = 'widget';

echo $cell;
