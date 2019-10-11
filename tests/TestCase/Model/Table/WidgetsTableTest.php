<?php
namespace Search\Test\TestCase\Model\Table;

use Cake\Event\EventList;
use Cake\Event\EventManager;
use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;
use Search\Event\Model\WidgetsListener;

/**
 * Search\Model\Table\WidgetsTable Test Case
 */
class WidgetsTableTest extends TestCase
{
    private $Widgets;

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

        $this->Widgets = TableRegistry::getTableLocator()->get('Search.Widgets');
        $this->Widgets->getEventManager()->setEventList(new EventList());
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

    public function testValidationDefault(): void
    {
        $validator = new Validator();
        $result = $this->Widgets->validationDefault($validator);

        $this->assertInstanceOf(Validator::class, $result);
    }

    public function testBuildRules(): void
    {
        $rules = new RulesChecker();
        $result = $this->Widgets->buildRules($rules);

        $this->assertInstanceOf(RulesChecker::class, $result);
    }

    /**
     * testing find
     */
    public function testGetWidgets(): void
    {
        EventManager::instance()->on(new WidgetsListener());

        $res = $this->Widgets->getWidgets();
        $this->assertNotEmpty($res);
        $this->assertInternalType('array', $res);
    }

    public function testGetWidgetsWithReports(): void
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
            'title' => 'Report',
            'icon' => 'area-chart',
            'color' => 'primary',
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

    public function testGetWidgetsWithAppWidgets(): void
    {
        EventManager::instance()->on(new WidgetsListener());
        $result = $this->Widgets->getWidgets();

        $this->assertNotEmpty($result);
        $this->assertInternalType('array', $result);

        $data = [
            'type' => 'app',
            'title' => 'App',
            'icon' => 'gears',
            'color' => 'danger',
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
