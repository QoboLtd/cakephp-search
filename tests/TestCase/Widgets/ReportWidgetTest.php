<?php
namespace Search\Test\TestCase\Widgets;

use Cake\Event\EventList;
use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\Fixture\FixtureManager;
use Cake\TestSuite\TestCase;
use Search\Widgets\ReportWidget;

class ReportWidgetTest extends TestCase
{
    protected $widget;

    public $Widgets;

    public $fixtureManager;
    public $appView;

    public $autoFixtures = true;
    public $dropTables = false;

    public $fixtures = [
        'plugin.search.widgets',
    ];

    public function setUp()
    {
        parent::setUp();

        $this->appView = new \Cake\View\View();

        $this->appView->EventManager()->trackEvents(true);
        $this->appView->EventManager()->setEventList(new EventList());

        $this->fixtureManager = new FixtureManager();
        $this->fixtureManager->fixturize($this);

        $this->widget = new ReportWidget();

        $config = TableRegistry::exists('Widgets') ? [] : ['className' => 'Search\Model\Table\WidgetsTable'];
        $this->Widgets = TableRegistry::get('Widgets', $config);

        $this->fixtureManager->load($this);
    }

    public function tearDown()
    {
        parent::tearDown();
        unset($this->fixtureManager);
        unset($this->widget);
        unset($this->Widgets);
        unset($this->appView);
    }

    public function testGetRenderElement()
    {
        $result = $this->widget->getRenderElement();
        $this->assertEquals($result, 'Search.Widgets/graph');
    }

    public function testGetReportConfigWithoutRootView()
    {
        $result = $this->widget->getReport();
        $this->assertEquals($result, []);
    }

    public function testGetReportInstanceWithoutArgs()
    {
        $instance = $this->widget->getReportInstance();
        $this->assertEquals($instance, null);
    }

    /**
     * @dataProvider getInstancesList
     */
    public function testGetReportInstance($config, $expectedClass)
    {
        $instance = $this->widget->getReportInstance($config);
        $this->assertInstanceOf($expectedClass, $instance);

        $this->widget->_instance = $instance;

        $this->widget->setContainerId($config['config']);

        $this->assertEquals($config['config']['info']['renderAs'], $this->widget->getType());
        $this->assertEquals('graph_' . $config['config']['slug'], $this->widget->getContainerId());
        $this->assertEquals([], $this->widget->getOptions());
        $this->assertEquals([], $this->widget->getData());

        $this->widget->setConfig($config['config']);
        $this->assertEquals($this->widget->getConfig(), $config['config']);

        $dummyData = ['foo' => 'bar'];

        $this->widget->setData($dummyData);
        $this->assertEquals($dummyData, $this->widget->getData());
        $this->assertEquals([], $this->widget->getErrors());
    }

    public function getInstancesList()
    {
        $configs = [
           [['config' => ['slug' => 'barChartTest', 'info' => ['renderAs' => 'barChart']]], '\Search\Widgets\Reports\BarChartReportWidget'],
           [['config' => ['slug' => 'lineChartTest', 'info' => ['renderAs' => 'lineChart']]], '\Search\Widgets\Reports\LineChartReportWidget'],
           [['config' => ['slug' => 'donutChartTest', 'info' => ['renderAs' => 'donutChart']]], '\Search\Widgets\Reports\DonutChartReportWidget'],
           [['config' => ['slug' => 'knobChartTest', 'info' => ['renderAs' => 'knobChart']]], '\Search\Widgets\Reports\KnobChartReportWidget'],
        ];

        return $configs;
    }

    /**
     * @dataProvider getInstancesList
     */
    public function testGetReportConfigWithEvent($config, $expectedClass)
    {
        $entity = (object)[
            'widget_id' => '123123',
        ];

        $instance = $this->widget->getReportInstance($config);
        $this->assertInstanceOf($expectedClass, $instance);

        $this->widget->_instance = $instance;

        $result = $this->widget->getReport(['rootView' => $this->appView, 'entity' => $entity]);

        $events = $this->appView->EventManager()->getEventList();
        $events[0]->result = ['foo' => 'bar'];
        $this->assertEventFired('Search.Report.getReports', $this->appView->EventManager());
    }

    public function testValidatesWithoutRequiredFields()
    {
        $config = ['config' => ['slug' => 'barChartTest', 'info' => ['renderAs' => 'barChart']]];

        $instance = $this->widget->getReportInstance($config);

        $this->widget->_instance = $instance;

        $expectedReport = [
            'modelName' => 'Reports',
            'slug' => 'bar_assigned_by_year',
            'info' => [
                'id' => '00000000-0000-0000-0000-000000000002',
                'model' => 'Bar',
                'widget_type' => 'report',
                'name' => 'Report Bar',
                'query' => 'SELECT * FROM bar',
                'columns' => 'a,b',
                'renderAs' => 'lineChart',
                'y_axis' => 'a',
                'x_axis' => 'b'
            ]
        ];

        $this->widget->_instance->requiredFields = [];

        $validated = $this->widget->validate($expectedReport);
        $this->assertEquals($validated['status'], false);
    }

