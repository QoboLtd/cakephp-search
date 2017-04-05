<?php
namespace Search\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
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
        $this->assertEquals($this->SavedSearches->getDefaultLimit(), 10);
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

    /**
     * @expectedException \RuntimeException
     */
    public function testGetSearchableFields()
    {
        $result = $this->SavedSearches->getSearchableFields('Widgets');
        $this->assertEventFired('Search.Model.Search.searchabeFields', $this->EventManager());
    }

    public function testGetListingFields()
    {
        $result = $this->SavedSearches->getListingFields('Dashboards');
        $this->assertNotEmpty($result);
        $this->assertEquals($result, ['name']);
    }

    /**
     * @expectedException \RuntimeException
     * @dataProvider dataProviderGetBasicSearchCriteria
     */
    public function testGetBasicSearchCriteriaException($config)
    {
        $result = $this->SavedSearches->getBasicSearchCriteria(
            ['query' => $config['query']],
            $config['table'],
            ['id' => '00000000-0000-0000-0000-000000000001']
        );
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
            'limit' => '999'
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

        $this->assertArrayHasKey('saveSearchCriteriaId', $result);
        $this->assertArrayHasKey('saveSearchResultsId', $result);

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

    public function testGetBasicSearchCriteriaEmptyQuery()
    {
        $result = $this->SavedSearches->getBasicSearchCriteria(
            ['query' => []],
            'Dashboards',
            ['id' => '00000000-0000-0000-0000-000000000001']
        );

        $this->assertEmpty($result);
    }

    public function testGetSearchCriteria()
    {
        // $this->markTestSkipped();
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
                ],
                'modified' => [
                    'type' => 'datetime',
                    'operators' => [
                        'is' => [
                            'label' => 'is',
                            'operator' => 'IN'
                        ]
                    ]
                ],
                'created' => [
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

        $result = $this->SavedSearches->getSearchCriteria(['query' => ['foo']], 'Dashboards');
        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('name', $result);
    }

    public function testGetSearchCriteriaVirtualField()
    {
        // anonymous event listener that passes some dummy searchable fields
        $this->SavedSearches->eventManager()->on('Search.Model.Search.searchabeFields', function ($event, $table) {
            return [
                'foo' => [
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

        // set display field to a virtual one
        TableRegistry::get('Dashboards')->displayField('just_a_virtual_field');

        $result = $this->SavedSearches->getSearchCriteria(['query' => ['foo']], 'Dashboards');
        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('foo', $result);
    }
}
