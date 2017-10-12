<?php
namespace Search\Utility;

use Cake\Event\EventManager;
use Cake\Http\ServerRequest;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use ReflectionClass;
use Search\Model\Entity\SavedSearch;
use Search\Utility\Search;

/**
 * Search\Utility\Search Test Case
 */
class SearchTest extends TestCase
{
    public $fixtures = [
        'plugin.search.articles',
        'plugin.search.authors',
        'plugin.search.dashboards',
        'plugin.search.saved_searches',
        'plugin.roles_capabilities.roles'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->user = ['id' => '00000000-0000-0000-0000-000000000001'];
        $this->Search = new Search(TableRegistry::get('Dashboards'), $this->user);

        EventManager::instance()->on('Search.Model.Search.searchabeFields', function ($event, $table) {
            $tableName = $table->getRegistryAlias();

            $result = [];
            switch ($tableName) {
                case 'Dashboards':
                    $result = [
                        'Dashboards.name' => ['type' => 'string', 'operators' => [
                            'contains' => ['label' => 'contains', 'operator' => 'LIKE', 'pattern' => '%{{value}}%', 'emptyCriteria' => [
                                'aggregator' => 'OR', 'values' => ['IS NULL', '= ""']
                            ]]
                        ]],
                        'Dashboards.image' => ['type' => 'blob', 'operators' => [
                            'contains' => ['label' => 'contains', 'operator' => 'LIKE', 'pattern' => '%{{value}}%', 'emptyCriteria' => [
                                'aggregator' => 'OR', 'values' => ['IS NULL', '= ""']
                            ]],
                        ]],
                        'Dashboards.role_id' => ['type' => 'related', 'source' => 'Roles', 'operators' => [
                            'is' => ['label' => 'is', 'operator' => 'IN', 'emptyCriteria' => [
                                'aggregator' => 'OR', 'values' => ['IS NULL', '= ""']
                            ]],
                            'is_not' => ['label' => 'is not', 'operator' => 'NOT IN', 'emptyCriteria' => [
                                'aggregator' => 'AND', 'values' => ['IS NOT NULL', '!= ""']
                            ]],
                        ]],
                        'Dashboards.modified' => ['type' => 'datetime', 'operators' => [
                            'is' => ['label' => 'is', 'operator' => 'IN', 'emptyCriteria' => [
                                'aggregator' => 'OR', 'valuesF' => ['IS NULL', '= ""', '= "0000-00-00 00:00:00"']
                            ]],
                            'greater' => ['label' => 'from', 'operator' => '>', 'emptyCriteria' => [
                                'aggregator' => 'AND', 'values' => ['IS NOT NULL', '!= ""', '!= "0000-00-00 00:00:00"']
                            ]]
                        ]],
                        'Dashboards.created' => ['type' => 'datetime', 'operators' => [
                            'is' => ['label' => 'is', 'operator' => 'IN', 'emptyCriteria' => [
                                'aggregator' => 'OR', 'values' => ['IS NULL', '= ""', '= "0000-00-00 00:00:00"']
                            ]],
                            'greater' => ['label' => 'from', 'operator' => '>', 'emptyCriteria' => [
                                'aggregator' => 'AND', 'values' => ['IS NOT NULL', '!= ""', '!= "0000-00-00 00:00:00"']
                            ]]
                        ]]
                    ];
                    break;

                case 'Roles':
                    $result = [
                        'Roles.name' => ['type' => 'string', 'operators' => [
                            'contains' => ['label' => 'contains', 'operator' => 'LIKE', 'pattern' => '%{{value}}%', 'emptyCriteria' => [
                                'aggregator' => 'OR', 'values' => ['IS NULL', '= ""']
                            ]]
                        ]]
                    ];
                    break;

                case 'AppWidgets':
                    $result = [
                        'AppWidgets.name' => ['type' => 'string', 'operators' => [
                            'contains' => ['label' => 'contains', 'operator' => 'LIKE', 'pattern' => '%{{value}}%', 'emptyCriteria' => [
                                'aggregator' => 'OR', 'values' => ['IS NULL', '= ""']
                            ]],
                        ]]
                    ];
                    break;

                case 'Articles':
                    $result = [
                        'Articles.title' => ['type' => 'string', 'operators' => [
                            'contains' => ['label' => 'contains', 'operator' => 'LIKE', 'pattern' => '%{{value}}%', 'emptyCriteria' => [
                                'aggregator' => 'OR', 'values' => ['IS NULL', '= ""']
                            ]]
                        ]],
                        'Articles.created' => ['type' => 'datetime', 'operators' => [
                            'is' => ['label' => 'is', 'operator' => 'IN', 'emptyCriteria' => [
                                'aggregator' => 'OR', 'values' => ['IS NULL', '= ""', '= "0000-00-00 00:00:00"']
                            ]]
                        ]],
                        'Articles.modified' => ['type' => 'datetime', 'operators' => [
                            'is' => ['label' => 'is', 'operator' => 'IN', 'emptyCriteria' => [
                                'aggregator' => 'OR', 'values' => ['IS NULL', '= ""', '= "0000-00-00 00:00:00"']
                            ]]
                        ]]
                    ];
                    break;

                case 'Authors':
                    $result = [
                        'Authors.name' => ['type' => 'string', 'operators' => [
                            'contains' => ['label' => 'contains', 'operator' => 'LIKE', 'pattern' => '%{{value}}%', 'emptyCriteria' => [
                                'aggregator' => 'OR', 'values' => ['IS NULL', '= ""']
                            ]]
                        ]]
                    ];
                    break;
            }

            return $result;
        });
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Search);
        unset($this->user);

