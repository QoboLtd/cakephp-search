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
namespace Search\Service;

use Cake\ORM\Association;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Search\Filter\FilterInterface;

final class Search
{
    /**
     * SQL sort by orders
     */
    const SORT_BY_ORDERS = ['desc', 'asc'];

    /**
     * Default SQL order by direction
     */
    const DEFAULT_SORT_BY_ORDER = 'desc';

    /**
     * SQL conjunctions
     */
    const CONJUNCTIONS = ['AND', 'OR'];

    /**
     * Default SQL conjunction
     */
    const DEFAULT_CONJUNCTION = 'AND';

    /**
     * Group by count field.
     */
    const GROUP_BY_FIELD = 'total';

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
     * Strict mode flag.
     *
     * @var bool
     */
    private $strict = true;

    /**
     * Search conjunction.
     *
     * @var string
     */
    private $conjunction = 'AND';

    /**
     * Search criteria list.
     *
     * @var \Search\Service\Criteria[]
     */
    private $criteria = [];

    /**
     * Constructor method.
     *
     * @param \Cake\ORM\Query $query Query instance
     * @param \Cake\ORM\Table $table Table instance
     * @param bool $strict Strict mode flag
     * @return void
     */
    public function __construct(Query $query, Table $table, bool $strict = true)
    {
        $this->table = $table;
        $this->query = $query;
        $this->strict = $strict;
    }

    /**
     * Add criteria to Search.
     *
     * @param \Search\Service\Criteria $criteria Criteria object
     * @return void
     * @throws \RuntimeException When invalid filter class is provided
     */
    public function addCriteria(Criteria $criteria) : void
    {
        if (! $this->isValidFilter($criteria->getOperator())) {
            throw new \RuntimeException(sprintf('Invalid filter provided: %s', $criteria->getOperator()));
        }

        $this->criteria[] = $criteria;
    }

    /**
     * Add conjunction to Search.
     *
     * @param string $conjunction Search conjunction
     * @return void
     * @throws \RuntimeException When invalid filter class is provided
     */
    public function setConjunction(string $conjunction = self::DEFAULT_CONJUNCTION) : void
    {
        if (! in_array($conjunction, self::CONJUNCTIONS)) {
            throw new \RuntimeException(sprintf('Invalid conjunction provided: %s', $conjunction));
        }

        $this->conjunction = $conjunction;
    }

    /**
     * Executes search logic.
     *
     * @return \Cake\ORM\Query
     */
    public function execute() : Query
    {
        $this->applyFilters();
        $this->applySelect();
        $this->applyJoins();

        $clause = $this->query->clause('where');
        // adjust where clause conjunction
        if (null !== $clause) {
            $this->query->where($clause->setConjunction($this->conjunction), [], true);
        }

        return $this->query;
    }

    /**
     * Validates filter class.
     *
     * @param string $filter Filter class name
     * @return bool
     */
    private function isValidFilter(string $filter) : bool
    {
        return in_array(FilterInterface::class, class_implements($filter));
    }

    /**
     * Applies filters to the Query.
     *
     * @return void
     */
    private function applyFilters() : void
    {
        foreach ($this->criteria as $criteria) {
            $filterClass = $criteria->getOperator();

            $filter = new $filterClass(
                $this->table->aliasField($criteria->getField()),
                $criteria->getValue()
            );

            $filter->apply($this->query);
        }
    }

    /**
     * Adjusts select clause in case a group_by clause is defined.
     *
     * @return void
     */
    private function applySelect() : void
    {
        $group = $this->query->clause('group');
        $group = array_filter($group);
        if (empty($group)) {
            return;
        }

        $this->query->select($group, true);
        $this->query->select([self::GROUP_BY_FIELD => $this->query->func()->count($group[0])]);
    }

    /**
     * Applies association joins to the Query.
     *
     * @return void
     */
    private function applyJoins() : void
    {
        foreach ($this->getAssociations() as $association) {
            switch ($association->type()) {
                case Association::MANY_TO_ONE:
                    $this->query->leftJoinWith($association->getName());
                    break;

                case Association::ONE_TO_ONE:
                case Association::ONE_TO_MANY:
                case Association::MANY_TO_MANY:
                default:
                    break;
            }
        }
    }

    /**
     * Get required associations based on current search criteria.
     *
     * @return mixed[]
     */
    private function getAssociations() : array
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
    private function getQueryFields() : array
    {
        $result = [];
        foreach ($this->criteria as $criteria) {
            $field = $this->table->aliasField($criteria->getField());
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
    private function getAssociationByField(string $field) : ?Association
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
