<?php
namespace Search\Utility;

use Cake\I18n\Time;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Search\Utility\MagicValue;

/**
 * Search\Utility\MagicValue Test Case
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

    public function testConstructor()
    {
        new MagicValue('foo', $this->user);
    }

    public function testConstructorExceptionInvalidValue()
    {
        $this->expectException(InvalidArgumentException::class);

        new MagicValue([], $this->user);
    }

    public function testConstructorExceptionInvalidUserInfo()
    {
        $this->expectException(InvalidArgumentException::class);

        new MagicValue('foo', []);
    }

    public function testGetWithoutMagicValue()
    {
        $this->assertEquals('%%foo%%', (new MagicValue('%%foo%%', $this->user))->get());
    }

    public function testGetWithMagicValue()
    {
        $this->assertEquals('00000000-0000-0000-0000-000000000002', (new MagicValue('%%me%%', $this->user))->get());
        $this->assertInstanceOf(Time::class, (new MagicValue('%%today%%', $this->user))->get());
        $this->assertInstanceOf(Time::class, (new MagicValue('%%yesterday%%', $this->user))->get());
        $this->assertInstanceOf(Time::class, (new MagicValue('%%tomorrow%%', $this->user))->get());
    }
}