        parent::tearDown();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInstantiateWithEmptyUser()
    {
        $user = [];
        new Search(TableRegistry::get('Dashboards'), $user);
    }

    public function testPrepareData()
    {
        $request = new ServerRequest(['post' => [
            'criteria' => ['name' => 'foo']
        ]]);

        $result = $this->Search->prepareData($request);

        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('criteria', $result);
    }

    public function testPrepareDataBasicSearch()
    {
        $request = new ServerRequest(['post' => [
            'criteria' => ['query' => 'foo']
        ]]);

        $result = $this->Search->prepareData($request);

        $this->assertArrayHasKey('criteria', $result);
        $this->assertArrayHasKey('aggregator', $result);
    }

    public function testPrepareDataBasicSearchWithoutSearchableFields()
    {
        $model = 'SomeRandomModel';

        $request = new ServerRequest(['post' => [
            'criteria' => ['query' => 'foo']
        ]]);

        $search = new Search(TableRegistry::get($model), $this->user);
        $result = $search->prepareData($request);

        $this->assertEmpty($result['criteria']);
    }

    public function testPrepareDataBasicSearchWithRelatedField()
    {
        EventManager::instance()->on('Search.Model.Search.basicSearchFields', function ($event, $table) {
            return ['Dashboards.role_id'];
        });

        $request = new ServerRequest(['post' => [
            'criteria' => ['query' => 'Lorem']
        ]]);

        $result = $this->Search->prepareData($request);

        $this->assertArrayHasKey('criteria', $result);
        $this->assertArrayHasKey('aggregator', $result);

        $expected = [
            'type' => 'related',
            'operator' => 'is',
            'value' => '79928943-0016-4677-869a-e37728ff6564'
        ];
        $this->assertContains($expected, $result['criteria']['Dashboards.role_id']);
    }

    public function testExecute()
    {
        $model = 'Dashboards';

        $data = [
            'criteria' => [
                $model . '.name' => [
                    10 => ['type' => 'string', 'operator' => 'contains', 'value' => 'ipsum']
                ]
            ],
            'display_columns' => [
                $model . '.name',
                $model . '.created',
                $model . '.modified'
            ],
            'sort_by_field' => $model . '.name',
            'sort_by_order' => 'desc',
            'limit' => '10'
        ];

        $result = $this->Search->execute($data);

        $this->assertInstanceOf(Query::class, $result);
        $this->assertGreaterThan(0, $result->count());
    }

    public function testExecuteEmptyCriteria()
    {
        $model = 'Dashboards';
        $field = 'role_id';

        $data = [
            'criteria' => [
                $model . '.' . $field => [
                    10 => ['type' => 'string', 'operator' => 'is', 'value' => '']
                ]
            ],
            'display_columns' => [
                $model . '.' . $field
            ],
            'sort_by_field' => $model . '.name',
            'sort_by_order' => 'desc',
            'limit' => '10'
        ];

        $result = $this->Search->execute($data);

        $this->assertInstanceOf(Query::class, $result);
        $this->assertEquals(1, $result->count());
        $this->assertNull($result->first()->get($field));
    }

    public function testExecuteWithAssociated()
    {
        $model = 'Articles';
        $relatedModel = 'Authors';

        $data = [
            'aggregator' => 'AND',
            'criteria' => [
                $relatedModel . '.name' => [
                    10 => ['type' => 'string', 'operator' => 'contains', 'value' => 'Mark']
                ]
            ],
            'display_columns' => [
                $model . '.title',
                $relatedModel . '.name',
                $model . '.created',
                $model . '.modified'
            ],
            'sort_by_field' => $relatedModel . '.name',
            'sort_by_order' => 'desc'
        ];

        $search = new Search(TableRegistry::get($model), $this->user);
        $result = $search->execute($data);
        $entity = $result->first();

        $this->assertInstanceOf(Query::class, $result);
        $this->assertEquals(1, $result->count());

        $this->assertNotEmpty($entity->get('id'));
        $this->assertNotEmpty($entity->get('title'));
        $this->assertNotEmpty($entity->get('_matchingData'));
        $this->assertNotEmpty($entity->get('_matchingData'));

        $associated = $entity->get('_matchingData');
        $this->assertArrayHasKey($relatedModel, $associated);
        $this->assertNotEmpty($associated[$relatedModel]->get('name'));
    }

