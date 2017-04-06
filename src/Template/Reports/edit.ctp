<?php
use Search\Widgets\WidgetFactory;

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
$chartTypes = WidgetFactory::getChartReportTypes();
asort($chartTypes);
?>
<section class="content-header">
    <h1><?= __('Edit {0}', ['Report']) ?></h1>
</section>
<section class="content">
    <div class="row">
        <div class="col-xs-12 col-md-6">
            <div class="box box-solid">
                <?= $this->Form->create($report) ?>
                <?= $this->Form->hidden('user_id', ['value' => $user['id']]); ?>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <?= $this->Form->input('name'); ?>
                        </div>
                        <div class="col-md-6">
                            <?= $this->Form->input('model'); ?>
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
                            <?= $this->Form->input('type', ['type' => 'select', 'options' => $chartTypes]); ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <?= $this->Form->input('x_axis'); ?>
                        </div>
                        <div class="col-md-6">
                            <?= $this->Form->input('y_axis'); ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <?= $this->Form->input('chart_label'); ?>
                        </div>
                        <div class="col-md-6">
                            <?= $this->Form->input('chart_value'); ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <?= $this->Form->input('chart_max'); ?>
                        </div>
                        <div class="col-md-6">
                            <?= $this->Form->input('is_active'); ?>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    <?= $this->Form->button(__('Submit'), ['class' => 'btn btn-primary']) ?>
                    &nbsp;
                    <?= $this->Form->button(__('Cancel'), ['class' => 'btn remove-client-validation', 'name' => 'btn_operation', 'value' => 'cancel']); ?>
                </div>
                <?= $this->Form->end() ?>
            </div>
        </div>
    </div>
</section>
