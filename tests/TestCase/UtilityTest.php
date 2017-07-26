<?php
namespace Search\Test\TestCase;

use Cake\Event\EventList;
use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Search\Utility;

/**
 * Search\Utility Test Case
 */
class UtilityTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.search.articles',
        'plugin.search.authors',
        'plugin.search.dashboards'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->Utility = Utility::instance();

        $config = TableRegistry::exists('SavedSearches') ? [] : ['className' => 'Search\Model\Table\SavedSearchesTable'];
        $this->SavedSearches = TableRegistry::get('SavedSearches', $config);

        EventManager::instance()->on(
            'Search.Model.Search.searchabeFields',
            function ($event, $table) {
                return [
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
                    ],
                    'Authors.name' => [
                        'type' => 'string',
                        'operators' => [
                            'contains' => ['label' => 'contains', 'operator' => 'LIKE', 'pattern' => '%{{value}}%']
                        ]
                    ]
                ];
            }
        );
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Utility);
        unset($this->SavedSearches);

        parent::tearDown();
    }

    public function testGetAssociationLabels()
    {
        $table = TableRegistry::get('Articles');
        $result = $this->Utility->getAssociationLabels($table);

        $this->assertArrayHasKey('Authors', $result);
        $this->assertContains('Author Id', $result);
    }

    public function testGetSearchableFieldsEventFired()
    {
        $user = ['id' => '00000000-0000-0000-0000-000000000001'];
        EventManager::instance()->setEventList(new EventList());

        $result = $this->Utility->getSearchableFields(TableRegistry::get('Widgets'), $user);

        $this->assertEventFired('Search.Model.Search.searchabeFields', EventManager::instance());
    }

    public function testToDatatables()
    {
        $table = TableRegistry::get('Dashboards');
        $query = $table->find();

        $fields = ['Dashboards.name'];
        $result = $this->Utility->toDatatables($query->all(), $fields, $table);

        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);

        foreach ($result as $row) {
            $this->assertEquals(count($fields) + 1, count($row));
        }
    }

    public function testToDatatablesWithAssociated()
    {
        $table = TableRegistry::get('Articles');

        $user = ['id' => '00000000-0000-0000-0000-000000000001'];

        $data = [
            'aggregator' => 'AND',
            'criteria' => [
                'Authors.name' => [
                    10 => ['type' => 'string', 'operator' => 'contains', 'value' => 'Mark']
                ]
            ],
            'display_columns' => [
                'Articles.title',
                'Authors.name',
                'Articles.created',
                'Articles.modified'
            ],
            'sort_by_field' => 'Authors.name',
            'sort_by_order' => 'desc'
        ];

        $query = $this->SavedSearches->search($table, $user, $data);

        $result = $this->Utility->toDatatables($query->all(), $data['display_columns'], $table);

        foreach ($result as $row) {
            $this->assertEquals(count($data['display_columns']) + 1, count($row));
            foreach ($row as $value) {
                $this->assertNotNull($value);
            }
        }
    }

    public function testToCsv()
    {
        $table = TableRegistry::get('Dashboards');
        $query = $table->find();

        $fields = ['Dashboards.name', 'Dashboards.role_id', 'Dashboards.created'];
        $result = $this->Utility->toCsv($query->all(), $fields, $table);

        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);

        foreach ($result as $row) {
            $this->assertEquals(count($fields), count($row));
        }
    }

    public function testToCsvWithAssociated()
    {
        $table = TableRegistry::get('Articles');

        $user = ['id' => '00000000-0000-0000-0000-000000000001'];

        $data = [
            'aggregator' => 'AND',
            'criteria' => [
                'Authors.name' => [
                    10 => ['type' => 'string', 'operator' => 'contains', 'value' => 'Mark']
                ]
            ],
            'display_columns' => [
                'Articles.title',
                'Authors.name',
                'Articles.created',
                'Articles.modified'
            ],
            'sort_by_field' => 'Authors.name',
            'sort_by_order' => 'desc'
        ];

        $query = $this->SavedSearches->search($table, $user, $data);

        $result = $this->Utility->toCsv($query->all(), $data['display_columns'], $table);

        foreach ($result as $row) {
            $this->assertEquals(count($data['display_columns']), count($row));
            foreach ($row as $value) {
                $this->assertNotNull($value);
            }
        }
    }
}
