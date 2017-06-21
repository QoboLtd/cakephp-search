<?php
use Cake\Core\Configure;

// get search information from the saved search (if is set) to construct search results title
if (!empty($savedSearch)) {
    $searchId = $savedSearch->id;
    $searchName = $savedSearch->name;
    $model = $savedSearch->model;
}

// search title
$title = $this->name;
if (!empty($searchName)) {
    $title = $searchName;
}

//search url if is a saved one
$url = null;
if (!empty($model) && !empty($searchId)) {
    list($plugin, $controller) = pluginSplit($model);
    $url = [
        'plugin' => $plugin,
        'controller' => $controller,
        'action' => 'search',
        $searchId
    ];
} elseif (!empty($searchId)) {
    $url = $this->request->here;
}

if (!empty($url)) {
    $title = '<a href="' . $this->Url->build($url) . '">' . $title . '</a>';
}

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
                       <th><?= $searchFields[$field]['label'] ?></th>
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
