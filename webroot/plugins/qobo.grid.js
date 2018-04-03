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
        widgetTypes: [],
        searchModules: [],
        layout: [],
        index:0,
        token: api_token // getting token from global variable into vue app.
    },
    beforeMount: function () {
        this.getGridElements();

        this.getLayoutElements();
    },
    mounted: function () {
        this.index = this.layout.length;
    },
    beforeUpdate: function () {
        this.$nextTick(function () {
            this.adjustBoxesHeight();
        });
    },
    watch: {
        // save all the visible options into dashboard var
        layout: {
            handler: function () {
                var that = this;
                this.dashboard = [];

                if (this.layout.length > 0) {
                    this.layout.forEach(function (element) {
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
        getElementBackground: function (item) {
            let colorClass = 'info';

            if (item.hasOwnProperty('color')) {
                 colorClass = item.color;
            }

            return 'box-' + colorClass;
        },
        getElementIcon: function (item) {
            let className = 'cube';
            if ('saved_search' === item.type) {
                console.log(item.data.name);
                console.log(item.icon);
            }

            if (item.hasOwnProperty('icon')) {
                 className = item.icon;
            }

            return 'fa-' + className;
        },
        getLayoutElements: function () {
            let gridLayout = [];

            if (typeof grid_layout !== undefined ) {
                gridLayout = grid_layout;
                this.layout = JSON.parse(gridLayout);
            }

        },
        getGridElements: function () {
            var that = this;
            let types = [];
            let models = [];
            $.ajax({
                type: 'post',
                dataType: 'json',
                url: '/search/widgets/index',
                headers: {
                    'Authorization': 'Bearer ' + this.token
                }
            }).then(function (response) {
                that.elements = response;

                that.elements.forEach(function (element) {
                    if (!types.includes(element.type)) {
                        types.push(element.type);
                    }

                    if (element.type == 'saved_search' && !models.includes(element.data.model)) {
                        models.push(element.data.model);
                    }
                });

                that.widgetTypes = types.sort();
                that.searchModules = models.sort();
            });
        },
        addItem: function (item) {
            let element = {
                x: 0,
                y: this.getLastRow(),
                w: 2,
                h: 2,
                i: this.getUniqueId(),
                draggable: true,
            };

            let layoutElement = Object.assign({}, element, item);
            this.layout.push(layoutElement);
            this.index = this.layout.length;
        },
        removeItem: function (item) {
            this.layout.splice(this.layout.indexOf(item), 1);
            this.index = this.layout.index;
        },
        getUniqueId: function () {
            return '_' + Math.random().toString(36).substr(2, 9);
        },
        getLastRow: function () {
            let last = 0;

            if (!this.layout.length) {
                return last;
            }

            this.layout.forEach(function (element) {
                if (element.y >= last) {
                    last = element.y;
                }
            });

            last++;

            return last;
        },
        camelize: function (str) {
            str = str.replace(/(?:\_|\W)(.)/g, function (match, chr) {
                return ' ' + chr.toUpperCase();
            });

            return str.charAt(0).toUpperCase() + str.slice(1);
        },
        getActiveTab: function (type, defaultValue, cssClass) {
            return cssClass + ' ' + (type == defaultValue ? 'active' : '');
        },
        adjustBoxesHeight: function () {
            var maxHeight = Math.max.apply(null, $("div.available-widget").map(function () {
                return $(this).height();
            }).get());

            $("div.available-widget").height(maxHeight + 5);
        }
    }
});
