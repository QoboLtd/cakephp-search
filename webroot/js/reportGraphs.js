;(function ($, document, window) {
    'use strict';

    /**
     * Chart initializer.
     */
    function Newchart(chart)
    {
        this.id = chart.id;
        this.type = chart.chart;
        this.options = chart.options;
        this.ajax = chart.ajax;

        this.init();

        return this;
    }

    Newchart.prototype = {

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
                type: 'get',
                dataType: 'json',
                contentType: 'application/json',
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
                        type : "pie",
                        data : {
                            datasets : [
                                {
                                    data : parseData,
                                    backgroundColor : this.getColor(parseData.length)
                                }
                            ],
                            labels : parseLabel
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
                        type : "bar",
                        data : {
                            datasets : [
                                {
                                    data : parseData,
                                    backgroundColor : this.getColor(parseData.length)
                                }
                            ],
                            labels : parseLabel
                        },
                        options : {
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
            var colorGradients = ["#FF8A8A","#FF86E3","#FF86C2","#FE8BF0","#EA8DFE","#DD88FD","#AD8BFE","#FF9797","#FF97E8","#FF97CB","#FE98F1","#ED9EFE","#E29BFD","#B89AFE","#FFA8A8","#FFACEC","#FFA8D3","#FEA9F3","#EFA9FE","#E7A9FE","#C4ABFE","#FFBBBB","#FFACEC","#FFBBDD","#FFBBF7","#F2BCFE","#EDBEFE","#D0BCFE","#FF4AFF","#DD75DD","#C269FE","#AE70ED","#A095EE","#7BA7E1","#57BCD9","#FF86FF","#E697E6","#CD85FE","#C79BF2","#B0A7F1","#8EB4E6","#7BCAE1","#FFA4FF","#EAA6EA","#D698FE","#CEA8F4","#BCB4F3","#A9C5EB","#8CD1E6","#FFBBFF","#EEBBEE","#DFB0FF","#DBBFF7","#CBC5F5","#BAD0EF","#A5DBEB","#8C8CFF","#99C7FF","#99E0FF","#63E9FC","#74FEF8","#62FDCE","#72FE95","#9999FF","#99C7FF","#A8E4FF","#75ECFD","#92FEF9","#7DFDD7","#8BFEA8","#AAAAFF","#A8CFFF","#BBEBFF","#8CEFFD","#A5FEFA","#8FFEDD","#A3FEBA","#EAEA8A","#F7DE00","#FFD34F","#FFBE28","#FFCE73","#FFBB7D","#FFBD82","#EEEEA2","#FFE920","#FFDD75","#FFC848","#FFD586","#FFC48E","#FFC895","#F1F1B1","#FFF06A","#FFE699","#FFD062","#FFDEA2","#FFCFA4","#FFCEA2","#FF7373","#E37795","#D900D9","#BA21E0","#8282FF","#4FBDDD","#8DC7BB","#FF8E8E","#E994AB","#FF2DFF","#CB59E8","#9191FF","#67C7E2","#A5D3CA","#FFA4A4","#EDA9BC","#F206FF","#CB59E8","#A8A8FF","#8ED6EA","#C0E0DA"];

            var num = Math.floor(Math.random() * (colorGradients.length - 20) );
            for (let i = 0; i < count ; i++) {
                result.push(colorGradients[num + i])
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
        console.log(data)
        var id = data.id;
        var isVisible = (!$('a[href="#' + id + '"]').data('toggle') || $('#' + id).hasClass('active'));
        if (isVisible) {
            new Newchart(data);
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
        new Newchart(data);
        charts.push('#' + data.id);
    });

})(jQuery, document, window);
