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
use Search\Criteria\Aggregate;

class AggregateTest extends TestCase
{
    public function testShouldRequireNonEmptyAggregate() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $aggregate = new Aggregate('');
    }

    public function testShouldRequireValidAggregate() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $aggregate = new Aggregate(Aggregate::class);
    }

    public function testShouldAcceptValidAggregate() : void
    {
        $aggregate = new Aggregate(\Search\Aggregate\Average::class);
        $this->assertInstanceOf(Aggregate::class, $aggregate);
    }

    public function testShouldReturnAsString() : void
    {
        $aggregate = new Aggregate(\Search\Aggregate\Average::class);
        $this->assertEquals(\Search\Aggregate\Average::class, (string)$aggregate);
    }
}
