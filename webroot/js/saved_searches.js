var saved_searches = saved_searches || {};

(function ($) {

    /**
     * Saved Searches Logic.
     *
     * @param {object} options configuration options
     */
    function SavedSearches(options)
    {
        this.criteriasSelectId = options.hasOwnProperty('criteriasSelectId') ? options.criteriasSelectId : '#savedCriteriasSelect';
        this.criteriasViewId = options.hasOwnProperty('criteriasViewId') ? options.criteriasViewId : '#savedCriteriasView';
        this.criteriasDeleteId = options.hasOwnProperty('criteriasDeleteId') ? options.criteriasDeleteId : '#savedCriteriasDelete';
        this.criteriasCopyId = options.hasOwnProperty('criteriasCopyId') ? options.criteriasCopyId : '#savedCriteriasCopy';

        this.viewPrefixId = options.hasOwnProperty('viewPrefixId') ? options.viewPrefixId : '#view_';
        this.deletePrefixId = options.hasOwnProperty('deletePrefixId') ? options.deletePrefixId : '#delete_';
        this.copyPrefixId = options.hasOwnProperty('copyPrefixId') ? options.copyPrefixId : '#copy_';
    }

    /**
     * Initialize method.
     *
     * @return {undefined}
     */
    SavedSearches.prototype.init = function () {
        this._onCriteriasSelect();
    };

    /**
     * Enable saves search criterias view and delete functionality on click.
     *
     * @return {undefined}
     */
    SavedSearches.prototype._onCriteriasSelect = function () {
        var that = this;

        $(this.criteriasViewId).on('click', function () {
            selElement = $(that.criteriasSelectId).val();
            window.location.href = $(that.viewPrefixId + selElement).attr('href');
        });

        $(this.criteriasDeleteId).on('click', function () {
            selElement = $(that.criteriasSelectId).val();
            $(that.deletePrefixId + selElement).click();
        });

        $(this.criteriasCopyId).on('click', function () {
            selElement = $(that.criteriasSelectId).val();
            $(that.copyPrefixId + selElement).click();
        });
    };

    saved_searches = new SavedSearches({
        criteriasSelectId: '#savedCriteriasSelect',
        criteriasViewId: '#savedCriteriasView',
        criteriasDeleteId: '#savedCriteriasDelete',
        criteriasCopyId: '#savedCriteriasCopy',
        viewPrefixId: '#view_',
        deletePrefixId: '#delete_',
        copyPrefixId: '#copy_'
    });

    saved_searches.init();

})(jQuery);
