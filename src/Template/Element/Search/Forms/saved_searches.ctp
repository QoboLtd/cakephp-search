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

echo $this->Html->script('Search.saved_searches', ['block' => 'scriptBottom']);

$selectOptions = [];
foreach ($savedSearches as $savedSearch) {
    if ('criteria' !== $savedSearch->type) {
        continue;
    }
    // store select options
    $selectOptions[$savedSearch->id] = $savedSearch->name;

    echo $this->Html->link(
        $savedSearch->name,
        ['action' => 'search', $savedSearch->id],
        ['id' => 'view_' . $savedSearch->id, 'class' => 'hidden']
    );

    echo $this->Form->postLink(null, ['action' => 'copy-search', $savedSearch->id], [
        'id' => 'copy_' . $savedSearch->id,
        'title' => __('Copy'),
        'class' => 'saved-search-copy-form hidden',
        'escape' => false
    ]);

    echo $this->Form->postLink(null, ['action' => 'delete-search', $savedSearch->id], [
        'id' => 'delete_' . $savedSearch->id,
        'confirm' => __('Are you sure you want to delete {0}?', $savedSearch->name),
        'title' => __('Delete'),
        'class' => 'saved-search-delete-form hidden',
        'escape' => false
    ]);
} ?>
<div class="input-group">
    <?= $this->Form->select('criterias', $selectOptions, [
        'id' => 'savedCriteriasSelect',
        'default' => $this->request->param('pass.0'),
        'class' => 'form-control input-sm'
    ]) ?>
    <span class="input-group-btn">
    <?= $this->Form->button('<i class="fa fa-eye"></i>', [
        'type' => 'button',
        'id' => 'savedCriteriasView',
        'class' => 'btn btn-default btn-sm',
    ]) ?>
    <?= $this->Form->button('<i class="fa fa-clone"></i>', [
        'type' => 'button',
        'id' => 'savedCriteriasCopy',
        'class' => 'btn btn-default btn-sm'
    ]) ?>
    <?= $this->Form->button('<i class="fa fa-trash"></i>', [
        'type' => 'button',
        'id' => 'savedCriteriasDelete',
        'class' => 'btn btn-danger btn-sm'
    ]) ?>
    </span>
</div>
