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

use Cake\Event\Event;
use Cake\Utility\Inflector;
use Search\Event\EventName;

$config = $widget->getConfig();
$data = $widget->getData();
$type = $widget->getType();
$totals = !empty($config['info']['totals']) ? explode(',', $config['info']['totals']) : [];

$dataRecords = !empty($data['options']['data']) ? $data['options']['data'] : [];

$data = [];

$columns = explode(',', $config['info']['columns']);
$options = [];

echo $this->Html->script('Search.grid_report', ['block' => 'scriptBottom']);
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
                        <td><?= $this->element('Search.Result/grid-item', [
                            'config' => $config, 'column' => $col, 'item' => $item
                        ]) ?></td>
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
