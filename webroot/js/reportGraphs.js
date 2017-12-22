;(function ($, document, window) {
    'use strict';

    /**
     * Chart initializer.
     */
    function Chart(chart)
    {
        this.id = chart.options.element;
        this.type = chart.chart;
        this.options = chart.options;
        this.ajax = chart.ajax;

        this.init();

        return this;
    }

    Chart.prototype = {

        /**
         * Initialize method.
         *
         * @return {undefined}
         */
        init: function () {
            if (0 < this.options.data.length) {
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
            var that = this;

            switch (this.type) {
                case 'barChart':
                    Morris.Bar(this.options);
                    break;
                case 'lineChart':
                    Morris.Line(this.options);
                    break;
                case 'donutChart':
                    Morris.Donut(this.options);
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
                    this.options.data.sort(function (a, b) {
                        return (a.value < b.value) ? 1 : ((b.value < a.value) ? -1 : 0);
                    });

                    var d3chart = new D3Funnel('#' + this.id);
                    d3chart.draw(this.options.data, {
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
                type: 'get',
                dataType: 'json',
                contentType: 'application/json',
                success: function (data, textStatus, jqXHR) {
                    // remove placeholder
                    $('#' + placeholder.id).remove();

                    var chartData = that.normalizeData(data.data);

                    that.options.data = chartData;

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

            switch (this.type) {
                case 'funnelChart':
                case 'donutChart':
                    data.forEach(function (v, k) {
                        var label = data[k][that.options.xkey[0]];
                        var value = data[k][that.options.ykeys[0]];
                        data[k].label = $('<div>').html(label).text();
                        data[k].value = parseInt(value);
                    });
                    break;
                case 'barChart':
                    data.forEach(function (v, k) {
                        var key = that.options.xkey[0];
                        var value = data[k][key];
                        data[k][key] = $('<div>').html(value).text();
                    });
                    break;
            }

            return data;
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
        var id = data.options.element;
        var isVisible = (!$('a[href="#' + id + '"]').data('toggle') || $('#' + id).hasClass('active'));

        // initialize visible charts
        if (isVisible) {
            // init chart
            new Chart(data);
            charts.push('#' + data.options.element);
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
            return '#' + v.options.element === chartId;
        });

        if (!data.length) {
            return;
        }

        data = data[0];
        // init chart
        new Chart(data);
        charts.push('#' + data.options.element);
    });

})(jQuery, document, window);