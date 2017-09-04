var search_export = search_export || {};

(function ($) {
    'use strict';

    function SearchExport()
    {
    }

    SearchExport.prototype = {

        init: function (options) {
            var that = this;
            $(document).ready(function () {
                $("#search-export-link").exporter({
                    url: options.url,
                    count: options.count,
                    limit: options.limit,
                    token: options.token
                });

                $(document).on("progress.search.export", function (e) {
                    that.progress(e.percent);
                });

                $(document).on("completed.search.export", function (e) {
                    that.completed(e.link, options.completed_message);
                });
            });
        },

        progress: function (percent) {
            percent = Math.floor(percent);
            $("#search-export-report .progress-bar").css("width", percent + "%");
            $("#search-export-report .progress-percent").text(percent + "%");
        },

        completed: function (link, completed_message) {
            $("#search-export-report").removeClass("bg-blue").addClass("bg-green-active");
            $("#search-export-report .info-box-icon .fa").removeClass("fa-spinner fa-pulse").addClass("fa-check");
            this.progress(100);
            $("#search-export-report .progress-status").text(completed_message);
            $("#search-export-link a").attr("href", link);
            $("#search-export-link").removeClass("hidden");
        }
    };

    search_export = new SearchExport();
})(jQuery);
