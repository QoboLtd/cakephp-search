<?php
namespace Search\Test\TestCase\Model\Table;

use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Search\Event\WidgetsListener;
use Search\Model\Table\WidgetsTable;

/**
 * Search\Model\Table\WidgetsTable Test Case
 */
class WidgetsTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \Search\Model\Table\WidgetsTable
     */
    public $Widgets;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.search.widgets',
        'plugin.search.saved_searches'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('Widgets') ? [] : ['className' => 'Search\Model\Table\WidgetsTable'];
        $this->Widgets = TableRegistry::get('Widgets', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Widgets);

        parent::tearDown();
    }

    public function testValidationDefault()
    {
        $validator = new \Cake\Validation\Validator();
        $result = $this->Widgets->validationDefault($validator);

        $this->assertInstanceOf('\Cake\Validation\Validator', $result);
    }

    public function testBuildRules()
    {
        $rules = new \Cake\ORM\RulesChecker();
        $result = $this->Widgets->buildRules($rules);

        $this->assertInstanceOf('\Cake\ORM\RulesChecker', $result);
    }

    /**
     * testing find
     * @return array $res containing array of saved_searches
     */
    public function testGetWidgets()
    {
        EventManager::instance()->on(new WidgetsListener());

        $res = $this->Widgets->getWidgets();
        $this->assertNotEmpty($res);
        $this->assertInternalType('array', $res);
    }
}
