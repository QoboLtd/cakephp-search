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
namespace Search\Test\TestCase\Transformer;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Search\Criteria\Conjunction;
use Search\Criteria\Criteria;
use Search\Criteria\Field;
use Search\Criteria\OrderBy;
use Search\Transformer\QueryDataTransformer;

class QueryDataTransformerTest extends TestCase
{
    public $fixtures = [
        'plugin.Search.Articles',
    ];

    private $table;

    public function setUp(): void
    {
        parent::setUp();

        $this->table = TableRegistry::getTableLocator()->get('Articles');
    }

    public function tearDown(): void
    {
        unset($this->table);

        parent::tearDown();
    }

    public function testTransform(): void
    {
        $query = $this->table->query()
            ->applyOptions([
                'group' => 'content',
                'order' => ['title' => 'desc'],
                'fields' => ['title', 'avg(priority)', 'count(published)'],
            ]);
        $options = [
            'conjunction' => 'OR',
            'data' => [
                ['field' => 'title', 'operator' => \Search\Filter\EndsWith::class, 'value' => 't'],
                ['field' => 'avg(priority)', 'operator' => 'greater', 'value' => 3],
            ],
        ];

        $result = QueryDataTransformer::transform($query, $options);

        $this->assertInstanceOf(Field::class, $result->getGroup());
        $order = $result->getOrder();
        $this->assertTrue(is_array($order));
        $this->assertNotEmpty($order);
        foreach ($order as $o) {
            $this->assertInstanceOf(OrderBy::class, $o);
        }
        $this->assertInstanceOf(Conjunction::class, $result->getConjunction());

        $this->assertTrue([] !== $result->getSelect());
        foreach ($result->getSelect() as $item) {
            $this->assertInstanceOf(Field::class, $item);
        }

        $this->assertTrue([] !== $result->getCriteria());
        foreach ($result->getCriteria() as $item) {
            $this->assertInstanceOf(Criteria::class, $item);
        }
    }

    public function testTransformWithoutData(): void
    {
        $result = QueryDataTransformer::transform($this->table->query(), []);

        $this->assertNull($result->getGroup());
        $order = $result->getOrder();
        $this->assertTrue(is_array($order));
        $this->assertEmpty($order);
        $this->assertNull($result->getConjunction());
        $this->assertSame([], $result->getSelect());
        $this->assertSame([], $result->getCriteria());
    }

    public function testShouldRequireValidAggregate(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $result = QueryDataTransformer::transform(
            $this->table->query()->applyOptions(['fields' => ['(published)']]),
            []
        );
    }

    public function testShouldRequireValidFilter(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $result = QueryDataTransformer::transform(
            $this->table->query(),
            ['data' => [['field' => 'title', 'operator' => 'invalid-filter', 'value' => 't']]]
        );
    }
}
