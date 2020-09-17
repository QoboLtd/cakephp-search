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
namespace Qobo\Search\Criteria;

/**
 * This class acts as a value object for search criteria.
 */
final class Criteria
{
    /**
     * @var \Qobo\Search\Criteria\Field
     */
    private $field;

    /**
     * @var \Qobo\Search\Criteria\Filter
     */
    private $filter = null;

    /**
     * @var \Qobo\Search\Criteria\Aggregate
     */
    private $aggregate = null;

    /**
     * Create a new Criteria.
     *
     * @param \Qobo\Search\Criteria\Field $field Field
     * @return void
     */
    private function __construct(Field $field)
    {
        $this->field = $field;
    }

    /**
     * Create a new Criteria.
     *
     * @param \Qobo\Search\Criteria\Field $field Field
     * @return \Qobo\Search\Criteria\Criteria
     */
    public static function create(Field $field): Criteria
    {
        return new Criteria($field);
    }

    /**
     * Field getter.
     *
     * @return \Qobo\Search\Criteria\Field
     */
    public function field(): Field
    {
        return $this->field;
    }

    /**
     * Add Filter
     *
     * @param \Qobo\Search\Criteria\Filter $filter Filter object
     * @return void
     */
    public function setFilter(Filter $filter): void
    {
        $this->filter = $filter;
    }

    /**
     * Search filter getter.
     *
     * @return \Qobo\Search\Criteria\Filter|null
     */
    public function filter(): ?Filter
    {
        return $this->filter;
    }

    /**
     * Add Aggregate
     *
     * @param \Qobo\Search\Criteria\Aggregate $aggregate Aggregate object
     * @return void
     */
    public function setAggregate(Aggregate $aggregate): void
    {
        $this->aggregate = $aggregate;
    }

    /**
     * Search aggregate getter.
     *
     * @return \Qobo\Search\Criteria\Aggregate|null
     */
    public function aggregate(): ?Aggregate
    {
        return $this->aggregate;
    }
}
