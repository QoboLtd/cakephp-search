var dashboard = dashboard || {};

(function ($) {
    /**
     * Dashboard Logic.
     *
     * @param {object} options configuration options
     */
    function Dashboard(options)
    {
        this.formId = options.hasOwnProperty('formId') ? options.formId : '#dashboardForm';
        this.droppableId = options.hasOwnProperty('droppableId') ? options.droppableId : '.droppable-area';
        this.containerId = options.hasOwnProperty('containerId') ? options.containerId : '.savetrue';
        this.targetId = options.hasOwnProperty('targetId') ? options.targetId : '.droppable';
    }

    /**
     * Initialize method.
     *
     * @return {undefined}
     */
    Dashboard.prototype.init = function () {
        that = this;

        $(this.droppableId).sortable({
            placeholder: "sort-highlight",
            connectWith: this.droppableId,
            forcePlaceholderSize: true,
            zIndex: 999999
        });

        $(this.droppableId).disableSelection();

        // that = this;
        $(this.formId).submit(function (e) {
            that._generateInputs();

            return true;
        });

        $('#dashboardForm').on('click', '.widget-tab a', function(){
            //Remove active class from all tab-pane
            $('#dashboardForm .tab-content .tab-pane').each(function(){
                $(this).removeClass('active');
            });

            var tabId = $(this).attr("href");
            //Add active class to the selected tab-pane
            $('#dashboardForm .tab-content ' + tabId).addClass('active');
        })
    };

    /**
     * Generate related saved searches inputs.
     *
     * @return {undefined}
     */
    Dashboard.prototype._generateInputs = function () {
        that = this;

        $(that.containerId).each(function (i, c) {
            $(c).find(that.targetId).each(function (k, e) {
                $form = $(that.formId);
                $form.append(
                    $('<input>')
                       .attr('type', 'hidden')
                       .attr('name', 'widgets[widget_id][]').val($(e).data('id'))
                );
                $form.append(
                    $('<input>')
                        .attr('type','hidden')
                        .attr('name','widgets[widget_type][]').val($(e).data('type'))
                );
                $form.append(
                    $('<input>')
                       .attr('type', 'hidden')
                       .attr('name', 'widgets[row][]').val(k)
                );
                $form.append(
                    $('<input>')
                       .attr('type', 'hidden')
                       .attr('name', 'widgets[column][]').val($(c).data('column'))
                );
            });
        });
    };

    dashboard = new Dashboard({
        formId: '#dashboardForm',
        droppableId: '.droppable-area',
        containerId: '.savetrue',
        targetId: '.droppable'
    });

    dashboard.init();

})(jQuery);
