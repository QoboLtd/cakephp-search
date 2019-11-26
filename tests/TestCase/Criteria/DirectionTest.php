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

class DirectionTest extends TestCase
{
    public function testShouldRequireValidDirection(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $direction = new Direction('invalid-direction');
    }

    /**
     * @dataProvider validDirectionsProvider
     */
    public function testShouldAcceptValidDirection(string $value): void
    {
        $direction = new Direction($value);
        $this->assertInstanceOf(Direction::class, $direction);
    }

    /**
     * @dataProvider validDirectionsProvider
     */
    public function testShouldReturnDirectionAsUppercasedString(string $value): void
    {
        $direction = new Direction($value);
        $this->assertSame(strtoupper($value), (string)$direction);
    }

    /**
     * @return string[][]
     */
    public function validDirectionsProvider(): array
    {
        return [
            ['ASC'],
            ['asc'],
            ['DESC'],
            ['desc'],
        ];
    }
}
