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
use Search\Filter\Equal;
use Search\Filter\StartsWith;
use Search\Service\Criteria;
use Search\Service\Search;
use Webmozart\Assert\Assert;

class SearchTest extends TestCase
{
    public $fixtures = [
        'plugin.Search.articles',
        'plugin.Search.authors'
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

    public function testExecuteWithAssociated() : void
    {
        $this->table->deleteAll([]);
        $this->table->save(
            $this->table->newEntity([
                'title' => 'one',
                'content' => 'bla bla',
                'author_id' => '00000000-0000-0000-0000-000000000001'
            ])
        );

        $search = new Search($this->table->find(), $this->table);
        $search->addCriteria(new Criteria(['field' => 'Authors.name', 'operator' => Equal::class, 'value' => 'Stephen King']));

        $query = $search->execute();

        $this->assertCount(1, $query);
    }

    public function testExecuteWithAssociatedInvalid() : void
    {
        $this->expectException(\RuntimeException::class);

        $search = new Search($this->table->find(), $this->table);
        $search->addCriteria(new Criteria(['field' => 'NonExistingTable.name', 'operator' => Equal::class, 'value' => 'Stephen King']));

        $search->execute();
    }
}
