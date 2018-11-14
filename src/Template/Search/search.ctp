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
?>
<section class="content-header">
    <div class="row">
        <div class="col-xs-12 col-md-6">
            <h4><?= __('Search') ?></h4>
        </div>
        <div class="col-xs-12 col-md-6">
            <div class="pull-right">
            <div class="btn-group btn-group-sm" role="group">
                <?= $this->element('Search.Menu/search-view-top'); ?>
            </div>
            </div>
        </div>
    </div>
</section>
<section class="content">
<?php
echo $this->element('Search.Search/filters', [
    'searchOptions' => $searchOptions,
    'searchableFields' => $searchableFields,
    'savedSearch' => $savedSearch,
    'searchData' => $searchData,
    'isEditable' => $isEditable,
    'preSaveId' => $preSaveId,
    'associationLabels' => $associationLabels

]);
echo $this->element('Search.Search/results', [
    'searchableFields' => $searchableFields,
    'savedSearch' => $savedSearch,
    'searchData' => $searchData,
    'preSaveId' => $preSaveId,
    'associationLabels' => $associationLabels
]);
?>
</section>
