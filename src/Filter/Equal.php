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
namespace Qobo\Search\Filter;

use Cake\Database\Expression\QueryExpression;
use Cake\ORM\Query;

final class Equal extends AbstractFilter
{
    /**
     * Apply provided search value to the Query instance.
     *
     * @param \Cake\ORM\Query $query Query instance
     * @return \Cake\ORM\Query
     */
    public function apply(Query $query): Query
    {
        $method = is_array($this->value) ? 'in' : 'eq';

        return $query->{$this->clause}(
            (new QueryExpression())->{$method}(
                $this->field,
                [] === $this->value ? '' : $this->value,
                is_string($this->field) ? $query->getTypeMap()->type($this->field) : $this->type
            )
        );
    }
}
