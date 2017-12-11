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
        targetElement: '#dashboard-options',
        elements: [],
        layout: [],
        dashboard:[],
        index:0,
        token: api_token // getting token from global variable into vue app.
    },
    beforeMount: function() {
        this.getGridElements();
    },
    mounted: function() {
        this.index = this.layout.length;
    },
    watch: {
        // save all the visible options into dashboard var
        layout: {
            handler: function() {
                var that = this;
                this.dashboard = [];

                if(this.layout.length > 0) {
                    console.log('filtering out data');
                    this.layout.forEach(function(element) {
                        that.dashboard.push({
                            i: element.i,
                            h: element.h,
                            w: element.w,
                            x: element.x,
                            y: element.y,
                            id: element.data.id,
                            type: element.type,
                        });
                    });
                }

                $(this.targetElement).val(JSON.stringify(this.dashboard));
            },
            deep: true
        }
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
        getGridElementsById: function(id) {
            var that = this;

            $.ajax({
                url: '/search/dashboard/edit/' + id,
                dataType: 'json',
                type: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + this.token
                }
            }).then(function(response){
                console.log('editing existing layout');
            });
        },
        addItem: function(item) {
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
