<?php
namespace Search\Test\TestCase\Model\Table;

use Cake\Core\Configure;
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

    public function testInitialize()
    {
        $result = $this->AppWidgets->find('list')->toArray();

        $this->assertContains('Another Test Widget', $result);
        $this->assertContains('Hello World', $result);
    }

    public function testValidationDefault()
    {
        $validator = new \Cake\Validation\Validator();
        $result = $this->AppWidgets->validationDefault($validator);

        $this->assertInstanceOf('\Cake\Validation\Validator', $result);
    }

    public function testBuildRules()
    {
        $rules = new \Cake\ORM\RulesChecker();
        $result = $this->AppWidgets->buildRules($rules);

        $this->assertInstanceOf('\Cake\ORM\RulesChecker', $result);
    }
}
