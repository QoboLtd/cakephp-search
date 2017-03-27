<?php
echo $this->Html->css('Search.dashboard', ['block' => 'css']);
echo $this->Html->script('AdminLTE./plugins/jQueryUI/jquery-ui.min', ['block' => 'script']);
echo $this->Html->script('Search.dashboard', ['block' => 'scriptBotton']);
?>
<section class="content-header">
    <h4><?= __('Edit {0}', ['Dashboard']) ?></h4>
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
                    <div class="col-xs-<?= 12 / $columnsCount ?>">
                        <p class="h3 text-center"><?= $columns[$col] ?></p>
                        <ul class="savetrue droppable-area" data-column=<?= $col ?>>
                        <?php
                        foreach ($savedWidgetData as $savedWidget) {
                            if ($savedWidget['data']['column'] !== $col) {
                                continue;
                            }
                            echo $this->element('Search.Widgets/droppable_block', ['widget' => $savedWidget]);
                        }
                        ?>
                        </ul>
                    </div>
                <?php endfor; ?>
                </div>
            </div>
            <p class="h3 text-center saved-searches-title"><?= __('Widgets') ?></p>
            <ul class="list-inline droppable-area saved-searches-area">
                <?php foreach ($widgets as $widget) : ?>
                    <?php echo $this->element('Search.Widgets/droppable_block', ['widget' => $widget]);?>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php
    echo $this->Form->button(__('Submit'), ['class' => 'btn btn-primary']);
    echo $this->Form->end();
    ?>
</section>
