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
namespace Search\Aggregate;

use Cake\ORM\Query;
use Search\Criteria\Field;

abstract class AbstractAggregate implements AggregateInterface
{
    const IDENTIFIER = '';

    /**
     * @var string
     */
    protected $field;

    /**
     * Constructor method.
     *
     * @param \Search\Criteria\Field $field Field
     */
    public function __construct(Field $field)
    {
        $this->field = (string)$field;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(Query $query): Query
    {
        $alias = static::IDENTIFIER . '%%' . $this->field;

        $query->select([$alias => $this->getExpression()]);

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use ($alias) {
            return $results->map(function ($row) use ($alias) {
                $field = sprintf('%s(%s)', static::IDENTIFIER, $this->field);
                $row[$field] = $row[$alias];
                unset($row[$alias]);

                return $row;
            });
        });

        return $query;
    }

    /**
     * Aggregate field check.
     *
     * @param string $field Field name
     * @return bool
     */
    public static function isAggregate(string $field): bool
    {
        return 1 === preg_match(AggregateInterface::AGGREGATE_PATTERN, $field);
    }

    /**
     * Extract aggregate type from field's name.
     *
     * @param string $field Field name
     * @return string
     */
    public static function extractAggregate(string $field): string
    {
        preg_match(AggregateInterface::AGGREGATE_PATTERN, $field, $matches);

        return array_key_exists(1, $matches) ? $matches[1] : '';
    }

    /**
     * Extract field name from aggregated field.
     *
     * @param string $field Field name
     * @return string
     */
    public static function extractFieldName(string $field): string
    {
        preg_match(AggregateInterface::AGGREGATE_PATTERN, $field, $matches);

        return array_key_exists(2, $matches) ? $matches[2] : '';
    }
}
