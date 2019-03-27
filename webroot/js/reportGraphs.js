;(function ($, document, window) {
    'use strict';

    /**
     * Chart initializer.
     */
    function Chartjs(chart)
    {
        this.id = chart.id;
        this.type = chart.chart;
        this.options = chart.options;
        this.ajax = chart.ajax;

        this.init();

        return this;
    }

    Chartjs.prototype = {

        /**
         * Initialize method.
         *
         * @return {undefined}
         */
        init: function () {
            if (!$.isEmptyObject(this.options.dataChart)) {
                this.draw();
            } else {
                this.getData();
            }
        },

        /**
         * Draw method.
         *
         * @return {undefined}
         */
        draw: function () {
            switch (this.type) {
                case 'barChart':
                case 'donutChart':
                case 'lineChart':
                case 'polarArea':
                case 'pieChart':
                case 'horizontalBar':
                    var ctx = document.getElementById("canvas_" + this.id).getContext('2d');
                    var myChart = new Chart(ctx, this.options.dataChart);
                    break;
                case 'knobChart':
                    $('.knob-graph').knob({
                        'width': 85,
                        'height': 85,
                        'linecap': 'round',
                        'thickness': '.2',
                        'readOnly': true,
                        'cursor': false,
                        'draw': function () {
                            var colorGradients = [
                                '#05497d', '#043b64', '#032c4c', //blue
                                '#1e3302', '#2c4c03', '#3b6404', '#497d05', '#579606', '#66ae07', '#74c708', //green
                                '#a7a60e', '#bfbd0f', '#d6d411', //yellow
                                '#a70e0f', '#bf0f11', '#d61113', '#ec1517', //red
                            ];
                            var randomColor = Math.floor(Math.random() * colorGradients.length);
                            this.o.fgColor = colorGradients[randomColor];
                        }
                    });
                    break;
                case 'funnelChart':
                    this.options.dataChart.sort(function (a, b) {
                        return (a.value < b.value) ? 1 : ((b.value < a.value) ? -1 : 0);
                    });

                    var d3chart = new D3Funnel('#' + this.id);
                    d3chart.draw(this.options.dataChart, {
                        block: {
                            dynamicHeight: true
                        }
                    });
                    break;
            }
        },

        getData: function () {
            var that = this;

            if (!this.ajax) {
                console.log('No ajax information, cannot fetch chart data.');

                return;
            }

            var placeholder = this.getPlaceholder();
            // add loading placeholder
            $('#' + this.id).append(placeholder.element);

            $.ajax({
                url: this.ajax.url,
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    Authorization: 'Bearer ' + this.ajax.token
                },
                success: function (data, textStatus, jqXHR) {
                    // remove placeholder
                    $('#' + placeholder.id).remove();
                    that.options.dataChart = that.normalizeData(data.data);
                    that.draw();
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR);
                    console.log(textStatus);
                    console.log(errorThrown);
                }
            });
        },

        normalizeData: function (data) {
            var that = this;
            let parseData = [];
            let parseLabel = [];
            let label;
            let num;

            switch (this.type) {
                case 'funnelChart':
                    data.forEach(function (v, k) {
                        var label = data[k][that.options.xkey[0]];
                        var value = data[k][that.options.ykeys[0]];
                        data[k].label = $('<div>').html(label).text();
                        data[k].value = parseInt(value);
                    });
                    break;
                case 'donutChart':
                    label = that.options.xkey[0];
                    num = that.options.ykeys[0];
                    data.forEach(function (v, k) {
                        parseLabel.push($('<div>').html(data[k][label]).text());
                        parseData.push($('<div>').html(data[k][num]).text());
                    });
                    data = {
                        type: "pie",
                        data: {
                            datasets: [{
                                data: parseData,
                                backgroundColor: this.getColor(parseData.length)
                            }],
                            labels: parseLabel
                        }
                    };

                    break;
                case 'barChart':
                    label = that.options.xkey[0];
                    num = that.options.ykeys[0];
                    data.forEach(function (v, k) {
                        parseLabel.push($('<div>').html(data[k][label]).text());
                        parseData.push($('<div>').html(data[k][num]).text());
                    });
                    data = {
                        type: "bar",
                        data: {
                            datasets: [{
                                data: parseData,
                                backgroundColor: this.getColor(parseData.length)
                            }],
                            labels: parseLabel
                        },
                        options: {
                            legend: {
                                display: false
                            },
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        beginAtZero: true
                                    }
                                }]
                            }
                        }
                    };

                    break;
            }

            return data;
        },

        getColor : function (count) {
            var result = [];
            var colorGradients = ["#ff9a00","#ff165d","#f6f7d7","#3ec1d3","#521262","#6639a6","#3490de","#6fe7dd","#a4f6a5","#f1eb9a","#f8a978","#f68787","#e88a1a","#35477d","#a06ee1","#fcd307","#007880","#c7004c","#e3c4a8","#77628c","#5893d4","#30e3ca","#f8f3d4","#ffcfdf","#3f72af","#f73859","#61c0bf","#6639a6","#00e0ff","#d4a5a5","#dde7f2","#55e9bc","#d72323","#ff9a00"];
            // Quick hash function to get a unique number from a string
            let unique = Math.abs(this.id.split("").reduce(function (a, b) {
                a = ((a << 5) - a) + b.charCodeAt(0);

                return a & a
            }, 0)) % colorGradients.length;

            for (let i = 0; i < count; i++) {
                result.push(colorGradients[(unique + i) % colorGradients.length ])
            }

            return result;
        },

        getPlaceholder: function () {
            var result = {};

            // random id
            result.id = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
            result.element = $('<div>', {id: result.id, style: 'height:350px;padding-top:160px;'});
            result.element.html(
                '<p class="lead text-center"><i class="fa fa-refresh fa-spin fa-fw"></i> Processing...</p>'
            );

            return result;
        }
    };

    if (!window.chartsData) {
        console.log('No charts to initialize');

        return;
    }

    var charts = [];
    window.chartsData.forEach(function (data) {
        var id = data.id;
        var isVisible = (!$('a[href="#' + id + '"]').data('toggle') || $('#' + id).hasClass('active'));
        if (isVisible) {
            new Chartjs(data);
            charts.push('#' + data.options.id);
        }
    });

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var chartId = $(e.target).attr('href');

        // chart already initialized
        if (-1 !== charts.indexOf(chartId)) {
            return;
        }

        // get chart data
        var data = $.grep(window.chartsData, function (v) {
            return '#' + v.id === chartId;
        });

        if (!data.length) {
            return;
        }

        data = data[0];
        // init chart
        new Chartjs(data);
        charts.push('#' + data.id);
    });

})(jQuery, document, window);
