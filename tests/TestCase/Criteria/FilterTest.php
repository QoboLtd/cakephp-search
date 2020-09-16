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
namespace Qobo\Search\Test\TestCase\Criteria;

use PHPUnit\Framework\TestCase;
use Qobo\Search\Criteria\Filter;

class FilterTest extends TestCase
{
    public function testShouldRequireNonEmptyFilterType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $filter = new Filter('', 'foobar');
    }

    public function testShouldRequireScalarOrArrayFilterValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $filter = new Filter(\Search\Filter\StartsWith::class, (object)['foo']);
    }

    public function testShouldRequireValidFilterType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $filter = new Filter(\stdClass::class, 'valid-value');
    }

    public function testShouldAcceptValidFilterType(): void
    {
        $filter = new Filter(\Search\Filter\Contains::class, 'foobar');
        $this->assertInstanceOf(Filter::class, $filter);
    }

    public function testShouldReturnFilterTypeAsString(): void
    {
        $filter = new Filter(\Search\Filter\Contains::class, 'foobar');
        $this->assertEquals(\Search\Filter\Contains::class, $filter->type());
    }

    /**
     *
     * @dataProvider validValuesProvider
     * @param mixed $value
     */
    public function testShouldReturnFilterValueAsScalarOrArray($value): void
    {
        $filter = new Filter(\Search\Filter\Contains::class, $value);
        $this->assertEquals($value, $filter->value());
    }

    /**
     * @return mixed[]
     */
    public function validValuesProvider(): array
    {
        return [
            [''],
            ['string-value'],
            [['array-value']],
            [1],
            [1.1],
            [true],
            [false],
        ];
    }
}
