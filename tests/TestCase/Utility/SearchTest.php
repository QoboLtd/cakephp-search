<?php
namespace Search\Test\TestCase\Utility;

use Cake\Event\EventManager;
use Cake\Http\ServerRequest;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use ReflectionClass;
use Search\Event\EventName;
use Search\Model\Entity\SavedSearch;
use Search\Utility;
use Search\Utility\Search;

/**
 * Search\Utility\Search Test Case
 *
 * @property array $user
 * @property \Search\Utility\Search $Search
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

        $this->searchableFieldsListener();
        $this->user = ['id' => '00000000-0000-0000-0000-000000000001'];
        $this->Search = new Search(TableRegistry::get('Search.Dashboards'), $this->user);

        Utility::instance(new Utility());
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

    private function searchableFieldsListener(): void
    {
        EventManager::instance()->on((string)EventName::MODEL_SEARCH_SEARCHABLE_FIELDS(), function ($event, $table) {
            $tableName = $table->getRegistryAlias();

            $result = [];
            switch ($tableName) {
                case 'Search.Dashboards':
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
                        'Dashboards.role_id' => ['type' => 'related', 'source' => 'RolesCapabilities.Roles', 'operators' => [
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

                case 'RolesCapabilities.Roles':
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
                        ]],
                        'Authors.name' => ['type' => 'string', 'operators' => [
                            'contains' => ['label' => 'contains', 'operator' => 'LIKE', 'pattern' => '%{{value}}%', 'emptyCriteria' => [
                                'aggregator' => 'OR', 'values' => ['IS NULL', '= ""']
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
     * @expectedException InvalidArgumentException
     */
    public function testInstantiateWithEmptyUser(): void
    {
        $user = [];
        new Search(TableRegistry::get('Search.Dashboards'), $user);
    }

    public function testPrepareData(): void
    {
        $request = new ServerRequest(['post' => [
            'criteria' => ['name' => 'foo']
        ]]);

        $result = $this->Search->prepareData($request);

        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('criteria', $result);
    }

    public function testPrepareDataBasicSearch(): void
    {
        $request = new ServerRequest(['post' => [
            'criteria' => ['query' => 'foo']
        ]]);

        $result = $this->Search->prepareData($request);

        $this->assertArrayHasKey('criteria', $result);
        $this->assertArrayHasKey('aggregator', $result);
    }

    public function testPrepareDataBasicSearchWithoutSearchableFields(): void
    {
        $model = 'SomeRandomModel';

        $request = new ServerRequest(['post' => [
            'criteria' => ['query' => 'foo']
        ]]);

        $search = new Search(TableRegistry::get($model), $this->user);
        $result = $search->prepareData($request);

        $this->assertEmpty($result['criteria']);
    }

    public function testPrepareDataBasicSearchWithRelatedField(): void
    {
        EventManager::instance()->on((string)EventName::MODEL_SEARCH_BASIC_SEARCH_FIELDS(), function ($event, $table) {
            return ['Dashboards.role_id'];
        });

        $request = new ServerRequest(['post' => [
            'criteria' => ['query' => 'Everyone']
        ]]);

        $expected = [
            'criteria' => [
                'Dashboards.role_id' => [
                    [
                        'type' => 'related',
                        'operator' => 'is',
                        'value' => ['00000000-0000-0000-0000-000000000002']
                    ]
                ]
            ],
            'aggregator' => 'OR'
        ];
        $this->assertEquals($expected, $this->Search->prepareData($request));
    }

    public function testExecute(): void
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

    public function testExecuteEmptyCriteria(): void
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

        /**
         * @var \Cake\ORM\Query
         */
        $result = $this->Search->execute($data);

        $this->assertInstanceOf(Query::class, $result);
        $this->assertEquals(1, $result->count());
        /**
         * @var \Cake\Datasource\EntityInterface
         */
        $entity = $result->firstOrFail();
        $this->assertNull($entity->get($field));
    }

    public function testExecuteWithAssociated(): void
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
        /**
         * @var \Cake\ORM\Query
         */
        $result = $search->execute($data);

        /**
         * @var \Cake\Datasource\EntityInterface
         */
        $entity = $result->firstOrFail();

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

    public function testExecuteWithDatetimeIs(): void
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

    public function testExecuteWithAndAggregator(): void
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

    public function testExecuteWithRelatedIsNot(): void
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

    public function testExecuteWithRelatedValueArray(): void
    {
        $model = 'Dashboards';

        $data = [
            'criteria' => [
                $model . '.role_id' => [
                    10 => ['type' => 'related', 'operator' => 'is', 'value' => ['00000000-0000-0000-0000-000000000001', '00000000-0000-0000-0000-000000000002']]
                ]
            ]
        ];

        $result = $this->Search->execute($data);

        $this->assertEquals(2, $result->count());
    }

    public function testCreate(): void
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

    public function testUpdate(): void
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

        /**
         * @var \Cake\Datasource\EntityInterface
         */
        $result = $this->Search->update($data, $id);
        $this->assertInstanceOf(SavedSearch::class, $result);
        $this->assertNotEmpty($result->get('content'));

        $content = json_decode($result->get('content'), true);
        $this->assertArrayHasKey('latest', $content);
        $this->assertEquals($data, $content['latest']);
    }

    public function testGet(): void
    {
        $id = '00000000-0000-0000-0000-000000000001';

        $result = $this->Search->get($id);
        $this->assertInstanceOf(SavedSearch::class, $result);
        $this->assertNotEmpty($result->get('content'));

        $result = json_decode($result->get('content'), true);

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

    public function testGetWhereClause(): void
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
