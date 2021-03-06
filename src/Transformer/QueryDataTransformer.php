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
namespace Qobo\Search\Transformer;

use Cake\ORM\Query;
use Cake\Utility\Hash;
use Qobo\Search\Aggregate\AbstractAggregate;
use Qobo\Search\Criteria\Aggregate;
use Qobo\Search\Criteria\Conjunction;
use Qobo\Search\Criteria\Criteria;
use Qobo\Search\Criteria\Direction;
use Qobo\Search\Criteria\Field;
use Qobo\Search\Criteria\Filter;
use Qobo\Search\Criteria\OrderBy;
use Webmozart\Assert\Assert;

/**
 * This class is responsible for transforming a query and its options into search value objects.
 */
final class QueryDataTransformer
{
    private const FILTER_MAP = [
        'is' => \Qobo\Search\Filter\Equal::class,
        '=' => \Qobo\Search\Filter\Equal::class,
        'is_not' => \Qobo\Search\Filter\NotEqual::class,
        '!=' => \Qobo\Search\Filter\NotEqual::class,
        'greater' => \Qobo\Search\Filter\Greater::class,
        '>' => \Qobo\Search\Filter\Greater::class,
        'less' => \Qobo\Search\Filter\Less::class,
        '<' => \Qobo\Search\Filter\Less::class,
        'contains' => \Qobo\Search\Filter\Contains::class,
        'not_contains' => \Qobo\Search\Filter\NotContains::class,
        'starts_with' => \Qobo\Search\Filter\StartsWith::class,
        'ends_with' => \Qobo\Search\Filter\EndsWith::class,
    ];

    private const AGGREGATE_MAP = [
        'count' => \Qobo\Search\Aggregate\Count::class,
        'avg' => \Qobo\Search\Aggregate\Average::class,
        'sum' => \Qobo\Search\Aggregate\Sum::class,
        'max' => \Qobo\Search\Aggregate\Maximum::class,
        'min' => \Qobo\Search\Aggregate\Minimum::class,
    ];

    private $group;

    /**
     * Order by conditions
     * @var \Qobo\Search\Criteria\OrderBy[]
     */
    private $order = [];
    private $conjunction;
    private $select = [];
    private $criteria = [];

    /**
     * Transforms query and options into search value objects.
     *
     * @param \Cake\ORM\Query $query Query
     * @param mixed[] $options Query options
     */
    private function __construct(Query $query, array $options)
    {
        $this->setConjunction($options);
        $this->setGroup($query);
        $this->setOrder($query);
        $this->setSelect($query);
        $this->setCriteria($query, $options);
    }

    /**
     * Transforms query and options into search value objects.
     *
     * @param \Cake\ORM\Query $query Query
     * @param mixed[] $options Query options
     * @return \Qobo\Search\Transformer\QueryDataTransformer
     */
    public static function transform(Query $query, array $options): QueryDataTransformer
    {
        return new QueryDataTransformer($query, $options);
    }

    /**
     * Conjunction getter.
     *
     * @return \Qobo\Search\Criteria\Conjunction|null
     */
    public function getConjunction(): ?Conjunction
    {
        return $this->conjunction;
    }

    /**
     * Conjunction setter.
     *
     * @param mixed[] $options Query options
     * @return void
     */
    private function setConjunction(array $options): void
    {
        $conjunction = Hash::get($options, 'conjunction');
        if (null !== $conjunction) {
            $this->conjunction = new Conjunction($conjunction);
        }
    }

    /**
     * Group-by getter.
     *
     * @return \Qobo\Search\Criteria\Field|null
     */
    public function getGroup(): ?Field
    {
        return $this->group;
    }

    /**
     * Group-by setter.
     *
     * @param \Cake\ORM\Query $query Query
     * @return void
     */
    private function setGroup(Query $query): void
    {
        $group = $query->clause('group');
        Assert::isArray($group);
        if ([] === $group) {
            return;
        }

        Assert::keyExists($group, 0);

        $this->group = new Field($group[0]);
    }

