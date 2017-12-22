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

use Cake\ORM\Table;
use Search\Utility;
use Search\Utility\Options;

class Validator
{
    /**
     * Base search data validation method.
     *
     * Retrieves current searchable table columns, validates and filters criteria, display columns
     * and sort by field against them. Then validates sort by order againt available options
     * and sets it to the default option if they fail validation.
     *
     * @param \Cake\ORM\Table $table Table instace
     * @param array $data Search data
     * @param array $user User info
     * @return array
     */
    public static function validateData(Table $table, array $data, array $user)
    {
        $fields = Utility::instance()->getSearchableFields($table, $user);
        $fields = array_keys($fields);

        // merge default options
        $data += Options::getDefaults($table);

        if (!empty($data['criteria'])) {
            $data['criteria'] = static::validateCriteria($data['criteria'], $fields);
        }

        $data['display_columns'] = static::validateDisplayColumns($data['display_columns'], $fields);
        $data['sort_by_field'] = static::validateSortByField(
            $data['sort_by_field'],
            $fields,
            $data['display_columns'],
            $table
        );
        $data['sort_by_order'] = static::validateSortByOrder($data['sort_by_order']);
        $data['aggregator'] = static::validateAggregator($data['aggregator']);

        return $data;
    }

    /**
     * Validate search criteria.
     *
     * @param array $data Criteria values
     * @param array $fields Searchable fields
     * @return array
     */
    protected static function validateCriteria(array $data, array $fields)
    {
        foreach (array_keys($data) as $key) {
            if (in_array($key, $fields)) {
                continue;
            }
            unset($data[$key]);
        }

        return $data;
    }

    /**
     * Validate search display field(s).
     *
     * @param array $data Display field(s) values
     * @param array $fields Searchable fields
     * @return array
     */
    protected static function validateDisplayColumns(array $data, array $fields)
    {
        foreach ($data as $k => $v) {
            if (in_array($v, $fields)) {
                continue;
            }
            unset($data[$k]);
        }

        return $data;
    }

    /**
     * Validate search sort by field.
     *
     * @param string $data Sort by field value
     * @param array $fields Searchable fields
     * @param array $displayColumns Display columns
     * @param \Cake\ORM\Table $table Table instance
     * @return string
     */
    protected static function validateSortByField($data, array $fields, array $displayColumns, Table $table)
    {
        // use sort field if is searchable
        if (in_array($data, $fields)) {
            return $data;
        }

        // set display field as sort field
        $data = $table->getDisplayField();

        // check if display field exists in the database table
        if ($table->getSchema()->column($data)) {
            return $table->aliasField($data);
        }

        // use first display column which exists in the database table
        foreach ($displayColumns as $displayColumn) {
            // remove table prefix
            list(, $displayColumn) = explode('.', $displayColumn);
            if (!$table->getSchema()->column($displayColumn)) {
                continue;
            }

            return $table->aliasField($displayColumn);
        }

        // use primary key as a last resort
        return $table->aliasField($table->getPrimaryKey());
    }

    /**
     * Validate search sort by order.
     *
     * @param string $data Sort by order value
     * @return string
     */
    protected static function validateSortByOrder($data)
    {
        $options = array_keys(Options::getSortByOrders());
        if (!in_array($data, $options)) {
            $data = Options::DEFAULT_SORT_BY_ORDER;
        }

        return $data;
    }

    /**
     * Validate search aggregator.
     *
     * @param string $data Aggregator value
     * @return string
     */
    protected static function validateAggregator($data)
    {
        $options = array_keys(Options::getAggregators());
        if (!in_array($data, $options)) {
            $data = Options::DEFAULT_AGGREGATOR;
        }

        return $data;
    }
}
