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

$url = ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'exportSearch', $id, $name];
echo $this->Html->link('<i class="fa fa-download"></i> ' . __('Export'), $url, [
    'class' => 'btn btn-default', 'escape' => false
]);

if (Configure::read('Search.batch.active')) : ?>
&nbsp;<div class="btn-group">
    <?= $this->Form->button('<i class="fa fa-bars"></i> Batch', [
        'id' => 'batch-button',
        'type' => 'button',
        'class' => 'btn btn-default dropdown-toggle',
        'data-toggle' => 'dropdown',
        'aria-haspopup' => 'true',
        'aria-expanded' => 'false',
        'disabled' => true
    ]) ?>
</div>
<?php endif; ?>