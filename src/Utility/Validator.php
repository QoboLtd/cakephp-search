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
     * @param mixed[] $data Search data
     * @param mixed[] $user User info
     * @return mixed[]
     */
    public static function validateData(Table $table, array $data, array $user): array
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
     * @param mixed[] $data Criteria values
     * @param mixed[] $fields Searchable fields
     * @return mixed[]
     */
    protected static function validateCriteria(array $data, array $fields): array
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
     * @param mixed[] $data Display field(s) values
     * @param mixed[] $fields Searchable fields
     * @return mixed[]
     */
    protected static function validateDisplayColumns(array $data, array $fields): array
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
     * @param mixed[] $fields Searchable fields
     * @param mixed[] $displayColumns Display columns
     * @param \Cake\ORM\Table $table Table instance
     * @return string
     */
    protected static function validateSortByField(string $data, array $fields, array $displayColumns, Table $table): string
    {
        // use sort field if is searchable
        if (in_array($data, $fields)) {
            return $data;
        }

        // set display field as sort field
        $data = $table->getDisplayField();

        // check if display field exists in the database table
        if ($table->getSchema()->getColumn($data)) {
            return $table->aliasField($data);
        }

        // use first display column which exists in the database table
        foreach ($displayColumns as $displayColumn) {
            // remove table prefix
            list(, $displayColumn) = explode('.', $displayColumn);
            if (!$table->getSchema()->getColumn($displayColumn)) {
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
    protected static function validateSortByOrder(string $data): string
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
    protected static function validateAggregator(string $data): string
    {
        $options = array_keys(Options::getAggregators());
        if (!in_array($data, $options)) {
            $data = Options::DEFAULT_AGGREGATOR;
        }

        return $data;
    }
}
