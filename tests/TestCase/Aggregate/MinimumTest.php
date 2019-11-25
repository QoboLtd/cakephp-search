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

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Search\Aggregate\Minimum;
use Search\Criteria\Field;

class MinimumTest extends TestCase
{
    public $fixtures = ['plugin.Search.articles'];

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
        $aggregate = new Minimum(new Field('priority'));

        $result = $aggregate->apply($this->query);

        $this->assertRegExp(
            '/SELECT \(MIN\(priority\)\) AS "MIN%%priority"/',
            $result->sql()
        );
    }
}
