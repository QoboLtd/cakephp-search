<?php
namespace Search\Test\TestCase\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;

/**
 * Search\Model\Table\DashboardsTable Test Case
 */
class DashboardsTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \Search\Model\Table\DashboardsTable
     */
    public $Dashboards;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.search.dashboards',
        'plugin.CakeDC/Users.users',
        'plugin.groups.groups',
        'plugin.groups.groups_users',
        'plugin.roles_capabilities.groups_roles',
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

        $config = TableRegistry::exists('Search.Dashboards') ? [] : ['className' => 'Search\Model\Table\DashboardsTable'];
        /**
         * @var \Search\Model\Table\DashboardsTable $table
         */
        $table = TableRegistry::get('Search.Dashboards', $config);
        $this->Dashboards = $table;
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Dashboards);

        parent::tearDown();
    }

    public function testValidationDefault(): void
    {
        $validator = new \Cake\Validation\Validator();
        $result = $this->Dashboards->validationDefault($validator);

        $this->assertInstanceOf(Validator::class, $result);
    }

    public function testBuildRules(): void
    {
        $rules = new \Cake\ORM\RulesChecker();
        $result = $this->Dashboards->buildRules($rules);

        $this->assertInstanceOf(RulesChecker::class, $result);
    }

    public function testGetUserDashboards(): void
    {
        $user = ['id' => '00000000-0000-0000-0000-000000000001'];

        $query = $this->Dashboards->getUserDashboards($user);
        $this->assertInstanceOf(Query::class, $query);
        $this->assertEquals(1, $query->count());
    }

    public function testGetUserDashboardsWithoutRolesAndGroups(): void
    {
        $user = ['id' => '00000000-0000-0000-0000-000000000003'];

        $query = $this->Dashboards->getUserDashboards($user);
        $this->assertInstanceOf(Query::class, $query);
        $this->assertEquals(2, $query->count());
    }

    public function testGetUserDashboardsSuperuser(): void
    {
        $user = ['is_superuser' => true];

        $query = $this->Dashboards->getUserDashboards($user);
        $this->assertInstanceOf(Query::class, $query);
        $this->assertEquals(4, $query->count());
    }
}
