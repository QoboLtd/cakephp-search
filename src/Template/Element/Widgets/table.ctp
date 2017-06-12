<?php
use Cake\Event\Event;
use Cake\Utility\Inflector;

$savedSearch = $widget->getData();
$searchData = $savedSearch->content['saved'];
$widgetOptions = $widget->getOptions();
$fields = $widgetOptions['fields'];

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
                            <?php $field = Inflector::humanize($field);
                            $field = array_key_exists($field, $fields) ? $fields[$field]['label'] : $field ?>
                            <th><?= $field ?></th>
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