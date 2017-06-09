<?php
use Cake\Event\Event;
use Cake\Utility\Inflector;

$config = $widget->getConfig();
$data = $widget->getData();
$type = $widget->getType();
$totals = !empty($config['info']['totals']) ? explode(',', $config['info']['totals']) : [];

$dataRecords = !empty($data['options']['data']) ? $data['options']['data'] : [];

$data = [];

$columns = explode(',', $config['info']['columns']);
$options = [];

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
                <table  class="table table-hover table-condensed table-vertical-align report-grid" cellspacing="0" width="100%">
                <thead>
                    <tr>
                    <?php foreach ($columns as $col) : ?>
                        <th class="<?= in_array($col, $totals) ? 'sum' : 'normal'; ?>"><?= Inflector::humanize($col) ?></th>
                    <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($dataRecords as $k => $item) : ?>
                    <tr>
                    <?php foreach ($columns as $col) : ?>
                        <?php
                            $_col = preg_replace('/_/', '', $col);
                            $event = new Event('Search.Dashboard.Widget.GridElement', $this, [
                                'model' => $config['modelName'],
                                'field' => $_col,
                                'value' => $item[$col],
                                $options
                            ]);
                            $this->eventManager()->dispatch($event);
                        ?>
                        <td><?= !empty($event->result) ? $event->result : $item[$col] ?></td>
                    <?php endforeach; ?>                    
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>                        
                        <?php foreach ($columns as $col) : ?>
                            <th class="<?= in_array($col, $totals) ? 'sum' : 'normal'; ?>"></th>
                        <?php endforeach; ?>
                    </tr>
                </tfoot>
                </table>
            </div> 
        </div>
    </div>
</div>