    public function testValidates()
    {
        $config = ['config' => ['slug' => 'barChartTest', 'info' => ['renderAs' => 'barChart']]];

        $instance = $this->widget->getReportInstance($config);

        $this->widget->_instance = $instance;

        $expectedReport = [
            'modelName' => 'Reports',
            'slug' => 'bar_assigned_by_year',
            'info' => [
                'id' => '00000000-0000-0000-0000-000000000002',
                'model' => 'Bar',
                'widget_type' => 'report',
                'name' => 'Report Bar',
                'query' => 'SELECT * FROM bar',
                'columns' => 'a,b',
                'renderAs' => 'lineChart',
                'y_axis' => 'a',
                'x_axis' => 'b'
            ]
        ];

        $validated = $this->widget->validate($expectedReport);
        $this->assertEquals($validated['status'], true);

        $expectedReport = [
            'modelName' => 'Reports',
            'slug' => 'bar_assigned_by_year',
            'info' => [
                'id' => '00000000-0000-0000-0000-000000000002',
                'model' => 'Bar',
                'widget_type' => 'report',
                'name' => 'Report Bar',
                'query' => 'SELECT * FROM bar',
                'columns' => 'a,b',
                'renderAs' => 'lineChart',
                'y_axis' => '',
            ]
        ];

        $validated = $this->widget->validate($expectedReport);

        $this->assertEquals($validated['status'], false);
        $this->assertContains('Required Field [y_axis] cannot be empty', array_keys(array_flip($validated['messages'])));
    }

    public function testGetChartData()
    {
        $config = ['config' => ['slug' => 'barChartTest', 'info' => ['renderAs' => 'barChart']]];

        $instance = $this->widget->getReportInstance($config);

        $this->widget->_instance = $instance;

        $expectedReport = [
            'modelName' => 'Reports',
            'slug' => 'bar_assigned_by_year',
            'info' => [
                'id' => '00000000-0000-0000-0000-000000000002',
                'model' => 'Bar',
                'widget_type' => 'report',
                'name' => 'Report Bar',
                'query' => 'SELECT * FROM bar',
                'columns' => 'a,b',
                'renderAs' => 'lineChart',
                'y_axis' => 'a',
                'x_axis' => 'b'
            ]
        ];

        $validated = $this->widget->validate($expectedReport);
        $this->widget->setConfig($expectedReport);
        $this->assertEquals($validated['status'], true);
        $chartData = $this->widget->getChartData([]);
        $this->assertEquals(['A', 'B'], $chartData['options']['labels']);
    }

    public function testGetReportsWithoutMock()
    {
        $result = $this->widget->getReports([]);
        $this->assertEquals($result, []);
    }

    public function testGetReportWithMock()
    {
        $dummyReports = [
            'Reports' => [
                'foo_graph_by_assigned_to' => [
                    'id' => '00000000-0000-0000-0000-000000000001',
                    'model' => 'Foo',
                    'widget_type' => 'report',
                    'name' => 'Report Foo',
                    'query' => '',
                    'columns' => '',
                    'renderAs' => 'barChart',
                    'y_axis' => 'a',
                    'x_axis' => 'b',
                ],
                'bar_assigned_by_year' => [
                    'id' => '00000000-0000-0000-0000-000000000002',
                    'model' => 'Bar',
                    'widget_type' => 'report',
                    'name' => 'Report Bar',
                    'query' => '',
                    'columns' => '',
                    'renderAs' => 'lineChart',
                    'y_axis' => '',
                    'x_axis' => '',
                ]
            ]
        ];

        $expectedReport = [
            'modelName' => 'Reports',
            'slug' => 'bar_assigned_by_year',
            'info' => [
                'id' => '00000000-0000-0000-0000-000000000002',
                'model' => 'Bar',
                'widget_type' => 'report',
                'name' => 'Report Bar',
                'query' => '',
                'columns' => '',
                'renderAs' => 'lineChart',
                'y_axis' => '',
                'x_axis' => ''
            ]
        ];

        $entity = (object)[
            'widget_id' => '00000000-0000-0000-0000-000000000002',
        ];

        $widget = $this->getMockBuilder('Search\Widgets\ReportWidget')->getMock();

        $widget->expects($this->any())
            ->method('getReports')
            ->with(['rootView' => $this->appView])
            ->will($this->returnValue($dummyReports));

        $reports = $widget->getReports(['rootView' => $this->appView]);

        $report = $this->widget->getReport(['entity' => $entity, 'reports' => $reports]);

        $this->assertEquals($report, $expectedReport);
    }

    public function testGetResults()
    {
        $result = $this->widget->getResults([]);
        $this->assertEquals($result, []);
    }

    public function testGetQueryData()
    {
        $result = $this->widget->getQueryData([]);
        $this->assertEquals($result, []);
    }

    /**
     * @dataProvider getQueriesList
     */
    public function testGetQueryDataWithData($query, $expectedCount)
    {
        $config = [
            'slug' => 'Test Foo',
            'info' => [
                'renderAs' => 'barChart',
                'query' => $query,
                'columns' => 'id,created',
                'y_axis' => 'id',
                'x_axis' => 'created',
            ]
        ];

        $result = $this->widget->getQueryData($config);
        $this->assertInternalType('array', $result);
        $this->assertEquals(count($result), $expectedCount);
    }

    public function getQueriesList()
    {
        return [
            ['SELECT id,created FROM widgets LIMIT 10', 5],
            ['SELECT id,created FROM widgets WHERE id = 1', 0],
        ];
    }
}
