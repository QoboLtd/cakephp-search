<?php
namespace Search\Utility;

use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\ORM\Table;
use Search\Event\EventName;

final class Options
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
    protected static $searchableAssociations = ['manyToOne'];

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
    protected static $basicSearchFieldTypes = ['string', 'text', 'textarea', 'related', 'email', 'url', 'phone'];

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
     * Returns private shared status.
     *
     * @return string
     */
    public static function getPrivateSharedStatus()
    {
        return static::SHARED_STATUS_PRIVATE;
    }

    /**
     * Getter method for default sql sort by order.
     *
     * @return string
     */
    public static function getDefaultSortByOrder()
    {
        return static::DEFAULT_SORT_BY_ORDER;
    }

    /**
     * Getter method for default sql aggragator.
     *
     * @return string
     */
    public static function getDefaultAggregator()
    {
        return static::DEFAULT_AGGREGATOR;
    }

    /**
     * Searchable associations getter.
     *
     * @return array
     */
    public static function getSearchableAssociations()
    {
        return static::$searchableAssociations;
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
        return static::$basicSearchFieldTypes;
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
     * Return Table's listing fields.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @return array
     */
    public static function getListingFields(Table $table)
    {
        $result = [];

        if (method_exists($table, 'getListingFields') && is_callable([$table, 'getListingFields'])) {
            $result = $table->getListingFields();
        }

        if (empty($result)) {
            $event = new Event((string)EventName::MODEL_SEARCH_DISPLAY_FIELDS(), Validator::class, [
                'table' => $table
            ]);
            EventManager::instance()->dispatch($event);

            $result = $event->result;
        }

        if (empty($result)) {
            $result[] = $table->getPrimaryKey();
            $result[] = $table->getDisplayField();
            foreach (static::$defaultDisplayFields as $field) {
                $result[] = $field;
            }

            foreach ($result as $k => $field) {
                if ($table->hasField($field)) {
                    $result[$k] = $table->aliasField($field);
                    continue;
                }

                unset($result[$k]);
            }
        }

        if (!is_array($result)) {
            $result = (array)$result;
        }

        $skippedDisplayFields = [];
        foreach (static::$skipDisplayFields as $field) {
            $skippedDisplayFields[] = $table->aliasField($field);
        }

        // skip display fields
        $result = array_diff($result, $skippedDisplayFields);

        // reset numeric indexes
        $result = array_values($result);

        return $result;
    }
}
