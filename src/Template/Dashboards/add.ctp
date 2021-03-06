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

echo $this->Html->css(['Qobo/Search.dashboard', 'Qobo/Search.grid'], ['block' => 'css']);
echo $this->Html->script('AdminLTE./bower_components/jquery-ui/jquery-ui.min', ['block' => 'script']);
echo $this->Html->script('Qobo/Search.dashboard', ['block' => 'scriptBottom']);
echo $this->Html->script('Qobo/Search./plugins/vue.min', ['block' => 'scriptBottom']);
echo $this->Html->script('Qobo/Search./plugins/vue-grid-layout.min', ['block' => 'scriptBottom']);
echo $this->Html->scriptBlock('var api_token = "' . Configure::read('Search.api.token') . '";', ['block' => 'scriptBottom']);
echo $this->Html->scriptBlock("var grid_layout = '" . addslashes(json_encode($savedWidgetData)) . "';", ['block' => 'scriptBottom']);

echo $this->Html->script('Qobo/Search./plugins/qobo.grid', ['block' => 'scriptBottom']);
?>
<section class="content-header">
    <div class="row">
        <div class="col-xs-12 col-md-6">
            <h4><?= __d('Qobo/Search', 'Create {0}', ['Dashboard']) ?></h4>
        </div>
    </div>
</section>
<section class="content">
    <?= $this->Form->create($dashboard, ['id' => 'dashboardForm']) ?>
    <div class="box box-primary">
        <div class="box-body">
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <?= $this->Form->control('name'); ?>
                </div>
                <div class="col-xs-12 col-md-6">
                    <?= $this->Form->control('group_id', ['options' => $groups, 'empty' => true]); ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    echo $this->element('Qobo/Search.dashboard_form');
    echo $this->Form->button(__d('Qobo/Search', 'Submit'), ['class' => 'btn btn-primary']);
    echo $this->Form->end();
    ?>
</section>
