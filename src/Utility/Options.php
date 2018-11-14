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

use Cake\Datasource\RepositoryInterface;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\ORM\Table;
use Search\Event\EventName;

class Options
{
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
    protected static $basicFieldTypes = ['string', 'text', 'textarea', 'related', 'email', 'url', 'phone', 'integer'];

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
     * @return mixed[]
     */
    public static function getSearchableAssociations(): array
    {
        return static::$associations;
    }

    /**
     * Getter method for sql sort by order options.
     *
     * @return mixed[]
     */
    public static function getSortByOrders(): array
    {
        return static::$sortByOrders;
    }

    /**
     * Getter method for sql aggregator options.
     *
     * @return mixed[]
     */
    public static function getAggregators(): array
    {
        return static::$aggregators;
    }

    /**
     * Basic search allowed field types getter.
     *
     * @return mixed[]
     */
    public static function getBasicSearchFieldTypes(): array
    {
        return static::$basicFieldTypes;
    }

    /**
     * Search options getter.
     *
     * @return mixed[]
     */
    public static function get(): array
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
     * @param \Cake\Datasource\RepositoryInterface $table Table instance
     * @return mixed[]
     */
    public static function getDefaults(RepositoryInterface $table): array
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
     * @param \Cake\Datasource\RepositoryInterface $table Table instance
     * @return mixed[]
     */
    public static function getListingFields(RepositoryInterface $table): array
    {
        // broadcast event to fetch display fields
        $event = new Event((string)EventName::MODEL_SEARCH_DISPLAY_FIELDS(), null, [
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
     * @param \Cake\Datasource\RepositoryInterface $table Table instance
     * @return mixed[]
     */
    protected static function getDefaultDisplayFields(RepositoryInterface $table): array
    {
        /** @var \Cake\ORM\Table */
        $table = $table;

        $result = (array)$table->getPrimaryKey();
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
     * @param \Cake\Datasource\RepositoryInterface $table Table instance
     * @return mixed[]
     */
    protected static function getSkippedDisplayFields(bool $aliased = false, RepositoryInterface $table): array
    {
        /** @var \Cake\ORM\Table */
        $table = $table;

        if (! $aliased) {
            return static::$skipDisplayFields;
        }

        $result = [];
        foreach (static::$skipDisplayFields as $field) {
            $result[] = $table->aliasField($field);
        }

        return $result;
    }
}
