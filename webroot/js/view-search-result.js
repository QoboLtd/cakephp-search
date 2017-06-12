var view_search_result = view_search_result || {};

(function ($) {
    /**
     * View Search Result Logic.
     *
     * @param {object} options
     */
    function ViewSearchResult()
    {
    }

    /**
     * Initialize method.
     *
     * @return {void}
     */
    ViewSearchResult.prototype.init = function (options) {
        this.url = options.hasOwnProperty('url') ? options.url : null;
        this.extension = options.hasOwnProperty('extension') ? options.extension : null;
        this.token = options.hasOwnProperty('token') ? options.token : null;
        this.table_id = options.hasOwnProperty('table_id') ? options.table_id : null;
        this.columns = options.hasOwnProperty('columns') ? options.columns : null;
        this.sort_by_field = options.hasOwnProperty('sort_by_field') ? options.sort_by_field : 0;
        // set default value, if empty string is passed to the sort_by_field option
        if ('' === this.sort_by_field) {
            this.sort_by_field = 0;
        }
        this.sort_by_order = options.hasOwnProperty('sort_by_order') ? options.sort_by_order : 'asc';
        // set default value, if empty string is passed to the sort_by_order option
        if ('' === this.sort_by_order) {
            this.sort_by_order = 'asc';
        }

        this.datatable();
    };

    /**
     * Initialize datatables.
     *
     * @return {void}
     */
    ViewSearchResult.prototype.datatable = function () {
        var that = this;
        $(this.table_id).DataTable({
            columns: that.columns,
            searching: false,
            processing: true,
            serverSide: true,
            order: [[that.sort_by_field, that.sort_by_order]],
            ajax: {
                url: that.url + '.' + that.extension,
                headers: {
                    'Authorization': 'Bearer ' + that.token
                },
                data: function (d) {
                    d.limit = d.length;
                    d.page = 1 + d.start / d.length;

                    return d;
                },
                dataFilter: function (d) {
                    d = jQuery.parseJSON(d);
                    d.recordsTotal = d.pagination.count;
                    d.recordsFiltered = d.pagination.count;

                    return JSON.stringify(d);
                }
            }
        });
    };

    view_search_result = new ViewSearchResult();

})(jQuery);
