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
use Search\Criteria\Field;

class FieldTest extends TestCase
{
    public function testShouldRequireNonEmptyField() : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $field = new Field('');
    }

    public function testShouldAcceptValidField() : void
    {
        $field = new Field('foobar');
        $this->assertInstanceOf(Field::class, $field);
    }

    public function testShouldReturnAsString() : void
    {
        $field = new Field('foobar');
        $this->assertEquals('foobar', (string)$field);
    }
}
