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

use Search\Criteria\Aggregate;
use Search\Criteria\Field;
use Search\Criteria\Value;

abstract class AbstractFilter implements FilterInterface
{
    /**
     * Field name.
     *
     * @var \Cake\Database\Expression\FunctionExpression|string
     */
    protected $field;

    /**
     * Field type.
     *
     * @var string
     */
    protected $type;

    /**
     * Search value.
     *
     * @var mixed
     */
    protected $value;

    /**
     * Filter clause.
     *
     * @var string
     */
    protected $clause = 'where';

    /**
     * Create filter.
     *
     * @param Field $field Field
     * @param mixed $value Filter value
     * @param Aggregate|null $aggregate Aggregate
     * @param bool $withGroupBy Group-by flag
     */
    public function __construct(Field $field, $value, ?Aggregate $aggregate = null, bool $withGroupBy = false)
    {
        $this->value = $value;
        $this->field = (string)$field;

        if ($aggregate && $withGroupBy) {
            $this->clause = 'having';
            $className = (string)$aggregate;
            $expression = (new $className($field))->getExpression();
            $this->field = $expression;
            $this->type = $expression->getReturnType();
        }
    }
}
