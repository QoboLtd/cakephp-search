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

echo $this->Form->label(__('Save search'));

echo $this->Form->create(null, [
    'class' => 'save-search-form',
    'url' => [
        'plugin' => $this->request->getParam('plugin'),
        'controller' => $this->request->getParam('controller'),
        'action' => ($savedSearch->get('is_editable') ? 'edit': 'save') . '-search',
        $preSaveId,
        $savedSearch->get('is_editable') ? $savedSearch->get('id') : null
    ]
]); ?>
<div class="input-group">
    <?= $this->Form->control('name', [
        'label' => false,
        'class' => 'form-control input-sm',
        'placeholder' => 'Save criteria name',
        'required' => true,
        'value' => $savedSearch->get('is_editable') ? $savedSearch->get('name') : ''
    ]); ?>
    <span class="input-group-btn">
        <?= $this->Form->button(
            '<i class="fa fa-floppy-o"></i>',
            ['class' => 'btn btn-sm btn-primary']
        ) ?>
    </span>
</div>
<?= $this->Form->end(); ?>
