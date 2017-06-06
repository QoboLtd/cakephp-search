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

    public function testGetCriteriaType()
    {
        $result = $this->SavedSearches->getCriteriaType();
        $this->assertEquals('criteria', $result);
    }

    public function testGetResultType()
    {
        $result = $this->SavedSearches->getResultType();
        $this->assertEquals('result', $result);
    }

    public function testGetDefaultSortByOrder()
    {
        $result = $this->SavedSearches->getDefaultSortByOrder();
        $this->assertEquals($result, 'desc');
    }

    public function testGetDefaultLimit()
    {
        $this->assertEquals($this->SavedSearches->getDefaultLimit(), 100);
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

    public function testGetSearchOptions()
    {
        $result = $this->SavedSearches->getSearchOptions();
        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('limit', $result);
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
        $result = $this->SavedSearches->getListingFields('Dashboards');
        $this->assertNotEmpty($result);
        $this->assertEquals($result, ['name']);
    }

    public function testGetListingFieldsDatabaseColumns()
    {
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

        $table = TableRegistry::get('Dashboards');
        $table->setDisplayField('virtual_field');
        $result = $this->SavedSearches->getListingFields($table);
        $this->assertNotEmpty($result);
        $this->assertEquals($result, ['modified', 'created']);
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
                        ],
                    ]
                ]
            ];
        });

        $request = new ServerRequest([
            'post' => [
                'criteria' => ['query' => 'foo']
            ]
        ]);
        $model = 'Dashboards';
        $user = ['id' => '00000000-0000-0000-0000-000000000001'];

        $result = $this->SavedSearches->prepareData($request, $model, $user);

        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('criteria', $result);
        $this->assertArrayHasKey('aggregator', $result);
    }

    public function testPrepareDataBasicSearchWithRelatedField()
    {
        // anonymous event listener that passes some dummy searchable fields
        $this->SavedSearches->eventManager()->on('Search.Model.Search.searchabeFields', function ($event, $table) {
            if ('AppWidgets' === $table->getRegistryAlias()) {
                return [
                    'name' => [
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
                'name' => [
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
        });

        $request = new ServerRequest([
            'post' => [
                'criteria' => ['query' => 'Hello']
            ]
        ]);
        $model = 'Dashboards';
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
        $this->assertContains($expected, $result['criteria']['name']);
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
            'Dashboards'
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

    public function testGetLimitOptions()
    {
        $result = $this->SavedSearches->getLimitOptions();
        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
    }

    public function testGetSortByOrderOptions()
    {
        $result = $this->SavedSearches->getSortByOrderOptions();
        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
    }

    public function testValidateData()
    {
        // anonymous event listener that passes some dummy searchable fields
        $this->SavedSearches->eventManager()->on('Search.Model.Search.searchabeFields', function ($event, $table) {
            return [
                'name' => [],
                'modified' => [],
                'created' => []
            ];
        });

        $data = [
            'criteria' => [
                'name' => [
                    10 => [
                        'type' => 'string',
                        'operator' => 'contains',
                        'value' => 'foo'
                    ]
                ]
            ],
            'display_columns' => [
                'name', 'modified', 'created'
            ],
            'sort_by_field' => 'name',
            'sort_by_order' => 'asc',
            'limit' => '20',
            'aggregator' => 'AND'
        ];
        $result = $this->SavedSearches->validateData('Dashboards', $data);
        $this->assertEquals($data, $result);
    }

    public function testValidateDataWrong()
    {
        // anonymous event listener that passes some dummy searchable fields
        $this->SavedSearches->eventManager()->on(
            'Search.Model.Search.searchabeFields',
            function ($event, $table) {
                return [
                    'name' => [],
                    'modified' => [],
                    'created' => []
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
        $result = $this->SavedSearches->validateData('Dashboards', $data);

        $this->assertEmpty($result['criteria']);
        $this->assertEmpty($result['display_columns']);

        $expected = TableRegistry::get('Dashboards')->displayField();
        $this->assertEquals($expected, $result['sort_by_field']);

        $expected = $this->SavedSearches->getDefaultSortByOrder();
        $this->assertEquals($expected, $result['sort_by_order']);

        $expected = $this->SavedSearches->getDefaultLimit();
        $this->assertEquals($expected, $result['limit']);

        $expected = $this->SavedSearches->getDefaultAggregator();
        $this->assertEquals($expected, $result['aggregator']);
    }

    public function testSearch()
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

        $result = $this->SavedSearches->search('Dashboards', $user, $data);

        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);

        $this->assertArrayHasKey('preSaveId', $result);

        $this->assertNotEmpty($result['entities']);
        $this->assertEquals($data['criteria'], $result['entities']['criteria']);
        $this->assertEquals($data['sort_by_field'], $result['entities']['sort_by_field']);
        $this->assertEquals($data['sort_by_order'], $result['entities']['sort_by_order']);
        $this->assertEquals($data['limit'], $result['entities']['limit']);
        $this->assertNotEquals($data['display_columns'], $result['entities']['display_columns']);

        $this->assertNotEmpty($result['entities']['result']);
        $this->assertInstanceOf(\Cake\ORM\ResultSet::class, $result['entities']['result']);
        $this->assertGreaterThan(0, $result['entities']['result']->count());
    }

    public function testSearchWithDatetimeIs()
    {
        // anonymous event listener that passes some dummy searchable fields
        $this->SavedSearches->eventManager()->on('Search.Model.Search.searchabeFields', function ($event, $table) {
            return [
                'modified' => [
                    'type' => 'datetime',
                    'operators' => [
                        'is' => [
                            'label' => 'is',
                            'operator' => 'IN'
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
                'modified' => [
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

        $result = $this->SavedSearches->search('Dashboards', $user, $data);

        $this->assertNotEmpty($result['entities']['result']);
        $this->assertEquals(2, $result['entities']['result']->count());
    }

    public function testSearchWithRelatedIsNot()
    {
        // anonymous event listener that passes some dummy searchable fields
        $this->SavedSearches->eventManager()->on('Search.Model.Search.searchabeFields', function ($event, $table) {
            return [
                'role_id' => [
                    'type' => 'related',
                    'operators' => [
                        'is_not' => [
                            'label' => 'is not',
                            'operator' => 'NOT IN'
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
                'role_id' => [
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

        $result = $this->SavedSearches->search('Dashboards', $user, $data);

        $this->assertNotEmpty($result['entities']['result']);
        $this->assertEquals(1, $result['entities']['result']->count());
    }

    public function testNewSearch()
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

        $result = $this->SavedSearches->newSearch('Dashboards', $user, $data);

        $this->assertNotEmpty($result);
        $this->assertInternalType('string', $result);
        $this->assertEquals(36, strlen($result));
    }

    public function testExistingSearch()
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

        $result = $this->SavedSearches->existingSearch('Dashboards', $user, $data, $id);
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

        $content = json_decode($result->content, true);
        $this->assertArrayHasKey('saved', $content);
        $this->assertArrayHasKey('latest', $content);
    }
}
