var GridLayout = VueGridLayout.GridLayout;
var GridItem = VueGridLayout.GridItem;

var GridLink = Vue.component('grid-item-link',{
    template: `<a href="#" data-id:="dataId">
                    <i class="fa" v-bind:class="icon" @click="toggleItem"></i>
                </a>`,
    props: ['dataId','index', 'state'],
    data: function() {
        return {
            icon: null
        };
    },
    beforeMount: function() {
        this.icon = ('add' == this.state) ? 'fa-plus-circle' : 'fa-minus-circle';
    },
    methods: {
        toggleItem: function() {
            if ('add' == this.state) {
                this.$emit('add-item', this);
            } else if('remove' == this.state) {
                this.$emit('remove-item', this);
            }
        },
    }
});

new Vue({
    el: "#grid-app",
    components: {
        GridLayout,
        GridItem,
        GridLink,
    },
    data: {
        elements: [],
        layout: [],
        index:0,
        token: api_token // getting token from global variable into vue app.
    },
    beforeMount: function() {
        // @TODO:
        // 1. Fetch layout items in case we're editing it.
        // 2. Fetch all items (widgets) that we'd like to add to grid.
        // Summary: set initial data from AJAX to data()
        this.getGridElements();
    },
    mounted: function() {
        this.index = this.layout.length;
    },
    methods: {
        getElementIcon: function(item) {
            let className = 'fa-table';

            if (item.data.type == 'graph') {
                className = 'fa-area-chart';
            }

            return className;
        },
        getGridElements: function() {
            var that = this;

            $.ajax({
                type: 'post',
                dataType: 'json',
                url: '/search/widgets/index',
                headers: {
                    'Authorization': 'Bearer ' + this.token
                }
            }).then(function(response) {
                that.elements = response;
            });
        },
        addItem: function(item) {
            console.log(item);

            let element = {
                x: 0,
                y: 0,
                w: 2,
                h: 2,
                i: this.index + "",
                draggable: true
            };

            this.index++;
            this.layout.push(Object.assign({}, element, item));
        },
        removeItem: function(item) {
            this.layout.splice(this.layout.indexOf(item), 1);
            this.index--;
        }
    }
});
