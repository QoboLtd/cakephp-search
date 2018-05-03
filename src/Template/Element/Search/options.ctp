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

echo $this->Html->css('Search.search_options', ['block' => 'css']);
echo $this->Html->script('Search./plugins/multiselect.min', ['block' => 'script']);
echo $this->Html->script('Search.search_options', ['block' => 'scriptBottom']);

$availableColumns = [];
$displayColumns = [];
$groupByColumns = [];
// get display, group and available columns
foreach ($searchableFields as $k => $v) {
    $tableName = substr($k, 0, strpos($k, '.'));
    $tableName = array_key_exists($tableName, $associationLabels) ? $associationLabels[$tableName] : $tableName;
    $suffix = $savedSearch->model === $tableName ? '' : ' (' . $tableName . ')';

    if (in_array($k, $searchData['display_columns'])) {
        $displayColumns[$k] = $v['label'] . $suffix;
    }

    if (! in_array($k, $searchData['display_columns'])) {
        $availableColumns[$k] = $v['label'] . $suffix;
    }

    if ($savedSearch->model === $tableName) {
        $groupByColumns[$k] = $v['label'] . $suffix;
    }
}

asort($availableColumns);
asort($groupByColumns);

// sort display columns based on saved search display_columns order
$displayColumns = array_merge(array_flip($searchData['display_columns']), $displayColumns);
?>

<div class="row">
    <div class="col-md-5 col-lg-4">
        <?= $this->Form->label(__('Available Columns')) ?>
        <?= $this->Form->select('', $availableColumns, [
            'id' => 'available-columns',
            'class' => 'form-control',
            'size' => '8',
            'multiple' => 'multiple'
            ]) ?>
    </div>
    <div class="col-md-2">
        <?= $this->Form->label(false, '&nbsp;', ['escape' => false]) ?>
        <button type="button" id="available-columns_rightAll" class="btn btn-block">
            <i class="glyphicon glyphicon-forward"></i>
        </button>
        <button type="button" id="available-columns_rightSelected" class="btn btn-block">
            <i class="glyphicon glyphicon-chevron-right"></i>
        </button>
        <button type="button" id="available-columns_leftSelected" class="btn btn-block">
            <i class="glyphicon glyphicon-chevron-left"></i>
        </button>
        <button type="button" id="available-columns_leftAll" class="btn btn-block">
            <i class="glyphicon glyphicon-backward"></i>
        </button>
    </div>
    <div class="col-md-5 col-lg-4">
        <?= $this->Form->label(__('Display Columns')) ?>
        <?= $this->Form->select('display_columns', $displayColumns, [
            'id' => 'available-columns_to',
            'class' => 'form-control',
            'size' => '8',
            'multiple' => 'multiple'
            ]) ?>
        <div class="row">
            <div class="col-sm-6">
                <button type="button" id="available-columns_move_up" class="btn btn-block">
                    <i class="glyphicon glyphicon-arrow-up"></i>
                </button>
            </div>
            <div class="col-sm-6">
                <button type="button" id="available-columns_move_down" class="btn btn-block col-sm-6">
                    <i class="glyphicon glyphicon-arrow-down"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="col-lg-2">
        <div class="row">
            <div class="col-md-4 col-lg-12">
                <div class="form-group">
                <?php
                echo $this->Form->label(__('Sort Field'));
                echo $this->Form->select(
                    'sort_by_field',
                    $selectOptions,
                    [
                        'default' => isset($searchData['sort_by_field'])
                            ? $searchData['sort_by_field']
                            : key(current($selectOptions)),
                        'class' => 'form-control input-sm'
                     ]
                );
                ?>
                </div>
            </div>
            <div class="col-md-4 col-lg-12">
                <div class="form-group">
                <?php
                echo $this->Form->label(__('Sort Order'));
                echo $this->Form->select(
                    'sort_by_order',
                    $searchOptions['sortByOrder'],
                    [
                        'default' => isset($searchData['sort_by_order']) ? $searchData['sort_by_order'] : 'asc',
                        'class' => 'form-control input-sm'
                     ]
                );
                ?>
                </div>
            </div>
            <div class="col-md-4 col-lg-12">
                <div class="form-group">
                <?php
                echo $this->Form->label(__('Group By'));
                echo $this->Form->select(
                    'group_by',
                    $groupByColumns,
                    [
                        'empty' => true,
                        'default' => isset($searchData['group_by']) ? $searchData['group_by'] : '',
                        'class' => 'form-control input-sm'
                     ]
                );
                ?>
                </div>
            </div>
        </div>
    </div>
</div>
