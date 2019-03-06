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
namespace Search\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;
use Search\Filter\Equal;
use Search\Filter\StartsWith;

class SearchableBehaviorTest extends TestCase
{
    public $fixtures = [
        'plugin.Search.articles',
        'plugin.Search.authors'
    ];

    private $articles;

    public function setUp() : void
    {
        parent::setUp();

        $this->articles = TableRegistry::get('Articles');
        $this->articles->addBehavior('Search.Searchable');
    }

    public function tearDown() : void
    {
        unset($this->articles);

        parent::tearDown();
    }

    public function testFindSearch() : void
    {
        $this->articles->deleteAll([]);
        $this->articles->saveMany(
            $this->articles->newEntities([
                ['title' => 'one', 'content' => 'bla bla'],
                ['title' => 'two', 'content' => 'bla bla'],
                ['title' => 'three', 'content' => 'bla bla'],
                ['title' => 'four', 'content' => 'bla bla'],
            ])
        );

        $data = [
            ['field' => 'title', 'operator' => StartsWith::class, 'value' => 't']
        ];

        $query = $this->articles->find('search', ['data' => $data]);

        $this->assertCount(2, $query);
    }

    /**
     * @dataProvider booleanValueProvider
     * @param mixed $value
     * @return void
     */
    public function testFindSearchWithBoolean($value) : void
    {
        $this->articles->deleteAll([]);
        $this->articles->save(
            $this->articles->newEntity([
                'title' => 'one',
                'content' => 'bla bla',
                'published' => $value
            ])
        );

        $data = [
            ['field' => 'published', 'operator' => Equal::class, 'value' => $value]
        ];

        $query = $this->articles->find('search', ['data' => $data]);

        $this->assertCount(1, $query);
    }

    public function testFindSearchWithAssociated() : void
    {
        $this->articles->deleteAll([]);
        $this->articles->save(
            $this->articles->newEntity([
                'title' => 'one',
                'content' => 'bla bla',
                'author_id' => '00000000-0000-0000-0000-000000000001'
            ])
        );

        $data = [
            ['field' => 'Authors.name', 'operator' => Equal::class, 'value' => 'Stephen King']
        ];

        $query = $this->articles->find('search', ['data' => $data]);

        $this->assertCount(1, $query);
    }

    /**
     * @return mixed[]
     */
    public function booleanValueProvider() : array
    {
        return [
            [1],
            [0],
            [true],
            [false],
            ['1'],
            ['0']
        ];
    }
}
