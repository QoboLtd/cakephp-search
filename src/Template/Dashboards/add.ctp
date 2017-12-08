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

echo $this->Html->css(['Search.dashboard', 'Search.grid'], ['block' => 'css']);
echo $this->Html->script('AdminLTE./plugins/jQueryUI/jquery-ui.min', ['block' => 'script']);
echo $this->Html->script('Search.dashboard', ['block' => 'scriptBottom']);
echo $this->Html->script('https://unpkg.com/vue@2.5.9/dist/vue.js', ['block' => 'scriptBottom']);
echo $this->Html->script('Search.vue-grid-layout', ['block' => 'scriptBottom']);
echo $this->Html->script('Search.qobo.grid', ['block' => 'scriptBottom']);
?>
<section class="content-header">
    <div class="row">
        <div class="col-xs-12 col-md-6">
            <h4><?= __('Create {0}', ['Dashboard']) ?></h4>
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
    <div id="grid-app">
        <div class="box box-solid">
            <div class="box-body">
                <div class="box-header">
                    <h3 class="box-title"><?= __('Widgets') ?></h3>
                </div>
                <div class="box-body" style="border:1px dashed #d3d3d3;">
                    <grid-layout
                        :layout="layout"
                        :row-height="50"
                        :vertical-compact="true"
                        :margin="[10, 10]"
                        :use-css-transforms="true"
                    >
                        <grid-item v-for="item in layout" :key="item.i"
                                   :x="item.x"
                                   :y="item.y"
                                   :w="item.w"
                                   :h="item.h"
                                   :min-w="2"
                                   :min-h="2"
                                   :i="item.i"
                                   class="box box-solid box-success"
                        >
                            <div class='box-header with-border'>
                                <h3 class="box-title"><i class="fa fa-bar-chart"></i>Test - {{item.widget_id}}</h3>
                                <div class="box-tools">
                                    <div class="btn btn-box-tool"><grid-item-link :data-id="item.i" :index="item.i" state="remove" @remove-item="removeItem(item)"></grid-link></div>
                                </div>
                            </div>
                            <div class="box-body">
                                <p>Description - {{item.widget_id}}</p>
                            </div>
                        </grid-item>
                    </grid-layout>
                </div>
            </div>
        </div>
        <div class="box box-solid">
            <div class="box-header with-border">
                <h3 class="box-title"><?= __('Available Widgets');?></h3>
            </div>
            <div class="box-body">
                <ul class="droppable-area">
                    <?php for($i = 0; $i <= 3; $i++): ?>
                    <li class="col-lg-3 col-xs-6">
                        <div class="box box-info box-solid">
                            <div class='box-header with-border'>
                                <h3 class="box-title"><i class="fa fa-bar-chart"></i>Test - <?= $i;?></h3>
                                <div class="box-tools">
                                    <div class="btn btn-box-tool"><grid-item-link data-id="<?= $i;?>" state="add" @add-item="addItem"></grid-link></div>
                                </div>
                            </div>
                            <div class="box-body">
                                <p>Description - <?= $i;?></p>
                            </div>
                        </div>
                    </li>
                    <?php endfor;?>
                </ul>
            </div>
        </div>
    </div>
    <?php
    echo $this->Form->button(__('Submit'), ['class' => 'btn btn-primary']);
    echo $this->Form->end();
    ?>
</section>
