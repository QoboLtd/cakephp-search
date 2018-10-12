<?php
namespace Search\Utility;

use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Search\Utility\BasicSearch;

/**
 * Search\Utility\BasicSearch Test Case
 */
class BasicSearchTest extends TestCase
{
    public $fixtures = [
        'plugin.search.dashboards',
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

        EventManager::instance()->on('Search.Model.Search.searchabeFields', function ($event, $table) {
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
            }

            return $result;
        });

        EventManager::instance()->on('Search.Model.Search.basicSearchFields', function ($event, $table) {
            $tableName = $table->getRegistryAlias();

            if ('Search.Dashboards' === $tableName) {
                return [
                    'Dashboards.name',
                    'Dashboards.image',
                    'Dashboards.role_id',
                    'Dashboards.modified',
                    'Dashboards.created'
                ];
            }

            if ('RolesCapabilities.Roles' === $tableName) {
                return [
                    'Roles.name'
                ];
            }

            return [];
        });

        $this->user = ['id' => '00000000-0000-0000-0000-000000000001'];
        $this->BasicSearch = new BasicSearch(TableRegistry::get('Search.Dashboards'), $this->user);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->BasicSearch);
        unset($this->user);

        parent::tearDown();
    }

    public function testGetCriteria()
    {
        $result = $this->BasicSearch->getCriteria('foo');

        $expected = ['Dashboards.name' => [
            ['type' => 'string', 'operator' => 'contains', 'value' => 'foo']
        ]];
        $this->assertEquals($result, $expected);
    }

    public function testGetCriteriaWithoutSearchableFields()
    {
        $basicSearch = new BasicSearch(TableRegistry::get('NonExistingTable'), $this->user);
        $result = $basicSearch->getCriteria('foo');

        $this->assertInternalType('array', $result);
        $this->assertEmpty($result);
    }

    public function testGetCriteriaWithRelatedField()
    {
        $value = 'Everyone';
        $result = $this->BasicSearch->getCriteria($value);

        $expected = [
            'Dashboards.name' => [
                ['type' => 'string', 'operator' => 'contains', 'value' => $value]
            ],
            'Dashboards.role_id' => [
                ['type' => 'related', 'operator' => 'is', 'value' => '00000000-0000-0000-0000-000000000002']
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetCriteriaWithDefaultFields()
    {
        EventManager::instance()->on('Search.Model.Search.basicSearchFields', function ($event, $table) {
            return [
                'Dashboards.non_existing_field'
            ];
        });

        $basicSearch = new BasicSearch(TableRegistry::get('Search.Dashboards'), ['id' => '00000000-0000-0000-0000-000000000001']);

        $value = 'Everyone';
        $expected = [
            'Dashboards.name' => [
                ['type' => 'string', 'operator' => 'contains', 'value' => $value]
            ],
            'Dashboards.role_id' => [
                ['type' => 'related', 'operator' => 'is', 'value' => '00000000-0000-0000-0000-000000000002']
            ]
        ];

        $this->assertEquals($expected, $basicSearch->getCriteria($value));
    }
}
