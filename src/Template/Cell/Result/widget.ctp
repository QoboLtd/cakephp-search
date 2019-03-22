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

use Cake\Utility\Inflector;

// $chartOptions = [$chartOptions[1],$chartOptions[2]];

echo $cakeView->Html->css(
    [
        'Qobo/Utils./plugins/datatables/css/dataTables.bootstrap.min',
        'Search.grid'
    ],
    ['block' => 'css']
);

echo $cakeView->Html->script(
    [
        'Qobo/Utils./plugins/datatables/datatables.min',
        'Qobo/Utils./plugins/datatables/js/dataTables.bootstrap.min',
        'Qobo/Utils.dataTables.init'
    ],
    ['block' => 'scriptBottom']
);

if ($isGroup) {
    $chartsCount = count($chartOptions);

    echo $cakeView->Html->script(
        [
            'https://cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js',
            'Search./plugins/Chart.min.js',
            'Qobo/Utils./plugins/d3/d3.min',
            'Qobo/Utils./plugins/d3/extensions/d3-funnel.min',
            'Search.reportGraphs'
        ],
        ['block' => 'scriptBottom']
    );

    echo $cakeView->Html->scriptBlock('
        var chartsData = chartsData || [];
        chartsData = chartsData.concat(' . json_encode($chartOptions) . ');
    ');
}

if ($isExport) {
    echo $cakeView->Html->css('Qobo/Utils.buttons.dataTables.min', ['block' => 'css']);
}

echo $cakeView->Html->scriptBlock('new DataTablesInit(' . json_encode($dtOptions) . ');', ['block' => 'scriptBottom']);
?>
<div class="dashboard-widget-saved-search nav-tabs-custom">
    <ul class="nav nav-tabs pull-right" id="widget-<?= md5(implode('', $viewOptions['url'])) ?>">
        <li class="<?= ! $isGroup ? 'active' : '' ?>">
            <a href="#table_<?= $tableOptions['id'] ?>" data-toggle="tab" aria-expanded="true">
                <i class="fa fa-table"></i>
            </a>
        </li>
    <?php if ($isGroup) : ?>
        <?php foreach ($chartOptions as $key => $chart) : ?>
            <li class="<?= $chartsCount === $key + 1 ? 'active' : '' ?>">
                <a href="<?= '#' . Inflector::delimit($chart['chart']) . '_' . $tableOptions['id'] ?>" data-toggle="tab" aria-expanded="false">
                    <i class="fa fa-<?= $chart['icon'] ?>"></i>
                </a>
            </li>
        <?php endforeach; ?>
    <?php endif; ?>
        <li class="pull-left header"><?= $this->Html->link($viewOptions['title'], $viewOptions['url']) ?></li>
    </ul>
    <div class="tab-content">
        <div id="table_<?= $tableOptions['id'] ?>" class="tab-pane <?= ! $isGroup ? 'active' : '' ?>">
            <div class="table-responsive">
                <?php if ($isExport) : ?>
                    <?= $this->Html->link(__('Export'), $viewOptions['exportUrl'], ['class' => 'dt-button pull-right']) ?>
                <?php endif; ?>
                <table id="<?= $tableOptions['id'] ?>" class="table table-hover table-condensed table-vertical-align" width="100%">
                    <thead>
                        <tr>
                        <?php foreach ($tableOptions['headers'] as $header) : ?>
                            <th><?= $header ?></th>
                        <?php endforeach; ?>
                        <?php if (! $isGroup) : ?>
                            <th class="actions"><?= __('Actions') ?></th>
                        <?php endif; ?>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    <?php if ($isGroup) : ?>
        <?php foreach ($chartOptions as $key => $chart) : ?>
            <div id="<?= Inflector::delimit($chart['chart']) . '_' . $tableOptions['id'] ?>" class="tab-pane <?= $chartsCount === $key + 1 ? 'active' : '' ?>"><canvas id="canvas_<?= $chart['id']?>" ></canvas></div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
</div>
