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
namespace Search\Test\TestCase\Aggregate;

use PHPUnit\Framework\TestCase;
use Search\Aggregate\AbstractAggregate;

class AbstractAggregateTest extends TestCase
{
    public function testIsAggregate() : void
    {
        $this->assertFalse(AbstractAggregate::isAggregate('foo'));
        $this->assertTrue(AbstractAggregate::isAggregate('foo(bar)'));
    }

    public function testExtractFieldName() : void
    {
        $this->assertSame('', AbstractAggregate::extractFieldName('foo'));
        $this->assertSame('bar', AbstractAggregate::extractFieldName('foo(bar)'));
    }

    public function testExtractAggregate() : void
    {
        $this->assertSame('', AbstractAggregate::extractAggregate('foo'));
        $this->assertSame('foo', AbstractAggregate::extractAggregate('foo(bar)'));
    }
}
