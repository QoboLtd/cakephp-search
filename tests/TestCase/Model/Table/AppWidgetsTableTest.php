<?php
namespace Search\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Search\Model\Table\AppWidgetsTable;

/**
 * Search\Model\Table\AppWidgetsTable Test Case
 */
class AppWidgetsTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \Search\Model\Table\AppWidgetsTable
     */
    public $AppWidgets;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.search.app_widgets'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('AppWidgets') ? [] : ['className' => 'Search\Model\Table\AppWidgetsTable'];
        $this->AppWidgets = TableRegistry::get('AppWidgets', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->AppWidgets);

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
