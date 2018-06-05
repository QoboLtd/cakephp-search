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

use Cake\Core\Configure;

$args = [
    [
        'entity' => $savedSearch,
        'searchData' => $searchData,
        'searchableFields' => $searchableFields,
        'associationLabels' => $associationLabels,
        'batch' => (bool)Configure::read('Search.batch.active'),
        'preSaveId' => $preSaveId,
        'action' => 'search'
    ],
    $this
];

$cell = $this->cell('Search.Result', $args);

echo $cell;
