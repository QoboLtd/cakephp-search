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

use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Search\Filter\EndsWith;
use Search\Filter\Equal;
use Search\Filter\StartsWith;
use Search\Service\Criteria;
use Search\Service\Search;
use Webmozart\Assert\Assert;

class SearchTest extends TestCase
{
    public $fixtures = [
        'plugin.Search.articles',
        'plugin.Search.articles_tags',
        'plugin.Search.authors',
        'plugin.Search.tags'
    ];

    private $table;

    public function setUp() : void
    {
        parent::setUp();

        $this->table = TableRegistry::getTableLocator()->get('Articles');
    }

    public function tearDown() : void
    {
        unset($this->table);

        parent::tearDown();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testAddCriteria() : void
    {
        $this->doesNotPerformAssertions();

        $search = new Search($this->table->find(), $this->table);

        $search->addCriteria(new Criteria(['field' => 'title', 'operator' => StartsWith::class, 'value' => 't']));
    }
    public function testAddCriteriaInvalid() : void
    {
        $this->expectException(\RuntimeException::class);

        $search = new Search($this->table->find(), $this->table);

        $search->addCriteria(new Criteria(['field' => 'title', 'operator' => TestCase::class, 'value' => 't']));
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testSetConjunction() : void
    {
        $this->doesNotPerformAssertions();

        $search = new Search($this->table->find(), $this->table);

        $search->setConjunction(Search::DEFAULT_CONJUNCTION);
    }
    public function testSetConjunctionInvalid() : void
    {
        $this->expectException(\RuntimeException::class);

        $search = new Search($this->table->find(), $this->table);

        $search->setConjunction('invalid conjunction');
    }

    public function testExecute() : void
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

        $search = new Search($this->table->find(), $this->table);
        $search->addCriteria(new Criteria(['field' => 'title', 'operator' => StartsWith::class, 'value' => 't']));

        $query = $search->execute();

        $this->assertCount(2, $query);
    }

    public function testExecuteWithGroupBy() : void
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

        $query = $this->table->find();
        $query->group('content');

        $search = new Search($query, $this->table);
        $search->addCriteria(new Criteria(['field' => 'title', 'operator' => StartsWith::class, 'value' => 't']));

        $query = $search->execute()
            ->enableHydration(true);

        $result = $query->firstOrFail();

        Assert::isInstanceOf($result, EntityInterface::class);

        $this->assertEquals(2, $result->get('total'));
    }

    public function testExecuteWithGroupByAssociatedManyToOne() : void
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

        $query = $this->table->find();
        $query->group('Authors.name');

        $search = new Search($query, $this->table);

        $query = $search->execute();
        $query->order(['total' => 'DESC']);
        $entities = $query->toArray();

        $this->assertCount(2, $entities);