    public function testExecuteWithDatetimeIs()
    {
        $model = 'Dashboards';

        $data = [
            'aggregator' => 'OR',
            'criteria' => [
                $model . '.modified' => [
                    10 => ['type' => 'datetime', 'operator' => 'is', 'value' => '2016-04-27 08:21:53'],
                    20 => ['type' => 'datetime', 'operator' => 'is', 'value' => '2016-04-27 08:21:54'],
                    30 => ['type' => 'datetime', 'operator' => 'is', 'value' => '2016-04-27 08:21:55']
                ]
            ]
        ];

        $result = $this->Search->execute($data);

        $this->assertEquals(2, $result->count());
    }

    public function testExecuteWithAndAggregator()
    {
        $model = 'Dashboards';

        $data = [
            'aggregator' => 'AND',
            'criteria' => [
                $model . '.name' => [
                    10 => ['type' => 'string', 'operator' => 'contains', 'value' => 'ipsum']
                ],
                $model . '.modified' => [
                    10 => ['type' => 'datetime', 'operator' => 'greater', 'value' => '2016-04-27 08:21:53']
                ]
            ]
        ];

        $result = $this->Search->execute($data);

        $this->assertEquals(1, $result->count());
    }

    public function testExecuteWithRelatedIsNot()
    {
        $model = 'Dashboards';

        $data = [
            'criteria' => [
                $model . '.role_id' => [
                    10 => ['type' => 'related', 'operator' => 'is_not', 'value' => '00000000-0000-0000-0000-000000000001'],
                    20 => ['type' => 'related', 'operator' => 'is_not', 'value' => '00000000-0000-0000-0000-000000000002'],
                    30 => ['type' => 'related', 'operator' => 'is_not', 'value' => '00000000-0000-0000-0000-000000000003']
                ]
            ]
        ];

        $result = $this->Search->execute($data);

        $this->assertEquals(1, $result->count());
    }

    public function testCreate()
    {
        $data = [
            'criteria' => [
                'name' => [
                    10 => ['type' => 'string', 'operator' => 'contains', 'value' => 'ipsum']
                ]
            ],
            'display_columns' => ['name', 'created', 'modified'],
            'sort_by_field' => 'name',
            'sort_by_order' => 'desc',
            'limit' => '10'
        ];

        $result = $this->Search->create($data);

        $this->assertNotEmpty($result);
        $this->assertInternalType('string', $result);
        $this->assertEquals(36, strlen($result));
    }

    public function testUpdate()
    {
        $model = 'Dashboards';

        $id = '00000000-0000-0000-0000-000000000001';

        $data = [
            'criteria' => [
                $model . '.name' => [
                    10 => ['type' => 'string', 'operator' => 'contains', 'value' => 'ipsum']
                ]
            ],
            'display_columns' => [$model . '.name', $model . '.created', $model . '.modified'],
            'sort_by_field' => $model . '.name',
            'sort_by_order' => 'desc',
            'limit' => '10'
        ];

        $result = $this->Search->update($data, $id);
        $this->assertInstanceOf(SavedSearch::class, $result);
        $this->assertNotEmpty($result->content);

        $content = json_decode($result->content, true);
        $this->assertArrayHasKey('latest', $content);
        $this->assertEquals($data, $content['latest']);
    }

    public function testGet()
    {
        $id = '00000000-0000-0000-0000-000000000001';

        $result = $this->Search->get($id);
        $this->assertInstanceOf(SavedSearch::class, $result);
        $this->assertNotEmpty($result->content);

        $result = json_decode($result->content, true);

        $this->assertArrayHasKey('saved', $result);
        $this->assertArrayHasKey('display_columns', $result['saved']);
        $this->assertArrayHasKey('criteria', $result['saved']);
        $this->assertArrayHasKey('sort_by_field', $result['saved']);
        $this->assertArrayHasKey('sort_by_order', $result['saved']);
        $this->assertArrayHasKey('limit', $result['saved']);

        $this->assertArrayHasKey('latest', $result);
        $this->assertArrayHasKey('display_columns', $result['latest']);
        $this->assertArrayHasKey('criteria', $result['latest']);
        $this->assertArrayHasKey('sort_by_field', $result['latest']);
        $this->assertArrayHasKey('sort_by_order', $result['latest']);
        $this->assertArrayHasKey('limit', $result['latest']);
    }

    public function testGetWhereClause()
    {
        $class = new ReflectionClass(Search::class);
        $method = $class->getMethod('getWhereClause');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->Search, [
            []
        ]);

        $this->assertEquals($result, []);
    }
}
