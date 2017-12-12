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
        if (this.layout.length > 0) {

        } else {
            this.index = this.layout.length;
        }
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
        getElementIcon: function(item) {
            let className = 'fa-table';

            return className;
        },
        getLayoutElements: function() {
            // function called in case of editing the dashboard.
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
            this.index++;
        },
        removeItem: function(item) {
            this.layout.splice(this.layout.indexOf(item), 1);
            this.index--;
        }
    }
});
