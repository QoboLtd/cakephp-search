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
use Cake\Database\FunctionsBuilder;

final class Count extends AbstractAggregate
{
    const IDENTIFIER = 'COUNT';

    /**
     * {@inheritDoc}
     */
    public function getExpression(): FunctionExpression
    {
        return (new FunctionsBuilder())->count($this->field);
    }
}
