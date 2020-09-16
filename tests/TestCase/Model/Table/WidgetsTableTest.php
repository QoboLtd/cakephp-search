<?php
namespace Qobo\Search\Test\TestCase\Model\Table;

use Cake\Event\EventList;
use Cake\Event\EventManager;
use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;
use Qobo\Search\Event\EventName;
use Qobo\Search\Event\Model\WidgetsListener;
use Webmozart\Assert\Assert;

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
        'plugin.Search.Widgets',
        'plugin.Search.AppWidgets',
        'plugin.Search.Dashboards',
        'plugin.Search.SavedSearches',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->Widgets = TableRegistry::getTableLocator()->get('Qobo/Search.Widgets');
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

        $result = $this->Widgets->getWidgets();
        $this->assertInternalType('array', $result);
        $this->assertCount(7, $result);
        $this->assertEventFired(EventName::MODEL_DASHBOARDS_GET_WIDGETS, $this->Widgets->getEventManager());
    }

    public function testGetWidgetsWithoutListener(): void
    {
        $this->assertSame([], $this->Widgets->getWidgets());
        $this->assertEventFired(EventName::MODEL_DASHBOARDS_GET_WIDGETS, $this->Widgets->getEventManager());
    }

    public function testGetWidgetsWithoutWidgets(): void
    {
        EventManager::instance()->on(new WidgetsListener());

        TableRegistry::getTableLocator()->get('Qobo/Search.AppWidgets')->deleteAll([]);
        TableRegistry::getTableLocator()->get('Qobo/Search.SavedSearches')->deleteAll([]);

        $this->assertSame([], $this->Widgets->getWidgets());
        $this->assertEventFired(EventName::MODEL_DASHBOARDS_GET_WIDGETS, $this->Widgets->getEventManager());
    }

    public function testGetWidgetsWithWidgetWhichHasNotData(): void
    {
        EventManager::instance()->on(new WidgetsListener());

        TableRegistry::getTableLocator()->get('Qobo/Search.AppWidgets')->deleteAll([]);
        TableRegistry::getTableLocator()->get('Qobo/Search.SavedSearches')->deleteAll([]);

        EventManager::instance()->on('Search.Report.getReports', function ($event) {
            return ['Foo' => ['bar_assigned_by_year' => []]];
        });

        $this->assertSame([], $this->Widgets->getWidgets());
    }

    public function testGetWidgetsWithReports(): void
    {
        EventManager::instance()->on(new WidgetsListener());

        $expected = [
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
                'x_axis' => '',
            ],
        ];

        // anonymous event listener that passes some dummy reports
        EventManager::instance()->on('Search.Report.getReports', function ($event) use ($expected) {
            return ['Foo' => ['bar_assigned_by_year' => $expected['data']]];
        });

        $result = $this->Widgets->getWidgets();

        $this->assertInternalType('array', $result);
        $this->assertContains($expected, $result);
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
                'path' => 'Template/Element/Plugin/Search/Widgets/hello_world.ctp',
            ],
        ];

        $this->assertContains($data, $result);
        $this->assertIsReadable(APP . $data['data']['path']);
    }

    public function testGetWidgetOptions(): void
    {
        $widget = $this->Widgets->get('00000000-0000-0000-0000-000000000001');
        $expected = array_merge(
            ['title' => 'Report', 'icon' => 'area-chart', 'color' => 'primary'],
            json_decode($widget->get('widget_options'), true)
        );

        $result = $this->Widgets->getWidgetOptions($widget);

        $this->assertSame($expected, $result);
    }

    public function testGetWidgetDefaultOptions(): void
    {
        $expected = [
            'title' => 'Report',
            'icon' => 'area-chart',
            'color' => 'primary',
            'i' => '0',
            'x' => 0,
            'y' => 0,
            'h' => 3,
            'w' => 6,
            'id' => null,
            'type' => 'report',
        ];

        $widget = $this->Widgets->newEntity(['widget_type' => $expected['type']]);

        $result = $this->Widgets->getWidgetOptions($widget);

        $this->assertSame($expected, $result);
    }

    public function testSaveDashboardWidgets(): void
    {
        $widgets = [['id' => 'foobaz', 'type' => 'foobar']];

        $dashboard = TableRegistry::getTableLocator()
            ->get('Qobo/Search.Dashboards')
            ->find()
            ->firstOrFail();
        Assert::isInstanceOf($dashboard, \Cake\Datasource\EntityInterface::class);

        $this->assertTrue($this->Widgets->saveDashboardWidgets($dashboard->get('id'), $widgets));
    }

    public function testSaveDashboardWidgetsWithoutWidgets(): void
    {
        $this->assertFalse($this->Widgets->saveDashboardWidgets('some-id', []));
    }
}
