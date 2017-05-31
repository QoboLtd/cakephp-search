<?php
namespace Search\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Search\Model\Table\DashboardsTable;

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
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('Dashboards') ? [] : ['className' => 'Search\Model\Table\DashboardsTable'];
        $this->Dashboards = TableRegistry::get('Dashboards', $config);
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

    public function testGetUserDashboards()
    {
        $user = ['id' => '00000000-0000-0000-0000-000000000001'];

        $query = $this->Dashboards->getUserDashboards($user);
        $this->assertInstanceOf('\Cake\ORM\Query', $query);
    }

    public function testGetUserDashboardsSuperuser()
    {
        $user = ['is_superuser' => true];

        $query = $this->Dashboards->getUserDashboards($user);
        $this->assertInstanceOf('\Cake\ORM\Query', $query);
    }
}
