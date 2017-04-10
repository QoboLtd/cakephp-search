<?php
echo $this->Html->css(
    [
        'AdminLTE./plugins/select2/select2.min',
        'Groups.select2-bootstrap.min'
    ],
    [
        'block' => 'css'
    ]
);
echo $this->Html->script('AdminLTE./plugins/select2/select2.full.min', ['block' => 'scriptBotton']);
echo $this->Html->scriptBlock(
    '$(".select2").select2({
        theme: "bootstrap",
        tags: "true",
        placeholder: "Select an option",
        allowClear: true
    });',
    ['block' => 'scriptBotton']
);

echo $this->Form->create($report);
echo $this->Form->hidden('user_id', ['value' => $user['id']]);

?>
<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><?= __('Add new report');?></h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-6">
                    <?= $this->Form->input('type', ['type' => 'select', 'options' => $chartTypes, 'value' => $this->request->query('type') ? $this->request->query('type') : '']); ?>
                </div>
                <div class="col-md-6">
                    <?= $this->Form->input('name'); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <?= $this->Form->input('content'); ?>    
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <?= $this->Form->input('columns'); ?>
                </div>
                <div class="col-md-6">
                    <?= $this->Form->input('model', ['type' => 'select', 'options' => $models]); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <?= $this->Form->input('is_active'); ?>
                </div>
                <div class="col-md-6">
                    &nbsp;
                </div>
            </div>
        </div>
    </div>
    <?php if (!empty($chartFields)) : ?>
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><?= __('Report options');?></h3>
        </div>
        <div class="box-body">
            <div class="row">
            <?php $count = 0; ?>
            <?php foreach ($chartFields as $field) : ?>
                <?php if (++$count % 2) : ?>
                    </div>
                    <div class="row">
                <?php endif; ?>
                <div class="col-md-6">
                    <?= $this->Form->input($field); ?>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>    
    <?php endif; ?>
    <div class="box-footer">
        <?= $this->Form->button(__('Submit'), ['class' => 'btn btn-primary']) ?>
        &nbsp;
        <?= $this->Form->button(__('Cancel'), ['class' => 'btn remove-client-validation', 'name' => 'btn_operation', 'value' => 'cancel']); ?>
    </div>
    <?= $this->Form->end() ?>
</section>
<?= $this->Html->script('Search.reports', ['block' => 'scriptBotton']); ?>
