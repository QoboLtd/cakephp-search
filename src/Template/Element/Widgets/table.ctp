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
use Cake\Event\Event;
use Cake\Utility\Inflector;

$savedSearch = $widget->getData();
$searchData = empty($savedSearch->content['saved']) ? [] : $savedSearch->content['saved'];
$widgetOptions = $widget->getOptions();
$fields = empty($widgetOptions['fields']) ? [] : $widgetOptions['fields'];
$associationLabels = empty($widgetOptions['associationLabels']) ? [] : $widgetOptions['associationLabels'];

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

// Export button
if (Configure::read('Search.dashboardExport')) {
    $exportLink = "<a href='/" . $savedSearch->model . '/export-search/' . $savedSearch->id . '/' . $savedSearch->name . "' class='dt-button pull-right'>" . __('Export') . "</a>";
}

echo $this->Html->css(['Search.grid'], ['block' => 'css']);

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
                <?php if (!empty($exportLink)) : ?>
                    <?= $exportLink ?>
                <?php endif; ?>     
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
