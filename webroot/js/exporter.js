;(function ($, document, window) {
    'use strict';

    /**
     * Exporter plugin.
     */
    function Exporter(element, options)
    {
        this.element = element;
        this.options = options;

        var pages = Math.ceil(this.options.count / this.options.limit);
        this.init(1, pages);
    }

    Exporter.prototype = {

        /**
         * Initialize method
         *
         * @return {undefined}
         */
        init: function (page, pages) {
            var that = this;

            $.ajax({
                url: this.options.url,
                type: 'get',
                data: {
                    page: page,
                    limit: this.options.limit
                },
                dataType: 'json',
                contentType: 'application/json',
                headers: {
                    'Authorization': 'Bearer ' + this.options.token
                },
                success: function (data) {
                    if (data.success) {
                        // next page
                        page++;

                        if (page <= pages) {
                            $.event.trigger({
                                type: "progress.search.export",
                                percent: (page / pages) * 100
                            });
                            that.init(page, pages);
                        } else {
                            $.event.trigger({
                                type: "completed.search.export",
                                link: data.data.path
                            });
                        }
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR);
                    console.log(textStatus);
                    console.log(errorThrown);
                }
            });
        }
    };

    $.fn.exporter = function (options) {
        return this.each(function () {
            new Exporter(this, options);
        });
    };

})(jQuery, document, window);
