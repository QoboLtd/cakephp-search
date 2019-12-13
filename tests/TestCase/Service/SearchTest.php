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
namespace Search\Test\TestCase\Service;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Search\Criteria\Aggregate;
use Search\Criteria\Conjunction;
use Search\Criteria\Criteria;
use Search\Criteria\Direction;
use Search\Criteria\Field;
use Search\Criteria\Filter;
use Search\Criteria\OrderBy;
use Search\Service\Search;
use Webmozart\Assert\Assert;

class SearchTest extends TestCase
{
    public $fixtures = [
        'plugin.Search.articles',
        'plugin.Search.articles_tags',
        'plugin.Search.authors',
        'plugin.Search.tags',
    ];

    private $table;
    private $criteria;
    private $conjunction;

    public function setUp(): void
    {
        parent::setUp();

        $this->table = TableRegistry::getTableLocator()->get('Articles');

        $this->criteria = Criteria::create(new Field('Articles.title'));
        $this->criteria->setFilter(new Filter(\Search\Filter\StartsWith::class, 't'));
        $this->criteria->setAggregate(new Aggregate(\Search\Aggregate\Count::class));

        $this->conjunction = new Conjunction('AND');
    }

    public function tearDown(): void
    {
        unset($this->table);

        parent::tearDown();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testShouldAddCriteria(): void
    {
        $this->doesNotPerformAssertions();

        $search = new Search($this->table);
        $search->addCriteria($this->criteria);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testShouldSetConjunction(): void
    {
        $this->doesNotPerformAssertions();

        $search = new Search($this->table);
        $search->setConjunction($this->conjunction);
    }

    public function testShouldExecute(): void
    {
        $this->table->deleteAll([]);
        $this->table->saveMany(
            $this->table->newEntities([
                ['title' => 'one', 'content' => 'bla bla'],
                ['title' => 'two', 'content' => 'bla bla'],
                ['title' => 'three', 'content' => 'bla bla'],
                ['title' => 'four', 'content' => 'bla bla'],
            ])
        );

        $criteria = Criteria::create(new Field('Articles.title'));
        $criteria->setFilter(new Filter(\Search\Filter\StartsWith::class, 't'));

        $search = new Search($this->table);
        $search->addCriteria($criteria);
        $search->addSelect(new Field('title'));
        $search->addSelect(new Field('content'));

        $query = $search->execute();

        $this->assertCount(2, $query);

        $result = $query->firstOrFail();
        Assert::isInstanceOf($result, \Cake\Datasource\EntityInterface::class);
        $this->assertEquals([], array_diff(['title', 'content'], $result->visibleProperties()));
    }

    public function testShouldExecuteWithGroupBy(): void
    {
        $this->table->deleteAll([]);
        $this->table->saveMany(
            $this->table->newEntities([
                ['title' => 'one', 'content' => 'bla bla'],
                ['title' => 'two', 'content' => 'bla bla'],
            ])
        );

        $field = new Field('content');

        $criteria = Criteria::create($field);
        $criteria->setAggregate(new Aggregate(\Search\Aggregate\Count::class));

        $search = new Search($this->table);
        $search->setGroupBy($field);
        $search->addCriteria($criteria);
        $query = $search->execute();

        $result = $query->enableHydration(true)
            ->firstOrFail();
        Assert::isInstanceOf($result, \Cake\Datasource\EntityInterface::class);

        $this->assertEquals(2, $result->get('COUNT(content)'));
    }

    public function testShouldExecuteWithGroupByAndFilter(): void
    {
        $this->table->deleteAll([]);
        $this->table->saveMany(
            $this->table->newEntities([
                ['title' => 'one', 'content' => 'bla bla'],
                ['title' => 'two', 'content' => 'bla bla'],
                ['title' => 'three', 'content' => 'foo'],
                ['title' => 'four', 'content' => 'foo'],
            ])
        );

        $criteria = Criteria::create(new Field('title'));
        $criteria->setFilter(new Filter(\Search\Filter\StartsWith::class, 't'));

        $search = new Search($this->table);
        $search->setGroupBy(new Field('content'));
        $search->addCriteria($criteria);

        $result = $search->execute()
            ->enableHydration(true)
            ->all();

        $this->assertCount(2, $result);
    }

    public function testShouldExecuteWithGroupByAssociatedManyToOne(): void
    {
        $this->table->deleteAll([]);
        $this->table->saveMany(
            $this->table->newEntities([
                ['title' => 'one', 'content' => 'bla bla', 'author_id' => '00000000-0000-0000-0000-000000000001'],
                ['title' => 'two', 'content' => 'bla bla', 'author_id' => '00000000-0000-0000-0000-000000000001'],
                ['title' => 'three', 'content' => 'bla bla', 'author_id' => '00000000-0000-0000-0000-000000000001'],
                ['title' => 'four', 'content' => 'bla bla', 'author_id' => '00000000-0000-0000-0000-000000000002'],
            ])
        );

        $field = new Field('Authors.name');
        $aggregateField = 'COUNT(Authors.name)';

        $criteria = Criteria::create($field);
        $criteria->setAggregate(new Aggregate(\Search\Aggregate\Count::class));

        $search = new Search($this->table);
        $search->addSelect($field);
        $search->setGroupBy($field);
        $search->addCriteria($criteria);
        $search->setOrderBy(new OrderBy(new Field($aggregateField), new Direction('DESC')));
        $query = $search->execute();

        $query->order([$aggregateField => 'DESC']);
        $entities = $query->toArray();
        $this->assertCount(2, $entities);
        $this->assertSame(
            [3, 'Stephen King'],
            [$entities[0]->get($aggregateField), $entities[0]->get('_matchingData')['Authors']->get('name')]
        );
        $this->assertSame(
            [1, 'Mark Twain'],
            [$entities[1]->get($aggregateField), $entities[1]->get('_matchingData')['Authors']->get('name')]
        );
    }

    public function testShouldExecuteWithGroupByAssociatedManyToMany(): void
    {
        $this->table->deleteAll([]);
        $this->table->saveMany(
            $this->table->newEntities([
                ['title' => 'one', 'content' => 'bla bla', 'tags' => ['_ids' => ['00000000-0000-0000-0000-000000000001', '00000000-0000-0000-0000-000000000002']]],
                ['title' => 'two', 'content' => 'bla bla', 'tags' => ['_ids' => ['00000000-0000-0000-0000-000000000001', '00000000-0000-0000-0000-000000000002']]],
                ['title' => 'three', 'content' => 'bla bla', 'tags' => ['_ids' => ['00000000-0000-0000-0000-000000000001']]],
                ['title' => 'four', 'content' => 'bla bla', 'tags' => ['_ids' => ['00000000-0000-0000-0000-000000000003']]],
            ])
        );

        $field = new Field('Tags.id');
        $aggregateField = 'COUNT(Tags.id)';

        $criteria = Criteria::create($field);
        $criteria->setAggregate(new Aggregate(\Search\Aggregate\Count::class));

        $search = new Search($this->table);
        $search->addSelect($field);
        $search->addSelect(new Field('Tags.name'));
        $search->setGroupBy($field);
        $search->addCriteria($criteria);
        $search->setOrderBy(new OrderBy(new Field($aggregateField), new Direction('DESC')));
        $query = $search->execute();

        $entities = $query->toArray();
        $this->assertCount(3, $entities);
        $this->assertSame(
            [3, '00000000-0000-0000-0000-000000000001'],
            [$entities[0]->get($aggregateField), $entities[0]->get('_matchingData')['Tags']->get('id')]
        );
        $this->assertSame(
            [2, '00000000-0000-0000-0000-000000000002'],
            [$entities[1]->get($aggregateField), $entities[1]->get('_matchingData')['Tags']->get('id')]
        );
        $this->assertSame(
            [1, '00000000-0000-0000-0000-000000000003'],
            [$entities[2]->get($aggregateField), $entities[2]->get('_matchingData')['Tags']->get('id')]
        );
    }

    public function testShouldExecuteWithGroupByAssociatedOneToMany(): void
    {
        $table = TableRegistry::getTableLocator()->get('Authors');

        $table->deleteAll([]);
        /**
         * @var \Cake\Datasource\EntityInterface[]|\Cake\ORM\ResultSet
         */
        $newEntities = $table->newEntities([
            ['name' => 'Author 1', 'articles' => ['_ids' => ['00000000-0000-0000-0000-000000000003']]],
            ['name' => 'Author 2', 'articles' => ['_ids' => ['00000000-0000-0000-0000-000000000001']]],
            ['name' => 'Author 3', 'articles' => ['_ids' => ['00000000-0000-0000-0000-000000000002']]],
        ]);

        $table->saveMany($newEntities);

        $field = new Field('Articles.published');
        $aggregateField = 'COUNT(Articles.published)';

        $criteria = Criteria::create($field);
        $criteria->setAggregate(new Aggregate(\Search\Aggregate\Count::class));

        $search = new Search($table);
        $search->addSelect($field);
        $search->setGroupBy($field);
        $search->addCriteria($criteria);
        $search->setOrderBy(new OrderBy(new Field($aggregateField), new Direction('DESC')));
        $query = $search->execute();

        $entities = $query->toArray();
        $this->assertCount(2, $entities);
        $this->assertSame(
            [2, true],
            [$entities[0]->get($aggregateField), $entities[0]->get('_matchingData')['Articles']->get('published')]
        );
        $this->assertSame(
            [1, false],
            [$entities[1]->get($aggregateField), $entities[1]->get('_matchingData')['Articles']->get('published')]
        );
    }

    /**
     * @dataProvider aggregatesProvider
     * @param string $aggregateClass Aggergate fqcn
     * @param mixed $expected
     * @param string $prefix Aggregate prefix
     */
    public function testShouldExecuteWithAggregate(string $aggregateClass, $expected, string $prefix): void
    {
        $this->table->deleteAll([]);
        $this->table->saveMany(
            $this->table->newEntities([
                ['title' => 'one', 'content' => 'bla bla', 'priority' => 10.5],
                ['title' => 'two', 'content' => 'bla bla', 'priority' => 20.32],
                ['title' => 'three', 'content' => 'bla bla', 'priority' => 30],
                ['title' => 'four', 'content' => 'bla bla', 'priority' => 40.11],
            ])
        );

        $criteria = Criteria::create(new Field('priority'));
        $criteria->setAggregate(new Aggregate($aggregateClass));

        $search = new Search($this->table);
        $search->addCriteria($criteria);

        $entity = $search->execute()
            ->enableHydration(true)
            ->firstOrFail();
        Assert::isInstanceOf($entity, \Cake\Datasource\EntityInterface::class);

        $this->assertSame($expected, $entity->get($prefix . '(priority)'));
    }

    public function testShouldExecuteWithAggregateAndFilter(): void
    {
        $this->table->deleteAll([]);
        $this->table->saveMany(
            $this->table->newEntities([
                ['title' => 'one', 'content' => 'bla bla', 'priority' => 10.5],
                ['title' => 'two', 'content' => 'bla bla', 'priority' => 20.32],
                ['title' => 'three', 'content' => 'bla bla', 'priority' => 30],
                ['title' => 'four', 'content' => 'bla bla', 'priority' => 40.11],
            ])
        );

        $criteria = Criteria::create(new Field('priority'));
        $criteria->setFilter(new Filter(\Search\Filter\Greater::class, 25));
        $criteria->setAggregate(new Aggregate(\Search\Aggregate\Sum::class));

        $search = new Search($this->table);
        $search->addCriteria($criteria);
        $query = $search->execute();

        $entity = $query->enableHydration(true)
            ->firstOrFail();
        Assert::isInstanceOf($entity, \Cake\Datasource\EntityInterface::class);

        $this->assertSame(70.11, $entity->get('SUM(priority)'));
    }

    public function testShouldExecuteWithAggregateAndGroupBy(): void
    {
        $this->table->deleteAll([]);
        $this->table->saveMany(
            $this->table->newEntities([
                ['title' => 'one', 'content' => 'bla bla', 'priority' => 10.5, 'published' => true],
                ['title' => 'two', 'content' => 'bla bla', 'priority' => 20.32, 'published' => true],
                ['title' => 'three', 'content' => 'bla bla', 'priority' => 30, 'published' => false],
                ['title' => 'four', 'content' => 'bla bla', 'priority' => 40.11, 'published' => false],
            ])
        );

        $criteria = Criteria::create(new Field('priority'));
        $criteria->setAggregate(new Aggregate(\Search\Aggregate\Maximum::class));

        $field = new Field('published');

        $search = new Search($this->table);
        $search->addSelect($field);
        $search->setGroupBy($field);
        $search->addCriteria($criteria);

        $query = $search->execute();

        $aggregateField = 'MAX(priority)';
        $query->order([$aggregateField => 'DESC']);

        $entities = $query->toArray();
        $this->assertCount(2, $entities);
        $this->assertSame(
            ['40.11', false],
            [$entities[0]->get($aggregateField), $entities[0]->get('published')]
        );
        $this->assertSame(
            ['20.32', true],
            [$entities[1]->get($aggregateField), $entities[1]->get('published')]
        );
    }

    public function testShouldExecuteWithAggregateAndGroupByAndFilter(): void
    {
        $this->table->deleteAll([]);
        $this->table->saveMany(
            $this->table->newEntities([
                ['title' => 'one', 'content' => 'bla bla', 'author_id' => '00000000-0000-0000-0000-000000000001', 'published' => true],
                ['title' => 'two', 'content' => 'bla bla', 'author_id' => '00000000-0000-0000-0000-000000000002', 'published' => true],
                ['title' => 'three', 'content' => 'bla bla', 'author_id' => '00000000-0000-0000-0000-000000000001', 'published' => false],
                ['title' => 'four', 'content' => 'bla bla', 'author_id' => '00000000-0000-0000-0000-000000000001', 'published' => false],
            ])
        );

        $field = new Field('author_id');

        $criteria = Criteria::create($field);
        $criteria->setAggregate(new Aggregate(\Search\Aggregate\Count::class));
        $criteria->setFilter(new Filter(\Search\Filter\Greater::class, 2));

        $search = new Search($this->table);
        $search->addSelect($field);
        $search->addCriteria($criteria);
        $search->setGroupBy($field);

        $query = $search->execute();

        $this->assertCount(1, $query);
        $result = $query->firstOrFail();
        Assert::isInstanceOf($result, \Cake\Datasource\EntityInterface::class);
        $this->assertSame(
            ['00000000-0000-0000-0000-000000000001', 3],
            [$result->get('author_id'), $result->get('COUNT(author_id)')]
        );
    }

    public function testShouldExecuteWithFilterOnAssociatedManyToOne(): void
    {
        $this->table->deleteAll([]);
        $this->table->saveMany(
            $this->table->newEntities([
                ['title' => 'one', 'content' => 'bla bla', 'author_id' => '00000000-0000-0000-0000-000000000001'],
                ['title' => 'two', 'content' => 'bla bla', 'author_id' => '00000000-0000-0000-0000-000000000002'],
                ['title' => 'three', 'content' => 'bla bla', 'author_id' => '00000000-0000-0000-0000-000000000001'],
            ])
        );

        $criteria = Criteria::create(new Field('Authors.name'));
        $criteria->setFilter(new Filter(\Search\Filter\Equal::class, 'Stephen King'));

        $search = new Search($this->table);
        $search->addCriteria($criteria);

        $query = $search->execute();

        $query->order(['Articles.title' => 'ASC']);
        $entities = $query->toArray();
        $this->assertCount(2, $entities);
        $this->assertSame('one', $entities[0]->get('title'));
        $this->assertSame('three', $entities[1]->get('title'));
    }

    public function testShouldExecuteWithFilterOnAssociatedManyToMany(): void
    {
        $this->table->deleteAll([]);
        $this->table->saveMany(
            $this->table->newEntities([
                ['title' => 'one', 'content' => 'bla bla', 'tags' => ['_ids' => ['00000000-0000-0000-0000-000000000001', '00000000-0000-0000-0000-000000000002']]],
                ['title' => 'two', 'content' => 'bla bla', 'tags' => ['_ids' => ['00000000-0000-0000-0000-000000000002']]],
                ['title' => 'three', 'content' => 'bla bla', 'tags' => ['_ids' => ['00000000-0000-0000-0000-000000000001']]],
                ['title' => 'four', 'content' => 'bla bla', 'tags' => ['_ids' => ['00000000-0000-0000-0000-000000000003']]],
                ['title' => 'five', 'content' => 'bla bla', 'tags' => ['_ids' => ['00000000-0000-0000-0000-000000000003']]],
            ])
        );

        $field = new Field('Tags.name');

        $criteria = Criteria::create($field);
        $criteria->setFilter(new Filter(\Search\Filter\EndsWith::class, '_tag'));

        $search = new Search($this->table);
        $search->addSelect($field);
        $search->addSelect(new Field('Articles.title'));
        $search->addCriteria($criteria);

        $query = $search->execute();

        $query->order(['Articles.title' => 'ASC']);
        $entities = $query->toArray();
        $this->assertCount(4, $entities);
        $this->assertSame(['one', '#first_tag'], [$entities[0]->get('title'), $entities[0]->get('_matchingData')['Tags']->get('name')]);
        $this->assertSame(['one', '#another_tag'], [$entities[1]->get('title'), $entities[1]->get('_matchingData')['Tags']->get('name')]);
        $this->assertSame(['three', '#first_tag'], [$entities[2]->get('title'), $entities[2]->get('_matchingData')['Tags']->get('name')]);
        $this->assertSame(['two', '#another_tag'], [$entities[3]->get('title'), $entities[3]->get('_matchingData')['Tags']->get('name')]);
    }

    public function testShouldExecuteWithFilterByRelatedIdOnAssociatedManyToMany(): void
    {
        $expected = '00000000-0000-0000-0000-000000000001';

        $this->table->deleteAll([]);
        $this->table->saveMany(
            $this->table->newEntities([
                ['title' => 'one', 'content' => 'bla bla', 'tags' => ['_ids' => [$expected]]],
                ['title' => 'two', 'content' => 'bla bla', 'tags' => ['_ids' => ['00000000-0000-0000-0000-000000000002']]],
                ['title' => 'three', 'content' => 'bla bla', 'tags' => ['_ids' => [$expected]]],
                ['title' => 'four', 'content' => 'bla bla', 'tags' => ['_ids' => ['00000000-0000-0000-0000-000000000003']]],
            ])
        );

        $field = new Field('Tags.id');

        $criteria = Criteria::create($field);
        $criteria->setFilter(new Filter(\Search\Filter\Equal::class, $expected));

        $search = new Search($this->table);
        $search->addSelect($field);
        $search->addSelect(new Field('Articles.title'));
        $search->addCriteria($criteria);

        $query = $search->execute();

        $query->order(['Articles.title' => 'ASC']);
        $entities = $query->toArray();
        $this->assertCount(2, $entities);
        $this->assertSame(['one', $expected], [$entities[0]->get('title'), $entities[0]->get('_matchingData')['Tags']->get('id')]);
        $this->assertSame(['three', $expected], [$entities[1]->get('title'), $entities[1]->get('_matchingData')['Tags']->get('id')]);
    }

    public function testShouldExecuteWithFilterOnAssociatedOneToMany(): void
    {
        $table = TableRegistry::getTableLocator()->get('Authors');

        $table->deleteAll([]);
        /**
         * @var \Cake\Datasource\EntityInterface[]|\Cake\ORM\ResultSet
         */
        $newEntities = $table->newEntities([
            ['name' => 'Author 1', 'articles' => ['_ids' => ['00000000-0000-0000-0000-000000000001']]],
            ['name' => 'Author 2', 'articles' => ['_ids' => ['00000000-0000-0000-0000-000000000002']]],
        ]);

        $table->saveMany($newEntities);

        $field = new Field('Articles.title');

        $criteria = Criteria::create($field);
        $criteria->setFilter(new Filter(\Search\Filter\StartsWith::class, 'First'));

        $search = new Search($table);
        $search->addSelect($field);
        $search->addSelect(new Field('Authors.name'));
        $search->addCriteria($criteria);

        $query = $search->execute();

        $this->assertCount(1, $query);

        $entity = $query->firstOrFail();
        Assert::isInstanceOf($entity, \Cake\Datasource\EntityInterface::class);

        $this->assertSame('Author 1', $entity->get('name'));
        $this->assertSame('First article title', $entity->get('_matchingData')['Articles']->get('title'));
    }

    public function testShouldRequireValidAssociation(): void
    {
        $this->expectException(\RuntimeException::class);

        $criteria = Criteria::create(new Field('NonExistingTable.name'));
        $criteria->setFilter(new Filter(\Search\Filter\Equal::class, 'Foobar'));

        $search = new Search($this->table);
        $search->addCriteria($criteria);

        $search->execute();
    }

    /**
     * @return mixed[]
     */
    public function aggregatesProvider(): array
    {
        return [
            [\Search\Aggregate\Average::class, 25.2325, 'AVG'],
            [\Search\Aggregate\Count::class, 4, 'COUNT'],
            [\Search\Aggregate\Maximum::class, '40.11', 'MAX'],
            [\Search\Aggregate\Minimum::class, '10.5', 'MIN'],
            [\Search\Aggregate\Sum::class, 100.93, 'SUM'],
        ];
    }
}
