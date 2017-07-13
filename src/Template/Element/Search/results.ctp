<?php
use Cake\Core\Configure;

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

$uid = uniqid();
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
            <table id="table-datatable-<?= $uid ?>" class="table table-hover table-condensed table-vertical-align" width="100%">
                <thead>
                    <tr>
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
<?php
// DataTables options
$options = [
    'table_id' => '#table-datatable-' . $uid,
    'url' => $this->Url->build([
        'plugin' => $this->request->plugin,
        'controller' => $this->request->controller,
        'action' => $this->request->action,
        $preSaveId
    ]),
    'extension' => 'json',
    'token' => Configure::read('CsvMigrations.api.token'),
    'sort_by_field' => (int)array_search($searchData['sort_by_field'], $searchData['display_columns']),
    'sort_by_order' => $searchData['sort_by_order']
];

foreach ($searchData['display_columns'] as $field) {
    $options['columns'][] = ['name' => $field];
}
$options['columns'][] = ['name' => 'actions'];

echo $this->Html->scriptBlock(
    'view_search_result.init(' . json_encode($options) . ');',
    ['block' => 'scriptBotton']
);
echo $this->Html->css('Search.search-datatables', ['block' => 'css']);
?>
<?php endif; ?>
