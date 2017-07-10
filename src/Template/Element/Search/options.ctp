<?php
echo $this->Html->css('Search.search_options', ['block' => 'css']);
echo $this->Html->script('AdminLTE./plugins/jQueryUI/jquery-ui.min', ['block' => 'script']);
echo $this->Html->script('Search.search_options', ['block' => 'scriptBotton']);

$availableColumns = [];
$displayColumns = [];
// get display and available columns
foreach ($searchableFields as $k => $v) {
    $tableName = substr($k, 0, strpos($k, '.'));
    $tableName = array_key_exists($tableName, $associationLabels) ?
        $associationLabels[$tableName] :
        $tableName;
    $suffix = $savedSearch->model === $tableName ? '' : ' (' . $tableName . ')';

    if (in_array($k, $searchData['display_columns'])) {
        $displayColumns[$k] = $v['label'] . $suffix;
    } else {
        $availableColumns[$k] = $v['label'] . $suffix;
    }
}

// alphabetically sort available columns
asort($availableColumns);

// sort display columns based on saved search display_columns order
$displayColumns = array_merge(array_flip($searchData['display_columns']), $displayColumns);
?>
<div class="row">
    <div class="col-md-4">
    <?= $this->Form->label(__('Available Columns')) ?>
        <ul id="availableColumns" class="connectedSortable">
        <?php foreach ($availableColumns as $k => $v) : ?>
            <li data-id="<?= $k ?>">
                <?= $v ?>
            </li>
        <?php endforeach; ?>
        </ul>
    </div>
    <div class="col-md-4">
    <?= $this->Form->label(__('Display Columns')) ?>
        <ul id="displayColumns" class="connectedSortable">
        <?php foreach ($displayColumns as $k => $v) : ?>
            <li data-id="<?= $k ?>">
                <?= $v ?>
            </li>
        <?php endforeach; ?>
        </ul>
    </div>
    <div class="col-md-4">
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
</div>
