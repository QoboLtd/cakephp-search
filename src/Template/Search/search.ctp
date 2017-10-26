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
    <h4>Search</h4>
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
    'associationLabels' => $associationLabels
]);
?>
</section>
