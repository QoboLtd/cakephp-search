<?php
    $config = $widget->getConfig();
    $data = $widget->getData();
    $type = $widget->getType();

    echo $this->Html->script('Search.grid_report', ['block' => 'scriptBotton']);
?>
<div class='dashboard-widget-display_config'>
    <div class="box box-default">
        <div class="box-header with-border">
            <h3 class="box-title"><?= $config['info']['name'] ?></h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse">
                    <i class="fa fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="box-body">
            <div id="<?= $widget->getContainerId()?>">
                <table  id="report" class="display" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($data['options']['data'] as $k => $item) : ?>
                    <tr>
                        <td><?= $item['name'] ?></td>
                        <td><?= $item['status'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                </table>
            </div> 
        </div>
    </div>
</div>
