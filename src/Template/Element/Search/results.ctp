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

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Search\Utility;

$tableId = 'table-datatable-' . uniqid();
echo $this->Html->css(
    [
        'Qobo/Utils./plugins/datatables/css/dataTables.bootstrap.min',
        'Qobo/Utils./plugins/datatables/extensions/Select/css/select.bootstrap.min',
        'Qobo/Utils./css/dataTables.batch'
    ],
    ['block' => 'css']
);
echo $this->Html->script(
    [
        'Qobo/Utils./plugins/datatables/datatables.min',
        'Qobo/Utils./plugins/datatables/js/dataTables.bootstrap.min',
        'Qobo/Utils./plugins/datatables/extensions/Select/js/dataTables.select.min',
        'Qobo/Utils.dataTables.init'
    ],
    ['block' => 'scriptBottom']
);

$orderField = (int)array_search($searchData['sort_by_field'], $searchData['display_columns']);
if (Configure::read('Search.batch.active')) {
    $orderField += 1;
}

$dtOptions = [
    'table_id' => '#' . $tableId,
    'order' => [$orderField, $searchData['sort_by_order']],
    'ajax' => [
        'token' => Configure::read('Search.api.token'),
        'url' => $this->Url->build([
            'plugin' => $this->request->plugin,
            'controller' => $this->request->controller,
            'action' => 'search',
            $preSaveId
        ]),
        'columns' => call_user_func(function () use ($searchData, $model) {
            $result = [];

            // add primary key to DataTable columns if batch is active
            if (Configure::read('Search.batch.active')) {
                $table = TableRegistry::get($model);
                $result[] = $table->getPrimaryKey();
            }

            foreach ($searchData['display_columns'] as $field) {
                list(, $fieldName) = pluginSplit($field);
                $result[] = $fieldName;
            }
            $result[] = Utility::MENU_PROPERTY_NAME;

            return $result;
        }),
        'extras' => ['format' => 'pretty']
    ],
];
if (Configure::read('Search.batch.active')) {
    $dtOptions['batch'] = ['id' => Configure::read('Search.batch.button_id')];
}

echo $this->Html->scriptBlock(
    '// initialize dataTable
    new DataTablesInit(' . json_encode($dtOptions) . ');',
    ['block' => 'scriptBottom']
);

//search url if is a saved one
list($plugin, $controller) = pluginSplit($savedSearch->model);
$url = [
    'plugin' => $plugin,
    'controller' => $controller,
    'action' => 'search',
    $savedSearch->id
];

$searchName = $savedSearch->has('name') ? $savedSearch->name : $this->name;
$title = '<a href="' . $this->Url->build($url) . '">' . $searchName . '</a>';
?>
<?php if (!empty($searchData['display_columns'])) : ?>
<div class="box box-solid">
    <div class="box-header">
        <h3 class="box-title"><?= $title; ?></h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="box-body">
        <div class="table-responsive">
            <table id="<?= $tableId ?>" class="table table-hover table-condensed table-vertical-align" width="100%">
                <thead>
                    <tr>
                    <?php if (Configure::read('Search.batch.active')) : ?>
                        <th class="dt-select-column"></th>
                    <?php endif; ?>
                    <?php foreach ($searchData['display_columns'] as $field) : ?>
                        <?php
                        $tableName = substr($field, 0, strpos($field, '.'));
                        $label = array_key_exists($tableName, $associationLabels) ?
                            $associationLabels[$tableName] :
                            $tableName;

                        list(, $modelName) = pluginSplit($savedSearch->model);
                        $suffix = $modelName === $label ? '' : ' (' . $label . ')';
                        ?>
                       <th><?= $searchableFields[$field]['label'] . $suffix ?></th>
                    <?php endforeach; ?>
                        <th class="actions"><?= __('Actions') ?></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