        $this->assertSame(
            [3, 'Stephen King'],
            [$entities[0]->get('total'), $entities[0]->get('_matchingData')['Authors']->get('name')]
        );
        $this->assertSame(
            [1, 'Mark Twain'],
            [$entities[1]->get('total'), $entities[1]->get('_matchingData')['Authors']->get('name')]
        );
    }

    public function testExecuteWithGroupByAssociatedManyToMany() : void
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

        $query = $this->table->find();
        $query->group('Tags.id');

        $search = new Search($query, $this->table);

        $query = $search->execute();
        $query->order(['total' => 'DESC']);
        $entities = $query->toArray();

        $this->assertCount(3, $entities);

        $this->assertSame(
            [3, '00000000-0000-0000-0000-000000000001'],
            [$entities[0]->get('total'), $entities[0]->get('_matchingData')['Tags']->get('id')]
        );
        $this->assertSame(
            [2, '00000000-0000-0000-0000-000000000002'],
            [$entities[1]->get('total'), $entities[1]->get('_matchingData')['Tags']->get('id')]
        );
        $this->assertSame(
            [1, '00000000-0000-0000-0000-000000000003'],
            [$entities[2]->get('total'), $entities[2]->get('_matchingData')['Tags']->get('id')]
        );
    }

    public function testExecuteWithGroupByAssociatedOneToMany() : void
    {
        $table = TableRegistry::getTableLocator()->get('Authors');

        $table->deleteAll([]);
        /**
         * @var \Cake\Datasource\EntityInterface[]|\Cake\ORM\ResultSet
         */
        $newEntities = $table->newEntities([
            ['name' => 'Author 1', 'articles' => ['_ids' => ['00000000-0000-0000-0000-000000000003']]],
            ['name' => 'Author 2', 'articles' => ['_ids' => ['00000000-0000-0000-0000-000000000001']]],
            ['name' => 'Author 3', 'articles' => ['_ids' => ['00000000-0000-0000-0000-000000000002']]]
        ]);

        $table->saveMany($newEntities);

        $query = $table->find();
        $query->group('Articles.published');

        $search = new Search($query, $table);

        $query = $search->execute();
        $query->order(['total' => 'DESC']);
        $entities = $query->toArray();

        $this->assertCount(2, $entities);

        $this->assertSame(
            [2, true],
            [$entities[0]->get('total'), $entities[0]->get('_matchingData')['Articles']->get('published')]
        );
        $this->assertSame(
            [1, false],
            [$entities[1]->get('total'), $entities[1]->get('_matchingData')['Articles']->get('published')]
        );
    }

    public function testExecuteWithAssociatedManyToOne() : void
    {
        $this->table->deleteAll([]);
        $this->table->saveMany(
            $this->table->newEntities([
                ['title' => 'one', 'content' => 'bla bla', 'author_id' => '00000000-0000-0000-0000-000000000001'],
                ['title' => 'two', 'content' => 'bla bla', 'author_id' => '00000000-0000-0000-0000-000000000002'],
                ['title' => 'three', 'content' => 'bla bla', 'author_id' => '00000000-0000-0000-0000-000000000001']
            ])
        );

        $query = $this->table->find();
        $search = new Search($query, $this->table);
        $search->addCriteria(new Criteria(['field' => 'Authors.name', 'operator' => Equal::class, 'value' => 'Stephen King']));

        $query = $search->execute();
        $query->order(['Articles.title' => 'ASC']);
        $entities = $query->toArray();

        $this->assertCount(2, $entities);

        $this->assertSame('one', $entities[0]->get('title'));
        $this->assertSame('three', $entities[1]->get('title'));
    }

    public function testExecuteWithAssociatedManyToMany() : void
    {
        $this->table->deleteAll([]);
        $this->table->saveMany(
            $this->table->newEntities([
                ['title' => 'one', 'content' => 'bla bla', 'tags' => ['_ids' => ['00000000-0000-0000-0000-000000000001', '00000000-0000-0000-0000-000000000002']]],
                ['title' => 'two', 'content' => 'bla bla', 'tags' => ['_ids' => ['00000000-0000-0000-0000-000000000002']]],
                ['title' => 'three', 'content' => 'bla bla', 'tags' => ['_ids' => ['00000000-0000-0000-0000-000000000001']]],
                ['title' => 'four', 'content' => 'bla bla', 'tags' => ['_ids' => ['00000000-0000-0000-0000-000000000003']]],
                ['title' => 'five', 'content' => 'bla bla', 'tags' => ['_ids' => ['00000000-0000-0000-0000-000000000003']]]
            ])
        );

        $query = $this->table->find();
        $query->select(['Articles.title', 'Tags.name']);
        $search = new Search($query, $this->table);
        $search->addCriteria(new Criteria(['field' => 'Tags.name', 'operator' => EndsWith::class, 'value' => '_tag']));

        $query = $search->execute();
        $query->order(['Articles.title' => 'ASC']);
        $entities = $query->toArray();

        $this->assertCount(4, $entities);
        $this->assertSame(['one', '#first_tag'], [$entities[0]->get('title'), $entities[0]->get('_matchingData')['Tags']->get('name')]);
        $this->assertSame(['one', '#another_tag'], [$entities[1]->get('title'), $entities[1]->get('_matchingData')['Tags']->get('name')]);
        $this->assertSame(['three', '#first_tag'], [$entities[2]->get('title'), $entities[2]->get('_matchingData')['Tags']->get('name')]);
        $this->assertSame(['two', '#another_tag'], [$entities[3]->get('title'), $entities[3]->get('_matchingData')['Tags']->get('name')]);
    }

    public function testExecuteWithAssociatedManyToManyByRelatedId() : void
    {
        $expected = '00000000-0000-0000-0000-000000000001';

        $this->table->deleteAll([]);
        $this->table->saveMany(
            $this->table->newEntities([
                ['title' => 'one', 'content' => 'bla bla', 'tags' => ['_ids' => [$expected]]],
                ['title' => 'two', 'content' => 'bla bla', 'tags' => ['_ids' => ['00000000-0000-0000-0000-000000000002']]],
                ['title' => 'three', 'content' => 'bla bla', 'tags' => ['_ids' => [$expected]]],
                ['title' => 'four', 'content' => 'bla bla', 'tags' => ['_ids' => ['00000000-0000-0000-0000-000000000003']]]
            ])
        );

        $query = $this->table->find();
        $query->select(['Articles.title', 'Tags.id']);
        $search = new Search($query, $this->table);
        $search->addCriteria(new Criteria(['field' => 'Tags.id', 'operator' => Equal::class, 'value' => $expected]));

        $query = $search->execute();
        $query->order(['Articles.title' => 'ASC']);
        $entities = $query->toArray();

        $this->assertCount(2, $entities);
        $this->assertSame(['one', $expected], [$entities[0]->get('title'), $entities[0]->get('_matchingData')['Tags']->get('id')]);
        $this->assertSame(['three', $expected], [$entities[1]->get('title'), $entities[1]->get('_matchingData')['Tags']->get('id')]);
    }

    public function testExecuteWithAssociatedOneToMany() : void
    {
        $table = TableRegistry::getTableLocator()->get('Authors');

        $table->deleteAll([]);
        /**
         * @var \Cake\Datasource\EntityInterface[]|\Cake\ORM\ResultSet
         */
        $newEntities = $table->newEntities([
            ['name' => 'Author 1', 'articles' => ['_ids' => ['00000000-0000-0000-0000-000000000001']]],
            ['name' => 'Author 2', 'articles' => ['_ids' => ['00000000-0000-0000-0000-000000000002']]]
        ]);

        $table->saveMany($newEntities);

        $query = $table->find();
        $query->select(['Authors.name', 'Articles.title']);
        $search = new Search($query, $table);
        $search->addCriteria(new Criteria(['field' => 'Articles.title', 'operator' => StartsWith::class, 'value' => 'First']));

        $query = $search->execute();

        $this->assertCount(1, $query);

        $entity = $query->firstOrFail();
        Assert::isInstanceOf($entity, \Cake\Datasource\EntityInterface::class);

        $this->assertSame('Author 1', $entity->get('name'));
        $this->assertSame('First article title', $entity->get('_matchingData')['Articles']->get('title'));
    }

    public function testExecuteWithAssociatedInvalid() : void
    {
        $this->expectException(\RuntimeException::class);

        $search = new Search($this->table->find(), $this->table);
        $search->addCriteria(new Criteria(['field' => 'NonExistingTable.name', 'operator' => Equal::class, 'value' => 'Stephen King']));

        $search->execute();
    }
}
