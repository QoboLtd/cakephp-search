<?php
namespace Search\Test\TestCase\Utility;

use Cake\I18n\Time;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Search\Utility\MagicValue;

/**
 * Search\Utility\MagicValue Test Case
 *
 * @property array $user
 */
class MagicValueTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->user = ['id' => '00000000-0000-0000-0000-000000000002'];
    }

    public function tearDown()
    {
        unset($this->user);

        parent::tearDown();
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(MagicValue::class, new MagicValue('foo', $this->user));
    }

    public function testConstructorExceptionInvalidUserInfo(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new MagicValue('foo', []);
    }

    public function testGetWithoutMagicValue(): void
    {
        $this->assertEquals('%%foo%%', (new MagicValue('%%foo%%', $this->user))->get());
    }

    public function testGetWithMagicValue(): void
    {
        $this->assertEquals('00000000-0000-0000-0000-000000000002', (new MagicValue('%%me%%', $this->user))->get());
        $this->assertInstanceOf(Time::class, (new MagicValue('%%today%%', $this->user))->get());
        $this->assertInstanceOf(Time::class, (new MagicValue('%%yesterday%%', $this->user))->get());
        $this->assertInstanceOf(Time::class, (new MagicValue('%%tomorrow%%', $this->user))->get());
    }
}
