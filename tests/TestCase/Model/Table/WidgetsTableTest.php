<?php
namespace Search\Test\TestCase\Model\Table;

use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Search\Event\Model\WidgetsListener;
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
        'plugin.search.app_widgets',
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

    public function testGetWidgetsWithReports()
    {
        EventManager::instance()->on(new WidgetsListener());
        // anonymous event listener that passes some dummy reports
        EventManager::instance()->on('Search.Report.getReports', function ($event) {
            return [
                'Foo' => [
                    'bar_assigned_by_year' => [
                        'id' => '00000000-0000-0000-0000-000000000002',
                        'model' => 'Bar',
                        'widget_type' => 'report',
                        'name' => 'Report Bar',
                        'query' => '',
                        'colors' => '',
                        'columns' => '',
                        'renderAs' => 'barChart',
                        'y_axis' => '',
                        'x_axis' => ''
                    ]
                ]
            ];
        });

        $result = $this->Widgets->getWidgets();

        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);

        $data = [
            'type' => 'report',
            'data' => [
                'id' => '00000000-0000-0000-0000-000000000002',
                'model' => 'Bar',
                'widget_type' => 'report',
                'name' => 'Report Bar',
                'query' => '',
                'colors' => '',
                'columns' => '',
                'renderAs' => 'barChart',
                'y_axis' => '',
                'x_axis' => ''
            ]
        ];

        $this->assertContains($data, $result);
    }

    public function testGetWidgetsWithAppWidgets()
    {
        EventManager::instance()->on(new WidgetsListener());
        $result = $this->Widgets->getWidgets();

        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);

        $data = [
            'type' => 'app',
            'data' => [
                'id' => '00000000-0000-0000-0000-000000000001',
                'model' => 'AppWidgets',
                'name' => 'Hello World',
                'path' => 'src/Template/Element/Plugin/Search/Widgets/hello_world.ctp'
            ]
        ];

        $this->assertContains($data, $result);
    }
}
