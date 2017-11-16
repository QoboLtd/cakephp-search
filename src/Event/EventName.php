<?php
/**
 * Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Search\Event;

use MyCLabs\Enum\Enum;

/**
 * Event Name enum
 */
class EventName extends Enum
{
    const MODEL_DASHBOARDS_GET_REPORTS = 'Search.Report.getReports';
    const MODEL_DASHBOARDS_GET_WIDGETS = 'Search.Dashboards.getWidgets';
    const MODEL_SEARCH_AFTER_FIND = 'Search.Model.Search.afterFind';
    const MODEL_SEARCH_BASIC_SEARCH_FIELDS = 'Search.Model.Search.basicSearchFields';
    const MODEL_SEARCH_CHILD_ITEMS = 'Search.Model.Search.childItemsForParent';
    const MODEL_SEARCH_DISPLAY_FIELDS = 'Search.Model.Search.displayFields';
    const MODEL_SEARCH_SEARCHABLE_FIELDS = 'Search.Model.Search.searchabeFields';
}
