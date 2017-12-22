var GridLayout = VueGridLayout.GridLayout;
var GridItem = VueGridLayout.GridItem;

new Vue({
    el: "#grid-app",
    components: {
        GridLayout,
        GridItem,
    },
    data: {
        targetElement: '#dashboard-options',
        dashboard:[],
        elements: [],
        layout: [],
        index:0,
        token: api_token // getting token from global variable into vue app.
    },
    beforeMount: function() {
        this.getGridElements();

        this.getLayoutElements();
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
        getElementBackground: function(item) {
            let colorClass = 'box-info';

            if (!item.hasOwnProperty('type')) {
                return colorClass;
            }

            switch(item.type) {
                case 'report':
                    colorClass = 'box-success';
                    break;
                case 'app':
                    colorClass = 'box-warning';
                    break;
                case 'saved_search':
                    colorClass = 'box-info';
                    break;
            }

            return colorClass;
        },
        getElementIcon: function(item) {
            let className = 'fa-table';

            if (!item.hasOwnProperty('type')) {
                return className;
            }

            switch(item.type) {
                case 'report':
                    className = 'fa-pie-chart';
                    break;
                case 'app':
                    className = 'fa-cube';
                    break;
            }

            return className;
        },
        getLayoutElements: function() {
            let gridLayout = [];

            if (typeof grid_layout !== undefined ) {
                gridLayout = grid_layout;
                this.layout = JSON.parse(gridLayout);
            }

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
            this.index++;
            let element = {
                x: 0,
                y: 0,
                w: 2,
                h: 2,
                i: this.index + "",
                draggable: true,
            };

            let layoutElement = Object.assign({}, element, item);
            this.layout.push(layoutElement);
        },
        removeItem: function(item) {
            this.layout.splice(this.layout.indexOf(item), 1);
            this.index--;
        }
    }
});
