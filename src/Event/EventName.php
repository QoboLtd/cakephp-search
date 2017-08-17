<?php
namespace Search\Event;

use MyCLabs\Enum\Enum;

/**
 * Event Name enum
 */
class EventName extends Enum
{
    const MENU_ACTIONS_SEARCH_VIEW = 'Search.View.View.Menu.Actions';
    const MENU_TOP_DASHBOARD_VIEW = 'Search.Dashboards.View.View.Menu.Top';

    const MODEL_DASHBOARDS_GET_REPORTS = 'Search.Report.getReports';
    const MODEL_DASHBOARDS_GET_WIDGETS = 'Search.Dashboards.getWidgets';
    const MODEL_SEARCH_AFTER_FIND = 'Search.Model.Search.afterFind';
    const MODEL_SEARCH_BASIC_SEARCH_FIELDS = 'Search.Model.Search.basicSearchFields';
    const MODEL_SEARCH_DISPLAY_FIELDS = 'Search.Model.Search.displayFields';
    const MODEL_SEARCH_SEARCHABLE_FIELDS = 'Search.Model.Search.searchabeFields';

    const VIEW_DASHBOARDS_WIDGET_GRID = 'Search.Dashboard.Widget.GridElement';
}
