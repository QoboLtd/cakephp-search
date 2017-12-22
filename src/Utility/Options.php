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
namespace Search\Utility;

use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\ORM\Table;
use Search\Event\EventName;

class Options
{
    /**
     * Private shared status value
     */
    const SHARED_STATUS_PRIVATE = 'private';

    /**
     * Default sql order by direction
     */
    const DEFAULT_SORT_BY_ORDER = 'desc';

    /**
     * Default sql aggregator
     */
    const DEFAULT_AGGREGATOR = 'AND';

    /**
     * Searchable associations list
     *
     * @var array
     */
    protected static $associations = ['manyToOne'];

    /**
     * Basic search default fields
     *
     * @var array
     */
    protected static $defaultDisplayFields = ['modified', 'created'];

    /**
     * Filter basic search allowed field types
     *
     * @var array
     */
    protected static $basicFieldTypes = ['string', 'text', 'textarea', 'related', 'email', 'url', 'phone'];

    /**
     * Search sort by order options.
     *
     * @var array
     */
    protected static $sortByOrders = [
        'asc' => 'Ascending',
        'desc' => 'Descending'
    ];

    /**
     * Search aggregator options.
     *
     * @var array
     */
    protected static $aggregators = [
        'AND' => 'Match all filters',
        'OR' => 'Match any filter'
    ];

    /**
     * List of display fields to be skipped.
     *
     * @var array
     */
    protected static $skipDisplayFields = ['id'];

    /**
     * Searchable associations getter.
     *
     * @return array
     */
    public static function getSearchableAssociations()
    {
        return static::$associations;
    }

    /**
     * Getter method for sql sort by order options.
     *
     * @return string
     */
    public static function getSortByOrders()
    {
        return static::$sortByOrders;
    }

    /**
     * Getter method for sql aggregator options.
     *
     * @return string
     */
    public static function getAggregators()
    {
        return static::$aggregators;
    }

    /**
     * Basic search allowed field types getter.
     *
     * @return array
     */
    public static function getBasicSearchFieldTypes()
    {
        return static::$basicFieldTypes;
    }

    /**
     * Search options getter.
     *
     * @return array
     */
    public static function get()
    {
        $result = [
            'sortByOrder' => static::$sortByOrders,
            'aggregators' => static::$aggregators
        ];

        return $result;
    }

    /**
     * Default search options.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @return array
     */
    public static function getDefaults(Table $table)
    {
        $result['display_columns'] = static::getListingFields($table);
        $result['sort_by_field'] = current($result['display_columns']);
        $result['sort_by_order'] = static::DEFAULT_SORT_BY_ORDER;
        $result['aggregator'] = static::DEFAULT_AGGREGATOR;

        return $result;
    }

    /**
     * Current table display fields getter.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @return array
     */
    public static function getListingFields(Table $table)
    {
        // broadcast event to fetch display fields
        $event = new Event((string)EventName::MODEL_SEARCH_DISPLAY_FIELDS(), Validator::class, [
            'table' => $table
        ]);
        EventManager::instance()->dispatch($event);

        $result = (array)$event->result;

        if (empty($result)) {
            $result = static::getDefaultDisplayFields($table);
        }

        $result = array_diff($result, static::getSkippedDisplayFields(true, $table));

        // reset numeric indexes
        return array_values($result);
    }

    /**
     * Default display fields getter.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @return array
     */
    protected static function getDefaultDisplayFields(Table $table)
    {
        $result = [];

        array_push($result, $table->getPrimaryKey());
        array_push($result, $table->getDisplayField());

        $result = array_merge($result, static::$defaultDisplayFields);

        // remove virtual fields
        foreach ($result as $k => $field) {
            if (!$table->hasField($field)) {
                unset($result[$k]);
            }
        }

        // alias fields
        foreach ($result as $k => $field) {
            $result[$k] = $table->aliasField($field);
        }

        return $result;
    }

    /**
     * Skipped display fields getter.
     * To alias the fields you need to set $aliased flag
     * to true and pass the table instance.
     *
     * @param bool $aliased Alias flag
     * @param \Cake\ORM\Table $table Table instance
     * @return array
     */
    protected static function getSkippedDisplayFields($aliased = false, $table = null)
    {
        $aliased = (bool)$aliased;

        if (!$aliased) {
            return static::$skipDisplayFields;
        }

        if (!$table instanceof Table) {
            return static::$skipDisplayFields;
        }

        $result = [];
        foreach (static::$skipDisplayFields as $field) {
            $result[] = $table->aliasField($field);
        }

        return $result;
    }
}
