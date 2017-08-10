<?php
namespace Search\Test\TestCase\Model\Table;

use Cake\Event\EventList;
use Cake\Event\EventManager;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use RuntimeException;
use Search\Model\Table\SavedSearchesTable;
use Search\Utility;

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
        'plugin.search.app_widgets',
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

        Utility::instance(new Utility());

        $config = TableRegistry::exists('SavedSearches') ? [] : ['className' => 'Search\Model\Table\SavedSearchesTable'];
        $this->SavedSearches = TableRegistry::get('SavedSearches', $config);

        EventManager::instance()->on('Search.Model.Search.searchabeFields', function ($event, $table) {
            $tableName = $table->getRegistryAlias();

            $result = [];
            switch ($tableName) {
                case 'Dashboards':
                    $result = [
                        'Dashboards.name' => [
                            'type' => 'string',
                            'operators' => [
                                'contains' => ['label' => 'contains', 'operator' => 'LIKE', 'pattern' => '%{{value}}%']
                            ]
                        ],
                        'Dashboards.image' => [
                            'type' => 'blob',
                            'operators' => [
                                'contains' => ['label' => 'contains', 'operator' => 'LIKE', 'pattern' => '%{{value}}%'],
                            ]
                        ],
                        'Dashboards.role_id' => [
                            'type' => 'related',
                            'source' => 'Roles',
                            'operators' => [
                                'contains' => ['label' => 'contains', 'operator' => 'LIKE', 'pattern' => '%{{value}}%'],
                                'is_not' => ['label' => 'is not', 'operator' => 'NOT IN'],
                            ]
                        ],
                        'Dashboards.modified' => [
                            'type' => 'datetime',
                            'operators' => [
                                'is' => ['label' => 'is', 'operator' => 'IN'],
                                'greater' => ['label' => 'from', 'operator' => '>']
                            ]
                        ],
                        'Dashboards.created' => [
                            'type' => 'datetime',
                            'operators' => [
                                'is' => ['label' => 'is', 'operator' => 'IN'],
                                'greater' => ['label' => 'from', 'operator' => '>']
                            ]
                        ]
                    ];
                    break;

                case 'Roles':
                    $result = [
                        'Roles.name' => [
                            'type' => 'string',
                            'operators' => [
                                'contains' => ['label' => 'contains', 'operator' => 'LIKE', 'pattern' => '%{{value}}%']
                            ]
                        ]
                    ];
                    break;

                case 'AppWidgets':
                    $result = [
                        'AppWidgets.name' => [
                            'type' => 'string',
                            'operators' => [
                                'contains' => ['label' => 'contains', 'operator' => 'LIKE', 'pattern' => '%{{value}}%'],
                            ]
                        ]
                    ];
                    break;

                case 'Articles':
                    $result = [
                        'Articles.title' => [
                            'type' => 'string',
                            'operators' => [
                                'contains' => ['label' => 'contains', 'operator' => 'LIKE', 'pattern' => '%{{value}}%']
                            ]
                        ],
                        'Articles.created' => [
                            'type' => 'datetime',
                            'operators' => [
                                'is' => ['label' => 'is', 'operator' => 'IN']
                            ]
                        ],
                        'Articles.modified' => [
                            'type' => 'datetime',
                            'operators' => [
                                'is' => ['label' => 'is', 'operator' => 'IN']
                            ]
                        ]
                    ];
                    break;

                case 'Authors':
                    $result = [
                        'Authors.name' => [
                            'type' => 'string',
                            'operators' => [
                                'contains' => ['label' => 'contains', 'operator' => 'LIKE', 'pattern' => '%{{value}}%']
                            ]
                        ]
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

    public function testGetListingFields()
    {
        $model = 'Dashboards';
        $expected = [$model . '.name', $model . '.modified', $model . '.created'];
        $result = $this->SavedSearches->getListingFields(TableRegistry::get($model));

        $this->assertNotEmpty($result);
        $this->assertEquals($result, $expected);
    }

    public function testGetListingFieldsDatabaseColumns()
    {
        $model = 'Dashboards';

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

        $result = $this->SavedSearches->prepareData($request, TableRegistry::get($model), $user);

        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('criteria', $result);
    }

    public function testPrepareDataBasicSearch()
    {
        $model = 'Dashboards';

        $request = new ServerRequest([
            'post' => [
                'criteria' => ['query' => 'foo']
            ]
        ]);
        $user = ['id' => '00000000-0000-0000-0000-000000000001'];

        $result = $this->SavedSearches->prepareData($request, TableRegistry::get($model), $user);

        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('criteria', $result);
        $this->assertArrayHasKey('aggregator', $result);
    }

    public function testPrepareDataBasicSearchWithRelatedField()
    {
        $model = 'Dashboards';
        $relatedModel = 'AppWidgets';

        EventManager::instance()->on('Search.Model.Search.basicSearchFields', function ($event, $table) {
            return ['Dashboards.role_id'];
        });

        $request = new ServerRequest([
            'post' => [
                'criteria' => ['query' => 'Lorem']
            ]
        ]);
        $user = ['id' => '00000000-0000-0000-0000-000000000001'];

        $result = $this->SavedSearches->prepareData($request, TableRegistry::get($model), $user);

        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('criteria', $result);
        $this->assertArrayHasKey('aggregator', $result);

        $expected = [
            'type' => 'related',
            'operator' => 'contains',
            'value' => '79928943-0016-4677-869a-e37728ff6564'
        ];
        $this->assertContains($expected, $result['criteria']['Dashboards.role_id']);
    }

    public function dataProviderGetBasicSearchCriteria()
    {
        return [
            [['query' => 'SELECT id,created FROM dashboards LIMIT 2', 'table' => 'Dashboards']],
        ];
    }

    public function test_prepareWhereStatement()
    {
        $user = ['id' => '00000000-0000-0000-0000-000000000001'];
        $class = new \ReflectionClass('Search\Model\Table\SavedSearchesTable');
        $method = $class->getMethod('_prepareWhereStatement');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->SavedSearches, [
            [],
            TableRegistry::get('Dashboards'),
            $user
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

        $data = [
            'criteria' => [
                $model . '.name' => [
                    10 => ['type' => 'string', 'operator' => 'contains', 'value' => 'foo']
                ]
            ],
            'display_columns' => [$model . '.name', $model . '.modified', $model . '.created'],
            'sort_by_field' => $model . '.name',
            'sort_by_order' => 'asc',
            'limit' => '20',
            'aggregator' => 'AND'
        ];

        $user = ['id' => '00000000-0000-0000-0000-000000000001'];

        $result = $this->SavedSearches->validateData(TableRegistry::get($model), $data, $user);
        $this->assertEquals($data, $result);
    }

    public function testValidateDataWrong()
    {
        $model = 'Dashboards';

        $data = [
            'criteria' => [
                'foo' => [
                    10 => ['type' => 'string', 'operator' => 'contains', 'value' => 'foo']
                ]
            ],
            'display_columns' => ['foo'],
            'sort_by_field' => 'foo',
            'sort_by_order' => 'foo',
            'limit' => '999',
            'aggregator' => 'foo'
        ];

        $user = ['id' => '00000000-0000-0000-0000-000000000001'];

        $result = $this->SavedSearches->validateData(TableRegistry::get($model), $data, $user);

        $this->assertEmpty($result['criteria']);
        $this->assertEmpty($result['display_columns']);

        $table = TableRegistry::get($model);
        $expected = $table->aliasField($table->getDisplayField());
        $this->assertEquals($expected, $result['sort_by_field']);

        $expected = $this->SavedSearches->getDefaultSortByOrder();
        $this->assertEquals($expected, $result['sort_by_order']);

        $expected = $this->SavedSearches->getDefaultAggregator();
        $this->assertEquals($expected, $result['aggregator']);
    }

    public function testSearch()
    {
        $model = 'Dashboards';

        $user = ['id' => '00000000-0000-0000-0000-000000000001'];

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

        $result = $this->SavedSearches->search(TableRegistry::get($model), $user, $data);

        $this->assertInstanceOf(\Cake\ORM\Query::class, $result);
        $this->assertGreaterThan(0, $result->count());
    }

    public function testSearchWithAssociated()
    {
        $model = 'Articles';
        $relatedModel = 'Authors';

        $user = ['id' => '00000000-0000-0000-0000-000000000001'];

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

        $result = $this->SavedSearches->search(TableRegistry::get($model), $user, $data);
        $entity = $result->first();

        $this->assertInstanceOf(\Cake\ORM\Query::class, $result);
        $this->assertEquals(1, $result->count());

        $this->assertNotEmpty($entity->get('id'));
        $this->assertNotEmpty($entity->get('title'));
        $this->assertNotEmpty($entity->get('_matchingData'));
        $this->assertNotEmpty($entity->get('_matchingData'));

        $associated = $entity->get('_matchingData');
        $this->assertArrayHasKey($relatedModel, $associated);
        $this->assertNotEmpty($associated[$relatedModel]->get('name'));
    }

    public function testSearchWithDatetimeIs()
    {
        $model = 'Dashboards';

        $user = ['id' => '00000000-0000-0000-0000-000000000001'];

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

        $result = $this->SavedSearches->search(TableRegistry::get($model), $user, $data);

        $this->assertEquals(2, $result->count());
    }

    public function testSearchWithAndAggregator()
    {
        $model = 'Dashboards';

        $user = ['id' => '00000000-0000-0000-0000-000000000001'];

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

        $result = $this->SavedSearches->search(TableRegistry::get($model), $user, $data);

        $this->assertEquals(1, $result->count());
    }

    public function testSearchWithRelatedIsNot()
    {
        $model = 'Dashboards';

        $user = ['id' => '00000000-0000-0000-0000-000000000001'];

        $data = [
            'criteria' => [
                $model . '.role_id' => [
                    10 => ['type' => 'related', 'operator' => 'is_not', 'value' => '00000000-0000-0000-0000-000000000001'],
                    20 => ['type' => 'related', 'operator' => 'is_not', 'value' => '00000000-0000-0000-0000-000000000002'],
                    30 => ['type' => 'related', 'operator' => 'is_not', 'value' => '00000000-0000-0000-0000-000000000003']
                ]
            ]
        ];

        $result = $this->SavedSearches->search(TableRegistry::get($model), $user, $data);

        $this->assertEquals(1, $result->count());
    }

    public function testCreateSearch()
    {
        $user = ['id' => '00000000-0000-0000-0000-000000000001'];

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

        $result = $this->SavedSearches->createSearch(TableRegistry::get('Dashboards'), $user, $data);

        $this->assertNotEmpty($result);
        $this->assertInternalType('string', $result);
        $this->assertEquals(36, strlen($result));
    }

    public function testUpdateSearch()
    {
        $model = 'Dashboards';

        $id = '00000000-0000-0000-0000-000000000001';

        $user = ['id' => '00000000-0000-0000-0000-000000000001'];

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

        $result = $this->SavedSearches->updateSearch(TableRegistry::get($model), $user, $data, $id);
        $this->assertInstanceOf(\Search\Model\Entity\SavedSearch::class, $result);
        $this->assertNotEmpty($result->content);

        $content = json_decode($result->content, true);
        $this->assertArrayHasKey('latest', $content);
        $this->assertEquals($data, $content['latest']);
    }

    public function testGetSearch()
    {
        $id = '00000000-0000-0000-0000-000000000001';

        $user = ['id' => '00000000-0000-0000-0000-000000000001'];

        $result = $this->SavedSearches->getSearch(TableRegistry::get('Dashboards'), $user, $id);
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
