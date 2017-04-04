<?php
namespace Search\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Search\Model\Table\ReportsTable;

/**
 * Search\Model\Table\ReportsTable Test Case
 */
class ReportsTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \Search\Model\Table\ReportsTable
     */
    public $Reports;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.search.reports',
        'plugin.search.users'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('Reports') ? [] : ['className' => 'Search\Model\Table\ReportsTable'];
        $this->Reports = TableRegistry::get('Reports', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Reports);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