    /**
     * Order-by getter.
     *
     * @return \Qobo\Search\Criteria\OrderBy[]
     */
    public function getOrder(): array
    {
        return $this->order;
    }

    /**
     * Order-by setter.
     *
     * @param \Cake\ORM\Query $query Query
     * @return void
     */
    private function setOrder(Query $query): void
    {
        $order = $query->clause('order');
        if (null === $order) {
            return;
        }
        Assert::isInstanceOf($order, \Cake\Database\Expression\OrderByExpression::class);

        $order->iterateParts(function ($direction, $field) {
            $this->order[] = new OrderBy(new Field($field), new Direction($direction));
        });
    }

    /**
     * Select fields getter.
     *
     * @return \Qobo\Search\Criteria\Field[]
     */
    public function getSelect(): array
    {
        return $this->select;
    }

    /**
     * Select fields setter.
     *
     * @param \Cake\ORM\Query $query Query
     * @return void
     */
    private function setSelect(Query $query): void
    {
        $items = array_filter($query->clause('select'), function ($item) {
            return ! AbstractAggregate::isAggregate($item);
        });

        array_map(function ($item) {
            $this->select[] = new Field($item);
        }, $items);
    }

    /**
     * Criteria getter.
     *
     * @return \Qobo\Search\Criteria\Criteria[]
     */
    public function getCriteria(): array
    {
        return $this->criteria;
    }

    /**
     * Criteria setter.
     *
     * @param \Cake\ORM\Query $query Query
     * @param mixed[] $options Query options
     * @return void
     */
    private function setCriteria(Query $query, array $options): void
    {
        $having = [];
        foreach (Hash::get($options, 'data', []) as $data) {
            Assert::isMap($data);
            Assert::keyExists($data, 'field');

            $fieldName = AbstractAggregate::isAggregate($data['field']) ?
                AbstractAggregate::extractFieldName($data['field']) :
                $data['field'];
            $criteria = Criteria::create(new Field($fieldName));

            if (array_key_exists('operator', $data)) {
                Assert::keyExists($data, 'value');
                $criteria->setFilter(new Filter(self::getFilterClass($data['operator']), $data['value']));
            }

            if (AbstractAggregate::isAggregate($data['field'])) {
                $aggregateClass = self::getAggregateClass(AbstractAggregate::extractAggregate($data['field']));
                $criteria->setAggregate(new Aggregate($aggregateClass));
                $having[] = $data['field'];
            }

            $this->criteria[] = $criteria;
        }

        $aggregates = (array)array_filter($query->clause('select'), function ($item) use ($having) {
            return AbstractAggregate::isAggregate($item) && ! in_array($item, $having);
        });

        // adding aggregate remainders
        foreach ($aggregates as $aggregate) {
            $criteria = Criteria::create(new Field(AbstractAggregate::extractFieldName($aggregate)));
            $aggregateClass = self::getAggregateClass(AbstractAggregate::extractAggregate($aggregate));
            $criteria->setAggregate(new Aggregate($aggregateClass));
            $this->criteria[] = $criteria;
        }
    }

    /**
     * Aggregate class getter.
     *
     * @param string $aggregate Aggregate type
     * @return string
     */
    private static function getAggregateClass(string $aggregate): string
    {
        return array_key_exists(strtolower($aggregate), self::AGGREGATE_MAP) ?
            self::AGGREGATE_MAP[strtolower($aggregate)] :
            $aggregate;
    }

    /**
     * Filter class getter.
     *
     * @param string $filter Filter type
     * @return string
     */
    private static function getFilterClass(string $filter): string
    {
        return array_key_exists(strtolower($filter), self::FILTER_MAP) ?
            self::FILTER_MAP[strtolower($filter)] :
            $filter;
    }
}
