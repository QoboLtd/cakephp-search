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

echo $cakeView->Html->css('Qobo/Utils./plugins/datatables/css/dataTables.bootstrap.min', ['block' => 'css']);

echo $cakeView->Html->script(
    [
        'Qobo/Utils./plugins/datatables/datatables.min',
        'Qobo/Utils./plugins/datatables/js/dataTables.bootstrap.min',
        'Qobo/Utils.dataTables.init'
    ],
    ['block' => 'scriptBottom']
);

if ($isBatch) {
    echo $cakeView->Html->css(
        [
            'Qobo/Utils./plugins/datatables/extensions/Select/css/select.bootstrap.min',
            'Qobo/Utils./css/dataTables.batch'
        ],
        ['block' => 'css']
    );

    echo $cakeView->Html->script(
        'Qobo/Utils./plugins/datatables/extensions/Select/js/dataTables.select.min',
        ['block' => 'scriptBottom']
    );
}

echo $cakeView->Html->scriptBlock('new DataTablesInit(' . json_encode($dtOptions) . ');', ['block' => 'scriptBottom']);
?>
<div class="box box-solid">
    <div class="box-header">
        <h3 class="box-title"><?= $this->Html->link($viewOptions['title'], $viewOptions['url']) ?></h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="box-body">
        <div class="table-responsive">
            <table id="<?= $tableOptions['id'] ?>" class="table table-hover table-condensed table-vertical-align" width="100%">
                <thead>
                    <tr>
                    <?php if ($isBatch && !$isGroup) : ?>
                        <th class="dt-select-column"></th>
                    <?php endif; ?>
                    <?php foreach ($tableOptions['headers'] as $header) : ?>
                        <th><?= $header ?></th>
                    <?php endforeach; ?>
                    <?php if (!$isGroup) : ?>
                        <th class="actions"><?= __('Actions') ?></th>
                    <?php endif; ?>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>