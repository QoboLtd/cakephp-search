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
namespace Search\Test\TestCase\Filter;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;
use Search\Criteria\Aggregate;
use Search\Criteria\Field;
use Search\Filter\NotEqual;

class NotEqualTest extends TestCase
{
    public $fixtures = ['plugin.Search.articles'];

    private $query;

    public function setUp() : void
    {
        parent::setUp();

        $this->query = TableRegistry::get('Articles')->query();
    }

    public function tearDown() : void
    {
        unset($this->query);

        parent::tearDown();
    }

    public function testApply() : void
    {
        $filter = new NotEqual(new Field('title'), 'foo');

        $result = $filter->apply($this->query);

        $this->assertRegExp(
            '/WHERE \("title" != :c0 OR \("title"\) IS NULL\)/',
            $result->sql()
        );

        $this->assertEquals(
            ['foo'],
            Hash::extract($result->getValueBinder()->bindings(), '{s}.value')
        );

        $this->assertEquals(
            ['string'],
            Hash::extract($result->getValueBinder()->bindings(), '{s}.type')
        );
    }

    public function testApplyWithMulti() : void
    {
        $filter = new NotEqual(new Field('title'), ['foo', 'bar']);

        $result = $filter->apply($this->query);

        $this->assertRegExp(
            '/WHERE \("title" NOT IN \(:c0,:c1\) OR \("title"\) IS NULL\)/',
            $result->sql()
        );

        $this->assertEquals(
            ['foo', 'bar'],
            Hash::extract($result->getValueBinder()->bindings(), '{s}.value')
        );

        $this->assertEquals(
            ['string', 'string'],
            Hash::extract($result->getValueBinder()->bindings(), '{s}.type')
        );
    }

    public function testApplyWithEmtpyArray() : void
    {
        $filter = new NotEqual(new Field('title'), []);

        $result = $filter->apply($this->query);

        $this->assertRegExp(
            '/WHERE \("title" NOT IN \(:c0\) OR \("title"\) IS NULL\)/',
            $result->sql()
        );

        $this->assertEquals(
            [''],
            Hash::extract($result->getValueBinder()->bindings(), '{s}.value')
        );

        $this->assertEquals(
            ['string'],
            Hash::extract($result->getValueBinder()->bindings(), '{s}.type')
        );
    }

    public function testApplyWithAggregateAndGroupBy() : void
    {
        $filter = new NotEqual(new Field('title'), 'foo', new Aggregate(\Search\Aggregate\Average::class), true);

        $result = $filter->apply($this->query);

        $this->assertRegExp(
            '/HAVING \(\(AVG\(title\)\) != :c0 OR \(AVG\(title\)\) IS NULL\)/',
            $result->sql()
        );

        $this->assertEquals(
            ['foo'],
            Hash::extract($result->getValueBinder()->bindings(), '{s}.value')
        );

        $this->assertEquals(
            ['float'],
            Hash::extract($result->getValueBinder()->bindings(), '{s}.type')
        );
    }
}
