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

echo $this->Html->css('Search.dashboard', ['block' => 'css']);
echo $this->Html->script('AdminLTE./plugins/jQueryUI/jquery-ui.min', ['block' => 'script']);
echo $this->Html->script('Search.dashboard', ['block' => 'scriptBottom']);
?>
<section class="content-header">
    <div class="row">
        <div class="col-xs-12 col-md-6">
            <h4><?= __('Create {0}', ['Dashboard']) ?></h4>
        </div>
    </div>
</section>
<section class="content">
    <?= $this->Form->create($dashboard, ['id' => 'dashboardForm']) ?>
    <div class="box box-solid">
        <div class="box-body">
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <?= $this->Form->input('name'); ?>
                </div>
                <div class="col-xs-12 col-md-6">
                    <?= $this->Form->input('role_id', ['options' => $roles, 'empty' => true]); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="box box-solid">
        <div class="box-body">
            <div class="dashboard-saved-searches">
                <div class="row">
                <?php $columnsCount = count($columns); for ($col = 0; $col < $columnsCount; $col++) : ?>
                    <div class="col-xs-12 col-sm-<?= 12 / $columnsCount ?>">
                        <p class="h3 text-center"><?= $columns[$col] ?></p>
                        <ul class="savetrue droppable-area" data-column=<?= $col ?>></ul>
                    </div>
                <?php endfor; ?>
                </div>
            </div>
            <p class="h3 text-center saved-searches-title"><?= __('Widgets') ?></p>
            <ul class="list-inline droppable-area saved-searches-area">
                <?php foreach ($widgets as $widget) : ?>
                    <?= $this->element('Search.Widgets/droppable_block', ['widget' => $widget]); ?>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php
    echo $this->Form->button(__('Submit'), ['class' => 'btn btn-primary']);
    echo $this->Form->end();
    ?>
</section>
