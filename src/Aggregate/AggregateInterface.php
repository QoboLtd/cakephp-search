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

use Cake\Database\Expression\FunctionExpression;
use Cake\ORM\Query;

interface AggregateInterface
{
    const AGGREGATE_PATTERN = '/(.*?)\((.*?)\)/';

    /**
     * Applies aggregate to the provided query.
     *
     * @param \Cake\ORM\Query $query Query instance
     * @return \Cake\ORM\Query
     */
    public function apply(Query $query) : Query;

    /**
     * Returns aggregate function expression.
     *
     * @return \Cake\Database\Expression\FunctionExpression
     */
    public function getExpression() : FunctionExpression;
}
