<div class="box box-solid available-widget" v-bind:class="getElementBackground(item)">
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
