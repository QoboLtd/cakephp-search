<?php
namespace Search\Test\TestCase\Model\Table;

use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use RuntimeException;
use Search\Model\Table\SavedSearchesTable;

/**
 * Search\Model\Table\SavedSearchesTable Test Case
 */
class SavedSearchesTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \Search\Model\Table\SavedSearchesTable
     */
    public $SavedSearches;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.search.dashboards',
        'plugin.search.app_widgets',
        'plugin.search.saved_searches',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('SavedSearches') ? [] : ['className' => 'Search\Model\Table\SavedSearchesTable'];
        $this->SavedSearches = TableRegistry::get('SavedSearches', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->SavedSearches);

        parent::tearDown();
    }

    public function testValidationDefault()
    {
        $validator = new \Cake\Validation\Validator();
        $result = $this->SavedSearches->validationDefault($validator);

        $this->assertInstanceOf('\Cake\Validation\Validator', $result);
    }

    public function testBuildRules()
    {
        $rules = new \Cake\ORM\RulesChecker();
        $result = $this->SavedSearches->buildRules($rules);

        $this->assertInstanceOf('\Cake\ORM\RulesChecker', $result);
    }

    public function testGetDefaultSortByOrder()
    {
        $result = $this->SavedSearches->getDefaultSortByOrder();
        $this->assertEquals($result, 'desc');
    }

    public function testGetPrivateSharedStatus()
    {
        $result = $this->SavedSearches->getPrivateSharedStatus();
        $this->assertEquals('private', $result);
    }

    public function testGetSkippedDisplayFields()
    {
        $expected = ['id'];
        $result = $this->SavedSearches->getSkippedDisplayFields();
        $this->assertEquals($expected, $result);
    }

    public function testGetDefaultDisplayFields()
    {
        $expected = ['modified', 'created'];
        $result = $this->SavedSearches->getDefaultDisplayFields();
        $this->assertEquals($expected, $result);
    }

    public function testGetSearchOptions()
    {
        $result = $this->SavedSearches->getSearchOptions();
        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('sortByOrder', $result);
        $this->assertArrayHasKey('aggregators', $result);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetSearchableFields()
    {
        $result = $this->SavedSearches->getSearchableFields('Widgets');
        $this->assertEventFired('Search.Model.Search.searchabeFields', $this->EventManager());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetSearchableFieldsWrongVarType()
    {
        $result = $this->SavedSearches->getSearchableFields(['Widgets']);
    }

    public function testGetListingFields()
    {
        $model = 'Dashboards';
        $expected = [$model . '.name', $model . '.modified', $model . '.created'];
        $result = $this->SavedSearches->getListingFields($model);

        $this->assertNotEmpty($result);
        $this->assertEquals($result, $expected);
    }

    public function testGetListingFieldsDatabaseColumns()
    {
        $model = 'Dashboards';
        // anonymous event listener that passes some dummy searchable fields
        $this->SavedSearches->eventManager()->on('Search.Model.Search.searchabeFields', function ($event, $table) {
            return [
                'name' => [
                    'type' => 'blob',
                    'operators' => [
                        'contains' => [
                            'label' => 'contains',
                            'operator' => 'LIKE',
                            'pattern' => '%{{value}}%'
                        ],
                    ]
                ]
            ];
        });

        $table = TableRegistry::get($model);
        $table->setDisplayField('virtual_field');
        $result = $this->SavedSearches->getListingFields($table);
        $this->assertNotEmpty($result);
        $this->assertEquals($result, [$model . '.modified', $model . '.created']);
    }

    public function testIsEditable()
    {
        $entity = $this->SavedSearches->get('00000000-0000-0000-0000-000000000001');
        $result = $this->SavedSearches->isEditable($entity);

        $this->assertTrue($result);
    }

    public function testPrepareData()
    {
        $request = new ServerRequest([
            'post' => [
                'criteria' => ['name' => 'foo']
            ]
        ]);
        $model = 'Dashboards';
        $user = ['id' => '00000000-0000-0000-0000-000000000001'];

        $result = $this->SavedSearches->prepareData($request, $model, $user);

        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('criteria', $result);
    }

    public function testPrepareDataBasicSearch()
    {
        $model = 'Dashboards';

        // anonymous event listener that passes some dummy searchable fields
        $this->SavedSearches->eventManager()->on(
            'Search.Model.Search.searchabeFields',
            function ($event, $table) use ($model) {
                return [
                    $model . '.name' => [
                        'type' => 'string',
                        'operators' => [
                            'contains' => [
                                'label' => 'contains',
                                'operator' => 'LIKE',
                                'pattern' => '%{{value}}%'
                            ],
                        ]
                    ]
                ];
            }
        );

        $request = new ServerRequest([
            'post' => [
                'criteria' => ['query' => 'foo']
            ]
        ]);
        $user = ['id' => '00000000-0000-0000-0000-000000000001'];

        $result = $this->SavedSearches->prepareData($request, $model, $user);

        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('criteria', $result);
        $this->assertArrayHasKey('aggregator', $result);
    }

    public function testPrepareDataBasicSearchWithRelatedField()
    {
        $model = 'Dashboards';
        $relatedModel = 'AppWidgets';

        // anonymous event listener that passes some dummy searchable fields
        $this->SavedSearches->eventManager()->on(
            'Search.Model.Search.searchabeFields',
            function ($event, $table) use ($model, $relatedModel) {
                if ($relatedModel === $table->getRegistryAlias()) {
                    return [
                        $relatedModel . '.name' => [
                            'type' => 'string',
                            'operators' => [
                                'contains' => [
                                    'label' => 'contains',
                                    'operator' => 'LIKE',
                                    'pattern' => '%{{value}}%'
                                ],
                            ]
                        ]
                    ];
                }

                return [
                    $model . '.name' => [
                        'type' => 'related',
                        'source' => 'AppWidgets',
                        'operators' => [
                            'contains' => [
                                'label' => 'contains',
                                'operator' => 'LIKE',
                                'pattern' => '%{{value}}%'
                            ],
                        ]
                    ]
                ];
            }
        );

        $request = new ServerRequest([
            'post' => [
                'criteria' => ['query' => 'Hello']
            ]
        ]);
        $user = ['id' => '00000000-0000-0000-0000-000000000001'];

        $result = $this->SavedSearches->prepareData($request, $model, $user);

        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('criteria', $result);
        $this->assertArrayHasKey('aggregator', $result);

        $expected = [
            'type' => 'related',
            'operator' => 'contains',
            'value' => '00000000-0000-0000-0000-000000000001'
        ];
        $this->assertContains($expected, $result['criteria'][$model . '.name']);
    }

    public function dataProviderGetBasicSearchCriteria()
    {
        return [
            [['query' => 'SELECT id,created FROM dashboards LIMIT 2', 'table' => 'Dashboards']],
        ];
    }

    public function test_prepareWhereStatement()
    {
        $class = new \ReflectionClass('Search\Model\Table\SavedSearchesTable');
        $method = $class->getMethod('_prepareWhereStatement');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->SavedSearches, [
            [],
            TableRegistry::get('Dashboards')
        ]);

        $this->assertEquals($result, []);
    }

    public function testGetSavedSearchesFindAll()
    {
        $resultset = $this->SavedSearches->getSavedSearches();
        $this->assertInternalType('array', $resultset);
        $this->assertInstanceOf('\Search\Model\Entity\SavedSearch', current($resultset));
    }

    public function testGetSavedSearchesByUser()
    {
        $records = $this->fixtureManager->loaded()['plugin.search.saved_searches']->records;
        $userId = current($records)['user_id'];
        $resultset = $this->SavedSearches->getSavedSearches([$userId]);
        $this->assertInternalType('array', $resultset);
        $this->assertInstanceOf('\Search\Model\Entity\SavedSearch', current($resultset));

        foreach ($resultset as $entity) {
            $this->assertEquals($userId, $entity->user_id);
        }
    }

    public function testGetSavedSearchesByModel()
    {
        $records = $this->fixtureManager->loaded()['plugin.search.saved_searches']->records;
        $model = current($records)['model'];
        $resultset = $this->SavedSearches->getSavedSearches([], [$model]);
        $this->assertInternalType('array', $resultset);
        $this->assertInstanceOf('\Search\Model\Entity\SavedSearch', current($resultset));

        foreach ($resultset as $entity) {
            $this->assertEquals($model, $entity->model);
        }
    }

    public function testGetSortByOrderOptions()
    {
        $result = $this->SavedSearches->getSortByOrderOptions();
        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
    }

    public function testValidateData()
    {
        $model = 'Dashboards';

        // anonymous event listener that passes some dummy searchable fields
        $this->SavedSearches->eventManager()->on(
            'Search.Model.Search.searchabeFields',
            function ($event, $table) use ($model) {
                return [
                    $model . '.name' => ['type' => 'string'],
                    $model . '.modified' => ['type' => 'datetime'],
                    $model . '.created' => ['type' => 'datetime']
                ];
            }
        );

        $data = [
            'criteria' => [
                $model . '.name' => [
                    10 => [
                        'type' => 'string',
                        'operator' => 'contains',
                        'value' => 'foo'
                    ]
                ]
            ],
            'display_columns' => [
                $model . '.name', $model . '.modified', $model . '.created'
            ],
            'sort_by_field' => $model . '.name',
            'sort_by_order' => 'asc',
            'limit' => '20',
            'aggregator' => 'AND'
        ];
        $result = $this->SavedSearches->validateData($model, $data);
        $this->assertEquals($data, $result);
    }

    public function testValidateDataWrong()
    {
        $model = 'Dashboards';

        // anonymous event listener that passes some dummy searchable fields
        $this->SavedSearches->eventManager()->on(
            'Search.Model.Search.searchabeFields',
            function ($event, $table) use ($model) {
                return [
                    $model . '.name' => ['type' => 'string'],
                    $model . '.modified' => ['type' => 'datetime'],
                    $model . '.created' => ['type' => 'datetime']
                ];
            }
        );

        $data = [
            'criteria' => [
                'foo' => [
                    10 => [
                        'type' => 'string',
                        'operator' => 'contains',
                        'value' => 'foo'
                    ]
                ]
            ],
            'display_columns' => [
                'foo'
            ],
            'sort_by_field' => 'foo',
            'sort_by_order' => 'foo',
            'limit' => '999',
            'aggregator' => 'foo'
        ];
        $result = $this->SavedSearches->validateData($model, $data);

        $this->assertEmpty($result['criteria']);
        $this->assertEmpty($result['display_columns']);

        $expected = TableRegistry::get($model)->displayField();
        $this->assertEquals($expected, $result['sort_by_field']);

        $expected = $this->SavedSearches->getDefaultSortByOrder();
        $this->assertEquals($expected, $result['sort_by_order']);

        $expected = $this->SavedSearches->getDefaultAggregator();
        $this->assertEquals($expected, $result['aggregator']);
    }

    public function testSearch()
    {
        $model = 'Dashboards';

        // anonymous event listener that passes some dummy searchable fields
        $this->SavedSearches->eventManager()->on(
            'Search.Model.Search.searchabeFields',
            function ($event, $table) use ($model) {
                return [
                    $model . '.name' => [
                        'type' => 'string',
                        'operators' => [
                            'contains' => [
                                'label' => 'contains',
                                'operator' => 'LIKE',
                                'pattern' => '%{{value}}%'
                            ]
                        ]
                    ]
                ];
            }
        );

        $user = [
            'id' => '00000000-0000-0000-0000-000000000001'
        ];

        $data = [
            'criteria' => [
                $model . '.name' => [
                    10 => [
                        'type' => 'string',
                        'operator' => 'contains',
                        'value' => 'ipsum'
                    ]
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

        $result = $this->SavedSearches->search($model, $user, $data);

        $this->assertInstanceOf(\Cake\ORM\Query::class, $result);
        $this->assertGreaterThan(0, $result->count());
    }

    public function testSearchWithDatetimeIs()
    {
        $model = 'Dashboards';

        // anonymous event listener that passes some dummy searchable fields
        $this->SavedSearches->eventManager()->on(
            'Search.Model.Search.searchabeFields',
            function ($event, $table) use ($model) {
                return [
                    $model . '.modified' => [
                        'type' => 'datetime',
                        'operators' => [
                            'is' => [
                                'label' => 'is',
                                'operator' => 'IN'
                            ]
                        ]
                    ]
                ];
            }
        );

        $user = [
            'id' => '00000000-0000-0000-0000-000000000001'
        ];

        $data = [
            'criteria' => [
                $model . '.modified' => [
                    10 => [
                        'type' => 'datetime',
                        'operator' => 'is',
                        'value' => '2016-04-27 08:21:53'
                    ],
                    20 => [
                        'type' => 'datetime',
                        'operator' => 'is',
                        'value' => '2016-04-27 08:21:54'
                    ],
                    30 => [
                        'type' => 'datetime',
                        'operator' => 'is',
                        'value' => '2016-04-27 08:21:55'
                    ]
                ]
            ]
        ];

        $result = $this->SavedSearches->search($model, $user, $data);

        $this->assertEquals(2, $result->count());
    }

    public function testSearchWithRelatedIsNot()
    {
        $model = 'Dashboards';

        // anonymous event listener that passes some dummy searchable fields
        $this->SavedSearches->eventManager()->on(
            'Search.Model.Search.searchabeFields',
            function ($event, $table) use ($model) {
                return [
                    $model . '.role_id' => [
                        'type' => 'related',
                        'operators' => [
                            'is_not' => [
                                'label' => 'is not',
                                'operator' => 'NOT IN'
                            ]
                        ]
                    ]
                ];
            }
        );

        $user = [
            'id' => '00000000-0000-0000-0000-000000000001'
        ];

        $data = [
            'criteria' => [
                $model . '.role_id' => [
                    10 => [
                        'type' => 'related',
                        'operator' => 'is_not',
                        'value' => '00000000-0000-0000-0000-000000000001'
                    ],
                    20 => [
                        'type' => 'related',
                        'operator' => 'is_not',
                        'value' => '00000000-0000-0000-0000-000000000002'
                    ],
                    30 => [
                        'type' => 'related',
                        'operator' => 'is_not',
                        'value' => '00000000-0000-0000-0000-000000000003'
                    ]
                ]
            ]
        ];

        $result = $this->SavedSearches->search($model, $user, $data);

        $this->assertEquals(1, $result->count());
    }

    public function testCreateSearch()
    {
        // anonymous event listener that passes some dummy searchable fields
        $this->SavedSearches->eventManager()->on('Search.Model.Search.searchabeFields', function ($event, $table) {
            return [
                'name' => [
                    'type' => 'string',
                    'operators' => [
                        'contains' => [
                            'label' => 'contains',
                            'operator' => 'LIKE',
                            'pattern' => '%{{value}}%'
                        ]
                    ]
                ]
            ];
        });

        $user = [
            'id' => '00000000-0000-0000-0000-000000000001'
        ];

        $data = [
            'criteria' => [
                'name' => [
                    10 => [
                        'type' => 'string',
                        'operator' => 'contains',
                        'value' => 'ipsum'
                    ]
                ]
            ],
            'display_columns' => [
                'name',
                'created',
                'modified'
            ],
            'sort_by_field' => 'name',
            'sort_by_order' => 'desc',
            'limit' => '10'
        ];

        $result = $this->SavedSearches->createSearch('Dashboards', $user, $data);

        $this->assertNotEmpty($result);
        $this->assertInternalType('string', $result);
        $this->assertEquals(36, strlen($result));
    }

    public function testUpdateSearch()
    {
        $model = 'Dashboards';

        // anonymous event listener that passes some dummy searchable fields
        $this->SavedSearches->eventManager()->on(
            'Search.Model.Search.searchabeFields',
            function ($event, $table) use ($model) {
                return [
                    $model . '.name' => [
                        'type' => 'string',
                        'operators' => [
                            'contains' => [
                                'label' => 'contains',
                                'operator' => 'LIKE',
                                'pattern' => '%{{value}}%'
                            ]
                        ]
                    ]
                ];
            }
        );

        $id = '00000000-0000-0000-0000-000000000001';

        $user = [
            'id' => '00000000-0000-0000-0000-000000000001'
        ];

        $data = [
            'criteria' => [
                $model . '.name' => [
                    10 => [
                        'type' => 'string',
                        'operator' => 'contains',
                        'value' => 'ipsum'
                    ]
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

        $result = $this->SavedSearches->updateSearch($model, $user, $data, $id);
        $this->assertInstanceOf(\Search\Model\Entity\SavedSearch::class, $result);
        $this->assertNotEmpty($result->content);

        $content = json_decode($result->content, true);
        $this->assertArrayHasKey('latest', $content);
        $this->assertEquals($data, $content['latest']);
    }

    public function testGetSearch()
    {
        // anonymous event listener that passes some dummy searchable fields
        $this->SavedSearches->eventManager()->on('Search.Model.Search.searchabeFields', function ($event, $table) {
            return [
                'name' => [
                    'type' => 'string',
                    'operators' => [
                        'contains' => [
                            'label' => 'contains',
                            'operator' => 'LIKE',
                            'pattern' => '%{{value}}%'
                        ]
                    ]
                ]
            ];
        });

        $id = '00000000-0000-0000-0000-000000000001';

        $user = [
            'id' => '00000000-0000-0000-0000-000000000001'
        ];

        $result = $this->SavedSearches->getSearch('Dashboards', $user, $id);
        $this->assertInstanceOf(\Search\Model\Entity\SavedSearch::class, $result);
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
}
