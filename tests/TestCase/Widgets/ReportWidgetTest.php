<?php
namespace Search\Test\TestCase\Widgets;

use Cake\Event\EventList;
use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Search\Widgets\Reports\BarChartReportWidget;
use Search\Widgets\Reports\DonutChartReportWidget;
use Search\Widgets\Reports\KnobChartReportWidget;
use Search\Widgets\Reports\LineChartReportWidget;
use Search\Widgets\ReportWidget;

class ReportWidgetTest extends TestCase
{

    public $fixtures = [
        'plugin.search.widgets',
        'plugin.search.articles'
    ];

    private $widget;

    public function setUp()
    {
        parent::setUp();

        $this->widget = new ReportWidget();

        EventManager::instance()->setEventList(new EventList());
        Configure::write('CsvMigrations.modules.path', TESTS . 'config' . DS . 'data' . DS);
    }

    public function tearDown()
    {
        unset($this->widget);

        parent::tearDown();
    }


    }

    }

    {
    }

    public function testGetRenderElement(): void
    {
        $result = $this->widget->getRenderElement();
        $this->assertEquals($result, 'Search.Widgets/graph');
    }

    public function testGetReportConfigWithoutRootView(): void
    {
        $result = $this->widget->getReport();
        $this->assertEquals($result, []);
    }

    public function testGetReportInstanceWithoutArgs(): void
    {
        $instance = $this->widget->getReportInstance();
        $this->assertEquals($instance, null);
    }

    /**
     * @dataProvider getInstancesList
     * @param mixed[] $config
     * @param string $expectedClass
     */
    public function testGetReportInstance(array $config, string $expectedClass): void
    {
        $instance = $this->widget->getReportInstance($config);
        $this->assertInstanceOf($expectedClass, $instance);

        $instance->setContainerId($config['config']);

        $this->assertEquals('graph_' . $config['config']['slug'], $instance->getContainerId());
        $this->assertEquals([], $instance->getOptions());
        $this->assertEquals([], $instance->getData());

        $instance->setConfig($config['config']);
        $this->assertEquals($instance->getConfig(), $config['config']);

        $dummyData = ['foo' => 'bar'];

        $instance->setData($dummyData);
        $this->assertEquals($dummyData, $instance->getData());
        $this->assertEquals([], $instance->getErrors());
    }

    /**
     * @return mixed[]
     */
    public function getInstancesList(): array
    {
        $configs = [
           [['config' => ['slug' => 'barChartTest', 'info' => ['renderAs' => 'barChart']]], BarChartReportWidget::class],
           [['config' => ['slug' => 'lineChartTest', 'info' => ['renderAs' => 'lineChart']]], LineChartReportWidget::class],
           [['config' => ['slug' => 'donutChartTest', 'info' => ['renderAs' => 'donutChart']]], DonutChartReportWidget::class],
           [['config' => ['slug' => 'knobChartTest', 'info' => ['renderAs' => 'knobChart']]], KnobChartReportWidget::class],
        ];

        return $configs;
    }

    /**
     * @dataProvider getInstancesList
     * @param mixed[] $config
     * @param string $expectedClass
     */
    public function testGetReportConfigWithEvent(array $config, string $expectedClass): void
    {
        $entity = (object)[
            'widget_id' => '123123',
        ];

        $instance = $this->widget->getReportInstance($config);
        $this->assertInstanceOf($expectedClass, $instance);

        $this->widget->getReport(['entity' => $entity]);

        $this->assertEventFired('Search.Report.getReports');
    }

    public function testValidatesWithoutRequiredFields(): void
    {
        $config = ['config' => ['slug' => 'barChartTest', 'info' => ['renderAs' => 'barChart']]];

        $instance = $this->widget->getReportInstance($config);

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

        $instance->requiredFields = [];

        $validated = $instance->validate($expectedReport);
        $this->assertEquals($validated['status'], false);
    }

    public function testValidates(): void
    {
        $config = ['config' => ['slug' => 'barChartTest', 'info' => ['renderAs' => 'barChart']]];

        $instance = $this->widget->getReportInstance($config);

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

        $validated = $instance->validate($expectedReport);
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

        $validated = $instance->validate($expectedReport);

        $this->assertEquals($validated['status'], false);
        $this->assertContains('Required Field [y_axis] cannot be empty', array_keys(array_flip($validated['messages'])));
    }

    public function testGetChartData(): void
    {
        $config = ['config' => ['slug' => 'barChartTest', 'info' => ['renderAs' => 'barChart']]];

        $instance = $this->widget->getReportInstance($config);

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

        $validated = $instance->validate($expectedReport);
        $instance->setConfig($expectedReport);
        $this->assertEquals($validated['status'], true);

        $chartData = $instance->getChartData([]);
        $this->assertEquals('A', $chartData['options']['dataChart']['data']['datasets'][0]['label']);
    }

    public function testGetReportsWithoutMock(): void
    {
        $result = $this->widget->getReports([]);
        $this->assertEquals($result, []);
    }

    public function testGetReportWithMock(): void
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

        /** @var \Search\Widgets\ReportWidget&\PHPUnit\Framework\MockObject\MockObject */
        $widget = $this->getMockBuilder('Search\Widgets\ReportWidget')->getMock();

        $widget->expects($this->any())
            ->method('getReports')
            ->will($this->returnValue($dummyReports));

        $reports = $widget->getReports();

        $report = $this->widget->getReport(['entity' => $entity, 'reports' => $reports]);

        $this->assertEquals($report, $expectedReport);
    }

    public function testGetResults(): void
    {
        $result = $this->widget->getResults([]);
        $this->assertEquals($result, []);
    }

    public function testGetQueryData(): void
    {
        $result = $this->widget->getQueryData([]);
        $this->assertEquals($result, []);
    }

    public function testGetQueryDataFinder(): void
    {
        $config = [
            'modelName' => 'Articles',
            'slug' => 'Articles',
            'info' => [
                'id' => '00000000-0000-0000-0001-000000000001',
                'model' => 'Articles',
                'widget_type' => 'report',
                'name' => 'Articles',
                'columns' => 'title,content',
                'renderAs' => 'barChart',
                'finder' => [
                    'name' => 'title',
                    'options' => [
                        'title' => 'First article title'
                    ]
                ],
                'y_axis' => 'total_amount',
                'x_axis' => 'quarter'
            ]
        ];

        $query = $this->widget->getQueryData($config);
        $results = [
                        0 => [
                            'title' => 'First article title',
                            'content' => 'First article content.'
                        ]
                    ];
        $this->assertEquals($results, $query);
    }

    /**
     * @dataProvider getQueriesList
     */
    public function testGetQueryDataWithData(string $query, int $expectedCount): void
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

    /**
     * @return mixed[]
     */
    public function getQueriesList(): array
    {
        return [
            ['SELECT id,created FROM widgets LIMIT 10', 6],
            ['SELECT id,created FROM widgets WHERE id = 1', 0],
        ];
    }
}
