<?php if (!empty($searchFields) && !empty($searchOperators)) : ?>

    <?= $this->Html->css('Search.search', ['block' => true]); ?>
    <?= $this->Html->script('Search.search', ['block' => 'scriptBottom']); ?>

    <?= $this->Html->scriptBlock(
        'search.setFieldTypeOperators(' . json_encode($searchOperators) . ');',
        ['block' => 'scriptBottom']
    ); ?>

    <?= $this->Html->scriptBlock(
        'search.setFieldProperties(' . json_encode($searchFields) . ');',
        ['block' => 'scriptBottom']
    ); ?>

    <?= $this->Html->scriptBlock(
        'search.generateCriteriaFields(' . json_encode($this->request->data) . ');',
        ['block' => 'scriptBottom']
    ); ?>
<div class="well">
    <h4><?= __('Filters') ?></h4>
    <hr />
    <div class="row">
        <div class="col-md-4 col-md-push-8 col-lg-3 col-lg-push-9">
            <?= $this->Form->label(__('Add Filter')) ?>
            <?php
            $selectOptions = array_combine(array_keys($searchFields), array_map(function ($v) {return $v['label'];}, $searchFields));
            echo $this->Form->select(
                'fields',
                 $selectOptions, [
                    'class' => 'form-control input-sm',
                    'id' => 'addFilter',
                    'empty' => true
                ]
            ) ?>
            <div class="row">
                <div class="col-sm-6 col-md-12">
                    <?= $this->element('save_search_criterias'); ?>
                </div>
                <div class="col-sm-6 col-md-12">
                    <?= $this->element('save_search_results'); ?>
                </div>
            </div>
        </div>
        <hr class="visible-xs visible-sm" />
        <div class="col-md-8 col-md-pull-4 col-lg-9 col-lg-pull-3">
            <?= $this->Form->create(null, [
                'id' => 'SearchFilterForm',
                'url' => [
                    'plugin' => 'Search',
                    'controller' => 'Search',
                    'action' => 'advanced',
                    $this->request->params['pass'][0]
                ]
            ]) ?>
            <fieldset></fieldset>
            <?= $this->Form->button(__('Submit'), ['class' => 'btn btn-primary']) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>
<?php endif; ?>
