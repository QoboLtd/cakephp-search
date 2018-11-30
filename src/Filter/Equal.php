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
namespace Search\Filter;

use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\QueryInterface;

final class Equal extends AbstractFilter
{
    /**
     * Apply provided search value to the Query instance.
     *
     * @param \Cake\Datasource\QueryInterface $query Query instance
     * @return \Cake\Datasource\QueryInterface
     */
    public function apply(QueryInterface $query) : QueryInterface
    {
        $value = $this->getValue();

        $method = is_array($value) ? 'in' : 'eq';

        return $query->where(
            (new QueryExpression())->{$method}($this->getField(), $value)
        );
    }
}
