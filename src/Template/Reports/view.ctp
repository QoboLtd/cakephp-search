<section class="content-header">
    <h1><?= $this->Html->link(
        __('Reports'),
        ['plugin' => 'Search', 'controller' => 'Reports', 'action' => 'index']
    ) . ' &raquo; ' . h($report->name) ?></h1>
</section>
<section class="content">
    <div class="row">
        <div class="col-md-6">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <i class="fa fa-area-chart"></i>

                    <h3 class="box-title">Details</h3>
                </div>
                <div class="box-body">
                    <dl class="dl-horizontal">
                        <dt><?= __('Name') ?></dt>
                        <dd><?= h($report->name) ?></dd>
                        <dt><?= __('Model') ?></dt>
                        <dd><?= h($report->model) ?></dd>
                        <dt><?= __('Content') ?></dt>
                        <dd><?= h($report->content) ?></dd>
                        <dt><?= __('Columns') ?></dt>
                        <dd><?= h($report->columns) ?></dd>
                        <dt><?= __('Type') ?></dt>
                        <dd><?= h($report->type) ?></dd>
                        <dt><?= __('X Axis') ?></dt>
                        <dd><?= h($report->x_axis) ?></dd>
                         <dt><?= __('Y Axis') ?></dt>
                        <dd><?= h($report->y_axis) ?></dd>
                        <dt><?= __('Status') ?></dt>
                        <dd><?= h($report->is_active ? 'Active' : 'Disabled') ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</section>

