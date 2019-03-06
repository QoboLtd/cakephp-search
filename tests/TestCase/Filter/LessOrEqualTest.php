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
use Search\Filter\LessOrEqual;

class LessOrEqualTest extends TestCase
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
        $filter = new LessOrEqual('title', 'foo');

        $result = $filter->apply($this->query);

        $this->assertRegExp(
            '/WHERE "title" <= :c0/',
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
}
