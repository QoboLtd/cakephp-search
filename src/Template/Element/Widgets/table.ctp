<?php
use Cake\Event\Event;
use Cake\Utility\Inflector;

$savedSearch = $widget->getData();
$searchData = empty($savedSearch->content['saved']) ? [] : $savedSearch->content['saved'];
$widgetOptions = $widget->getOptions();
$fields = $widgetOptions['fields'];
$associationLabels = $widgetOptions['associationLabels'];

//search url if is a saved one
list($plugin, $controller) = pluginSplit($savedSearch->model);
$url = [
    'plugin' => $plugin,
    'controller' => $controller,
    'action' => 'search',
    $savedSearch->id
];

// search title
$title = '<a href="' . $this->Url->build($url) . '">' . $savedSearch->name . '</a>';
?>
<?php if (!empty($searchData['display_columns'])) : ?>
<div class="dashboard-widget-saved_search">
    <div class="box box-default">
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
                <table id="<?= $widget->getContainerId() ?>" class="table table-hover table-condensed table-vertical-align" width="100%">
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
                            <th><?= $fields[$field]['label'] . $suffix ?></th>
                        <?php endforeach; ?>
                            <th class="actions"><?= __('Actions') ?></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
