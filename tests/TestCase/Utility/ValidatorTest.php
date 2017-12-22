<?php
namespace Search\Utility;

use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Search\Utility\Options;
use Search\Utility\Validator;

/**
 * Search\Utility\Validator Test Case
 */
class ValidatorTest extends TestCase
{
    public $fixtures = [
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

        $config = TableRegistry::exists('SavedSearches') ? [] : ['className' => 'Search\Model\Table\SavedSearchesTable'];
        $this->SavedSearches = TableRegistry::get('SavedSearches', $config);

        EventManager::instance()->on('Search.Model.Search.searchabeFields', function ($event, $table) {
            $result = [
                'Dashboards.name' => ['type' => 'string', 'operators' => [
                    'contains' => ['label' => 'contains', 'operator' => 'LIKE', 'pattern' => '%{{value}}%']
                ]],
                'Dashboards.image' => ['type' => 'blob', 'operators' => [
                    'contains' => ['label' => 'contains', 'operator' => 'LIKE', 'pattern' => '%{{value}}%'],
                ]],
                'Dashboards.role_id' => ['type' => 'related', 'source' => 'Roles', 'operators' => [
                    'contains' => ['label' => 'contains', 'operator' => 'LIKE', 'pattern' => '%{{value}}%'],
                    'is_not' => ['label' => 'is not', 'operator' => 'NOT IN'],
                ]],
                'Dashboards.modified' => ['type' => 'datetime', 'operators' => [
                    'is' => ['label' => 'is', 'operator' => 'IN'],
                    'greater' => ['label' => 'from', 'operator' => '>']
                ]],
                'Dashboards.created' => ['type' => 'datetime', 'operators' => [
                    'is' => ['label' => 'is', 'operator' => 'IN'],
                    'greater' => ['label' => 'from', 'operator' => '>']
                ]]
            ];

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

        $result = Validator::validateData(TableRegistry::get($model), $data, $user);
        $this->assertEquals($data, $result);
    }

    public function testValidateDataWrong()
    {
        $table = TableRegistry::get('Dashboards');

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

        $result = Validator::validateData($table, $data, $user);

        $this->assertEmpty($result['criteria']);
        $this->assertEmpty($result['display_columns']);

        $expected = $table->aliasField($table->getDisplayField());
        $this->assertEquals($expected, $result['sort_by_field']);

        $this->assertEquals(Options::DEFAULT_SORT_BY_ORDER, $result['sort_by_order']);

        $this->assertEquals(Options::DEFAULT_AGGREGATOR, $result['aggregator']);
    }

    public function testValidatePrimaryKeyAsSortField()
    {
        $table = TableRegistry::get('Dashboards');

        $data = [
            'criteria' => ['foo'],
            'display_columns' => ['foo'],
            'sort_by_field' => 'foo',
        ];

        $user = ['id' => '00000000-0000-0000-0000-000000000001'];

        $table->setDisplayField('foo');

        $result = Validator::validateData($table, $data, $user);

        $expected = $table->aliasField($table->aliasField($table->getPrimaryKey()));
        $this->assertEquals($expected, $result['sort_by_field']);
    }

    public function testValidateDisplayColumnAsSortField()
    {
        $table = TableRegistry::get('Dashboards');

        $expected = $table->aliasField('name');

        $data = [
            'criteria' => ['foo'],
            'display_columns' => ['foo', 'bar', $expected],
            'sort_by_field' => 'foo',
        ];

        $user = ['id' => '00000000-0000-0000-0000-000000000001'];

        $table->setDisplayField('foo');

        $result = Validator::validateData($table, $data, $user);

        $this->assertEquals($expected, $result['sort_by_field']);
    }
}
