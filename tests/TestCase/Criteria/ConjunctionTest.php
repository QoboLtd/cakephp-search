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
use Qobo\Search\Criteria\Conjunction;

class ConjunctionTest extends TestCase
{
    public function testShouldRequireValidValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $value = new Conjunction('invalid value');
    }

    /**
     * @dataProvider validValuesProvider
     */
    public function testShouldAcceptValidValue(string $item): void
    {
        $value = new Conjunction($item);
        $this->assertInstanceOf(Conjunction::class, $value);
    }

    /**
     * @return string[][]
     */
    public function validValuesProvider(): array
    {
        return [
            ['AND'],
            ['OR'],
            ['and'],
            ['or'],
        ];
    }

    public function testShouldReturnAsString(): void
    {
        $conjunction = new Conjunction('and');
        $this->assertEquals('AND', (string)$conjunction);
    }
}
