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
use Cake\I18n\Time;

echo $this->Html->css('Search.search', ['block' => 'css']);
echo $this->Html->script('Search.search', ['block' => 'scriptBottom']);

echo $this->Html->scriptBlock(
    'search.setAssociationLabels(' . json_encode($associationLabels) . ');',
    ['block' => 'scriptBottom']
);
echo $this->Html->scriptBlock('search.setModel("' . $savedSearch->model . '");', ['block' => 'scriptBottom']);
echo $this->Html->scriptBlock(
    'search.setFieldProperties(' . json_encode($searchableFields) . ');',
    ['block' => 'scriptBottom']
);
if (!empty($searchData['criteria'])) {
    echo $this->Html->scriptBlock(
        'search.generateCriteriaFields(' . json_encode($searchData['criteria']) . ');',
        ['block' => 'scriptBottom']
    );
}
?>
<div class="box box-solid">
    <div class="box-body">
        <?= $this->Form->create(null, [
            'id' => 'SearchFilterForm',
            'class' => 'search-form',
            'novalidate' => 'novalidate',
            'url' => [
                'plugin' => $this->request->plugin,
                'controller' => $this->request->controller,
                'action' => 'search',
                $this->request->param('pass.0')
            ]
        ]); ?>
        <div class="row">
            <div class="col-lg-3 col-lg-push-9">
                <div class="form-group">
                <?php
                $selectOptions = array_combine(
                    array_keys($searchableFields),
                    array_map(function ($v) {
                        return $v['label'];
                    }, $searchableFields)
                );

                foreach ($selectOptions as $k => $v) {
                    $optGroup = substr($k, 0, strpos($k, '.'));
                    $optGroup = array_key_exists($optGroup, $associationLabels) ?
                        $associationLabels[$optGroup] :
                        $optGroup;

                    if (!array_key_exists($optGroup, $selectOptions)) {
                        $selectOptions[$optGroup] = [];
                    }

                    $selectOptions[$optGroup][$k] = $v;
                    unset($selectOptions[$k]);
                }

                foreach ($selectOptions as $k => &$v) {
                    asort($v);
                }
                ksort($selectOptions);

                echo $this->Form->select(
                    'fields',
                    array_merge(['' => __('-- Add filter --')], $selectOptions),
                    [
                        'class' => 'form-control input-sm',
                        'id' => 'addFilter',
                        // 'empty' => true
                     ]
                ); ?>
                </div>
                <div class="form-group">
                <?php
                    echo $this->Form->select(
                        'aggregator',
                        $searchOptions['aggregators'],
                        [
                            'default' => isset($searchData['aggregator']) ?
                                $searchData['aggregator'] :
                                key($searchOptions['aggregators']),
                            'class' => 'form-control input-sm'
                         ]
                    );
                ?>
                </div>
            </div>
            <hr class="visible-xs visible-sm visible-md" />
            <div class="col-lg-9 col-lg-pull-3">
                <fieldset></fieldset>
            </div>
        </div>
        <div class="row">
            <div class="col-md-8 col-lg-9">
                <?php
                echo $this->element('Search.Search/options', [
                    'searchableFields' => $searchableFields,
                    'savedSearch' => $savedSearch,
                    'selectOptions' => $selectOptions,
                    'associationLabels' => $associationLabels
                ]);
                echo $this->Form->button('<i class="fa fa-search"></i> ' . __('Search'), ['class' => 'btn btn-primary']);
                echo $this->Form->end();
                echo '&nbsp;';
                $exportName = ($savedSearch->get('name') ? $savedSearch->get('name') : $this->name);
                $exportName .= ' ' . $this->Time->format(Time::now(), 'yyyy-MM-dd HH-mm-ss');
                echo $this->element('Search.Menu/search-view-options', [
                    'entity' => $savedSearch, 'name' => $exportName, 'id' => $preSaveId
                ]);
                ?>
            </div>
            <div class="col-md-4 col-lg-3">
            <?php if (!empty($savedSearches)) : ?>
                <div class="form-group">
                <?php
                echo $this->Form->label(__('Saved Searches'));
                echo $this->element('Search.Search/Forms/saved_searches', ['savedSearches' => $savedSearches]);
                ?>
                </div>
            <?php endif; ?>
                <div class="form-group">
                <?= $this->element('Search.Search/Forms/save_search', [
                    'preSaveId' => $preSaveId,
                    'savedSearch' => $savedSearch,
                    'isEditable' => $isEditable && 'criteria' === $savedSearch->type
                ]) ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$scripts = [];
foreach ($searchableFields as $searchField) {
    if (empty($searchField['input']['post'])) {
        continue;
    }
    array_push($scripts, ['post' => $searchField['input']['post']]);
}

echo $this->element('Search.widget_libraries', ['scripts' => $scripts]);
