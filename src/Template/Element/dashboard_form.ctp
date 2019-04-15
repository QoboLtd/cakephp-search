<?= $this->element('dashboard_option') ?>

 <div id="grid-app">
        <?= $this->Form->control('options', ['type' => 'hidden', 'id' => 'dashboard-options', 'value' => null]);?>
        <div class="box box-primary">
            <div class="box-body">
                <div class="box-header">
                    <h3 class="box-title"><?= __('Widgets') ?></h3>
                </div>
                <div class="box-body" style="border:1px dashed #d3d3d3;">
                <div id="templates"></div>
                    <grid-layout
                        :layout="layout"
                        :row-height="50"
                        :vertical-compact="true"
                        :margin="[5, 5]"
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
                                   drag-allow-from=".box-header"

                                   class="box box-solid"
                                   v-bind:class="getElementBackground(item)"
                        >
                            <div class='box-header with-border' id="title12">
                                <h3 class="box-title"><i class="fa" v-bind:class="getElementIcon(item)"></i> {{item.title}}</h3>
                                <div class="box-tools">
                                    <div class="btn btn-box-tool"><a href="#" @click="removeItem(item)"><i class='fa fa-minus-circle'></i></a></div>
                                </div>
                            </div>
                            <div class="box-body">
                                <p>{{item.data.name}}
                                 <span v-if="item.data.inputs" style="float: right">
                                 <button data-toggle="modal" :data-target="'#' + getComponentId(item.data.id)" v-on:click.self.prevent>Options</button>
                                 </span>
                                </p>
                            </div>
                        </grid-item>
                    </grid-layout>
                </div>
            </div>
        </div>
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><?= __('Available Widgets');?></h3>
            </div>

            <div class="row">
                <div class="col-md-2">
                    <ul class="nav nav-tabs nav-stacked">
                        <li v-for="type in widgetTypes" v-bind:class="getActiveTab(type, widgetTypes[0], '')" class="widget-tab">
                            <a :href="'#' + type" data-toggle="tab">{{camelize(type)}}</a>
                        </li>
                    </ul>
                </div>

                <div class="col-md-10">
                    <div class="tab-content">
                        <div role="tabpanel" v-bind:class="getActiveTab(type, widgetTypes[0], 'tab-pane')" v-for="type in widgetTypes" :id="type">
                            <div class="box-body">
                                 <ul class="nav nav-tabs" v-if="type == 'saved_search'">
                                    <li v-for="model in searchModules" v-bind:class="getActiveTab(model, searchModules[0], '')">
                                        <a :href="'#' + model" data-toggle="tab">{{camelize(model)}}</a>
                                    </li>
                                </ul>
                                <div class="tab-content" v-if="type == 'saved_search'">
                                    <div role="tabpanel"  v-bind:class="getActiveTab(model, searchModules[0], 'tab-pane')" v-for="model in searchModules" :id="model">
                                        <ul class="droppable-area">
                                            <li class="col-lg-3 col-xs-6" v-for="item in elements" v-if="item.type == type && item.data.model == model">
                                                <?= $this->element('dashboard_widget') ?>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <ul class="droppable-area" v-if="type != 'saved_search'">
                                    <li class="col-lg-3 col-xs-6" v-for="item in elements" v-if="item.type == type">
                                        <?= $this->element('dashboard_widget') ?>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
