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
namespace Qobo\Search\Test\TestCase\Filter;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;
use Qobo\Search\Criteria\Aggregate;
use Qobo\Search\Criteria\Field;
use Qobo\Search\Filter\Contains;

class ContainsTest extends TestCase
{
    public $fixtures = ['plugin.Search.Articles'];

    private $query;

    public function setUp(): void
    {
        parent::setUp();

        $this->query = TableRegistry::getTableLocator()->get('Articles')->query();
    }

    public function tearDown(): void
    {
        unset($this->query);

        parent::tearDown();
    }

    public function testApply(): void
    {
        $filter = new Contains(new Field('title'), 'foo');

        $result = $filter->apply($this->query);

        $this->assertRegExp(
            '/WHERE "title" LIKE :c0/',
            $result->sql()
        );

        $this->assertEquals(
            ['%foo%'],
            Hash::extract($result->getValueBinder()->bindings(), '{s}.value')
        );

        $this->assertEquals(
            ['string'],
            Hash::extract($result->getValueBinder()->bindings(), '{s}.type')
        );
    }

    public function testApplyWithAggregateAndGroupBy(): void
    {
        $filter = new Contains(new Field('title'), 'foo', new Aggregate(\Search\Aggregate\Count::class), true);

        $result = $filter->apply($this->query);

        $this->assertRegExp(
            '/HAVING \(COUNT\(title\)\) LIKE :c0/',
            $result->sql()
        );

        $this->assertEquals(
            ['%foo%'],
            Hash::extract($result->getValueBinder()->bindings(), '{s}.value')
        );

        $this->assertEquals(
            ['integer'],
            Hash::extract($result->getValueBinder()->bindings(), '{s}.type')
        );
    }
}
