<?php
/**
 * Copyright (c) Qobo Ltd. (https://www.qobo.biz) : void
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Qobo Ltd. (https://www.qobo.biz) : void
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Search\Test\TestCase\Criteria;

use PHPUnit\Framework\TestCase;
use Search\Criteria\Direction;
use Search\Criteria\Field;
use Search\Criteria\OrderBy;

class OrderByTest extends TestCase
{
    public function testShouldAcceptValidOrderByFieldAndDirection(): void
    {
        $orderBy = new OrderBy(new Field('valid-value'), new Direction('asc'));
        $this->assertInstanceOf(OrderBy::class, $orderBy);
    }

    public function testShouldReturnOrderByFieldAsString(): void
    {
        $orderBy = new OrderBy(new Field('valid-value'), new Direction('asc'));
        $this->assertInstanceOf(Field::class, $orderBy->field());
    }

    public function testShouldReturnOrderByDirection(): void
    {
        $orderBy = new OrderBy(new Field('valid-value'), new Direction('asc'));
        $this->assertInstanceOf(Direction::class, $orderBy->direction());
    }

    public function testShouldReturnAsString(): void
    {
        $orderBy = new OrderBy(new Field('valid-value'), new Direction('asc'));
        $this->assertEquals('valid-value - ASC', (string)$orderBy);
    }
}
