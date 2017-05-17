<?php
use Cake\Utility\Inflector;

$config = $widget->getConfig();
$data = $widget->getData();
$type = $widget->getType();

$columns = explode(',', $config['info']['columns']);

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
                <table  id="report" class="table table-hover table-condensed table-vertical-align" cellspacing="0" width="100%">
                <thead>
                    <tr>
                    <?php foreach ($columns as $col) : ?>
                        <th><?= Inflector::humanize($col) ?></th>
                    <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($data['options']['data'] as $k => $item) : ?>
                    <tr>
                    <?php foreach ($columns as $col) : ?>
                        <td><?= $item[$col] ?></td>
                    <?php endforeach; ?>                    
                    </tr>
                <?php endforeach; ?>
                </tbody>
                </table>
            </div> 
        </div>
    </div>
</div>
