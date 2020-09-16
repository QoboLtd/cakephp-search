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
namespace Qobo\Search\Service;

use Cake\ORM\Association;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Qobo\Search\Criteria\Conjunction;
use Qobo\Search\Criteria\Criteria;
use Qobo\Search\Criteria\Field;
use Qobo\Search\Criteria\OrderBy;
use Webmozart\Assert\Assert;

final class Search
{
    /**
     * Table instance.
     *
     * @var \Cake\ORM\Table
     */
    private $table;

    /**
     * Query instance.
     *
     * @var \Cake\ORM\Query
     */
    private $query;

    /**
     * Search conjunction.
     *
     * @var \Qobo\Search\Criteria\Conjunction
     */
    private $conjunction;

    /**
     * Search criteria list.
     *
     * @var \Qobo\Search\Criteria\Criteria[]
     */
    private $criteria = [];

    /**
     * @var \Qobo\Search\Criteria\Field|null
     */
    private $groupBy = null;

    /**
     * @var \Qobo\Search\Criteria\OrderBy[]
     */
    private $orderBy = [];

    /**
     * @var \Qobo\Search\Criteria\Field[]
     */
    private $select = [];

    /**
     * Constructor method.
     *
     * @param \Cake\ORM\Table $table Table instance
     * @return void
     */
    public function __construct(Table $table)
    {
        $this->table = $table;
        $this->query = $table->query();

        $this->setConjunction(new Conjunction('AND'));
    }

    /**
     * Add group-by to Search.
     *
     * @param \Search\Criteria\Field $field Field
     * @return void
     */
    public function setGroupBy(Field $field): void
    {
        $this->groupBy = $field;
    }

    /**
     * Add order-by to Search.
     *
     * @param \Search\Criteria\OrderBy|\Search\Criteria\OrderBy[] $orderBy OrderBy
     * @return void
     */
    public function setOrderBy($orderBy): void
    {
        if (is_array($orderBy)) {
            Assert::allIsInstanceOf($orderBy, OrderBy::class);
            $this->orderBy = array_merge($this->orderBy, $orderBy);
        } else {
            Assert::isInstanceOf($orderBy, OrderBy::class);
            $this->orderBy[] = $orderBy;
        }
    }

    /**
     * Add criteria to Search.
     *
     * @param \Search\Criteria\Criteria $criteria Criteria object
     * @return void
     */
    public function addCriteria(Criteria $criteria): void
    {
        $this->criteria[] = $criteria;
    }

    /**
     * Add selection field to Search.
     *
     * @param \Search\Criteria\Field $field Field
     * @return void
     */
    public function addSelect(Field $field): void
    {
        $this->select[] = $field;
    }

    /**
     * Add conjunction to Search.
     *
     * @param \Search\Criteria\Conjunction $conjunction Search conjunction
     * @return void
     */
    public function setConjunction(Conjunction $conjunction): void
    {
        $this->conjunction = $conjunction;
    }

    /**
     * Executes search logic.
     *
     * @return \Cake\ORM\Query
     */
    public function execute(): Query
    {
        $this->applySelect();
        $this->applyFilters();
        $this->applyJoins();

        if (null !== $this->groupBy) {
            $this->query->group((string)$this->groupBy);
        }

        foreach ($this->orderBy as $order) {
            $this->query->order([(string)$order->field() => (string)$order->direction()]);
        }

        // adjust where clause conjunction
        $clause = $this->query->clause('where');
        if (null !== $clause) {
            $this->query->where($clause->setConjunction((string)$this->conjunction), [], true);
        }

        return $this->query;
    }

    /**
     * Applies filters to the Query.
     *
     * @return void
     */
    private function applyFilters(): void
    {
        foreach ($this->criteria as $criteria) {
            if (null === $criteria->filter()) {
                continue;
            }

            $filterClass = $criteria->filter()->type();

            $filter = new $filterClass(
                $criteria->field(),
                $criteria->filter()->value(),
                $criteria->aggregate(),
                $this->hasGroup()
            );

            $filter->apply($this->query);
        }
    }

    /**
     * Adjusts select clause in case a group_by clause is defined.
     *
     * @return void
     */
    private function applySelect(): void
    {
        foreach ($this->select as $item) {
            $this->query->select((string)$item);
        }
        foreach ($this->criteria as $criteria) {
            if (null === $criteria->aggregate()) {
                continue;
            }

            $className = (string)$criteria->aggregate();
            $aggregate = new $className($criteria->field());

            $aggregate->apply($this->query);
        }
    }

    /**
     * Group checker.
     *
     * @return bool
     */
    private function hasGroup(): bool
    {
        return null !== $this->groupBy;
    }

    /**
     * Applies association joins to the Query.
     *
     * @return void
     */
    private function applyJoins(): void
    {
        foreach ($this->getAssociations() as $association) {
            switch ($association->type()) {
                case Association::MANY_TO_ONE:
                case Association::MANY_TO_MANY:
                case Association::ONE_TO_MANY:
                    $this->query->leftJoinWith($association->getName());
                    break;
            }
        }
    }

    /**
     * Get required associations based on current search criteria.
     *
     * @return mixed[]
     */
    private function getAssociations(): array
    {
        $result = [];
        foreach ($this->getQueryFields() as $field) {
            $association = $this->getAssociationByField($field);
            if (null === $association) {
                continue;
            }

            if (array_key_exists($association->getName(), $result)) {
                continue;
            }

            $result[$association->getName()] = $association;
        }

        return $result;
    }

    /**
     * Get search query fields from criteria and select clause.
     *
     * @return string[]
     */
    private function getQueryFields(): array
    {
        $result = [];
        foreach ($this->criteria as $criteria) {
            $field = $this->table->aliasField((string)$criteria->field());
            if (! in_array($field, $result)) {
                $result[] = $field;
            }
        }

        foreach ($this->query->clause('select') as $field) {
            if (is_string($field) && ! in_array($field, $result)) {
                $result[] = $field;
            }
        }

        return $result;
    }

    /**
     * Retrieves table association by aliased field name.
     *
     * Example: 'Author.name'
     *
     * @param string $field Field name
     * @return \Cake\ORM\Association|null
     * @throws \RuntimeException When invalid association is found
     */
    private function getAssociationByField(string $field): ?Association
    {
        list($name) = explode('.', $this->table->aliasField($field), 2);

        if ($name === $this->table->getAlias()) {
            return null;
        }
        if (! $this->table->hasAssociation($name)) {
            throw new \RuntimeException(sprintf('Table "%s" does not have association "%s"', $this->table->getAlias(), $name));
        }

        return $this->table->getAssociation($name);
    }
}
