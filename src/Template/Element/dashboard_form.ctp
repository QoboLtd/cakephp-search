 <div id="grid-app">
        <?= $this->Form->input('options', ['type' => 'hidden', 'id' => 'dashboard-options', 'value' => null]);?>
        <div class="box box-solid">
            <div class="box-body">
                <div class="box-header">
                    <h3 class="box-title"><?= __('Widgets') ?></h3>
                </div>
                <div class="box-body" style="border:1px dashed #d3d3d3;">
                    <grid-layout
                        :layout="layout"
                        :row-height="50"
                        :vertical-compact="false"
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
                                   class="box box-solid"
                                   v-bind:class="getElementBackground(item)"
                        >
                            <div class='box-header with-border'>
                                <h3 class="box-title"><i class="fa" v-bind:class="getElementIcon(item)"></i> {{item.data.model}}</h3>
                                <div class="box-tools">
                                    <div class="btn btn-box-tool"><a href="#" @click="removeItem(item)"><i class='fa fa-minus-circle'></i></a></div>
                                </div>
                            </div>
                            <div class="box-body">
                                <p>{{item.data.name}}</p>
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
            
            <div class="row">
                <div class="col-md-2">
                    <ul class="nav nav-tabs nav-stacked">
                        <li v-for="type in widgetTypes">
                            <a :href="'#' + type" data-toggle="tab">{{type}}</a>
                        </li>
                    </ul>
                </div>

                <div class="col-md-10">
                    <div class="tab-content">                    
                        <div role="tabpanel" class="tab-pane" v-for="type in widgetTypes" :id="type">
                            <div class="box-body">
                                <ul class="droppable-area">
                                    <li class="col-lg-3 col-xs-6" v-for="item in elements" v-if="item.type == type">
                                        <div class="box box-solid" v-bind:class="getElementBackground(item)">
                                            <div class='box-header with-border'>
                                                <h3 class="box-title"><i class="fa" v-bind:class="getElementIcon(item)"></i> {{item.data.model}}</h3>
                                                <div class="box-tools">
                                                    <div class="btn btn-box-tool"><a href="#" @click="addItem(item)"><i class='fa fa-plus-circle'></i></a></div>
                                                </div>
                                            </div>
                                            <div class="box-body">
                                                <p>{{item.data.name}}</p>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>      
                    </div>                
                </div>
            </div>
        </div>
    </div>
