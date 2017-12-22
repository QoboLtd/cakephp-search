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

echo $this->Html->css(['Search.dashboard', 'Search.grid'], ['block' => 'css']);
echo $this->Html->script('AdminLTE./plugins/jQueryUI/jquery-ui.min', ['block' => 'script']);
echo $this->Html->script('Search.dashboard', ['block' => 'scriptBottom']);
echo $this->Html->script('Search./plugins/vue.min', ['block' => 'scriptBottom']);
echo $this->Html->script('Search./plugins/vue-grid-layout.min', ['block' => 'scriptBottom']);
echo $this->Html->scriptBlock('var api_token = "' . Configure::read('Search.api.token') . '";', ['block' => 'scriptBottom']);
echo $this->Html->scriptBlock("var grid_layout = '" . json_encode($savedWidgetData) . "';", ['block' => 'scriptBottom']);

echo $this->Html->script('Search./plugins/qobo.grid', ['block' => 'scriptBottom']);
?>
<section class="content-header">
    <div class="row">
        <div class="col-xs-12 col-md-6">
            <h4><?= __('Edit {0}', ['Dashboard']) ?></h4>
        </div>
    </div>
</section>
<section class="content">
    <?= $this->Form->create($dashboard, ['id' => 'dashboardForm']) ?>
    <div class="box box-solid">
        <div class="box-body">
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    <?= $this->Form->input('name'); ?>
                </div>
                <div class="col-xs-12 col-md-6">
                    <?= $this->Form->input('role_id', ['options' => $roles, 'empty' => true]); ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    echo $this->element('Search.dashboard_form');
    echo $this->Form->button(__('Submit'), ['class' => 'btn btn-primary']);
    echo $this->Form->end();
    ?>
</section>
