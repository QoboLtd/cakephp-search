<?php
namespace Search\Test\TestCase\Widgets;

use Cake\Core\Configure;
use Cake\Event\EventList;
use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;
use Search\Widgets\Reports\BarChartReportWidget;
use Search\Widgets\Reports\DonutChartReportWidget;
use Search\Widgets\Reports\KnobChartReportWidget;
use Search\Widgets\Reports\LineChartReportWidget;
use Search\Widgets\ReportWidget;

class ReportWidgetTest extends TestCase
{
    private const DEFAULT_OPTIONS = ['config' => ['info' => ['renderAs' => 'barChart']]];

    public $fixtures = [
        'plugin.Search.Widgets',
        'plugin.Search.Articles',
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

    public function testGetRenderElement(): void
    {
        $this->assertSame('Search.Widgets/graph', $this->widget->getRenderElement());
    }

    public function testGetConfig(): void
    {
        $this->widget->getResults(self::DEFAULT_OPTIONS);
        $this->widget->setConfig(self::DEFAULT_OPTIONS['config']);
        $this->assertSame(self::DEFAULT_OPTIONS['config'], $this->widget->getConfig());
    }

    public function testGetChartData(): void
    {
        $options = [
            'config' => [
                'info' => [
                    'model' => 'Bar',
                    'widget_type' => 'report',
                    'name' => 'Report Bar',
                    'query' => 'SELECT * FROM bar',
                    'columns' => 'a,b',
                    'renderAs' => 'lineChart',
                    'y_axis' => 'a',
                    'x_axis' => 'b',
                ],
            ],
        ];

        $this->widget->getResults($options);
        $this->widget->setConfig($options['config']);

        $expected = [
            'chart' => 'line',
            'id' => '',
            'options' => [
                'resize' => true,
                'hideHover' => true,
                'dataChart' => [
                    'type' => 'line',
                    'data' => [
                        'labels' => [],
                        'datasets' => [['label' => 'A', 'data' => [], 'borderColor' => '#6639a6', 'fill' => false]],
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $this->widget->getChartData());
    }

    public function testValidate(): void
    {
        $expected = [
            'status' => false,
            'messages' => [
                'Required field [query] must be set',
                'Required Field [query] cannot be empty',
                'Required field [columns] must be set',
                'Required Field [columns] cannot be empty',
                'Required field [x_axis] must be set',
                'Required Field [x_axis] cannot be empty',
                'Required field [y_axis] must be set',
                'Required Field [y_axis] cannot be empty',
            ],
        ];

        $this->widget->getResults(self::DEFAULT_OPTIONS);

        $this->assertSame($expected, $this->widget->validate());
    }

    public function testGetOptions(): void
    {
        $this->widget->getResults(self::DEFAULT_OPTIONS);

        $this->assertSame([], $this->widget->getOptions());
    }

    public function testSetOptions(): void
    {
        $this->widget->getResults(self::DEFAULT_OPTIONS);
        $this->widget->setOptions(['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], $this->widget->getOptions());
    }

    public function testGetType(): void
    {
        $this->widget->getResults(self::DEFAULT_OPTIONS);

        $this->assertSame('bar', $this->widget->getType());
    }

    public function testGetData(): void
    {
        $this->widget->getResults(self::DEFAULT_OPTIONS);
        $this->assertSame([], $this->widget->getData());
    }

    public function testSetData(): void
    {
        $this->widget->getResults(self::DEFAULT_OPTIONS);
        $this->widget->setData(['foo']);

        $this->assertSame(['foo'], $this->widget->getData());
    }

    public function testGetContainerId(): void
    {
        $this->widget->getResults(self::DEFAULT_OPTIONS);
        $this->assertSame('', $this->widget->getContainerId());
    }

    public function testSetContainerId(): void
    {
        $this->widget->getResults(self::DEFAULT_OPTIONS);
        $this->widget->setContainerId(['slug' => 'foo-slug']);

        $this->assertSame('graph_foo-slug', $this->widget->getContainerId());
    }

    public function testGetErrors(): void
    {
        $this->widget->getResults(self::DEFAULT_OPTIONS);
        $this->assertSame([], $this->widget->getErrors());
    }

    public function testGetReportWithoutOptions(): void
    {
        $this->assertSame([], $this->widget->getReport());
    }

    public function testGetReportInstanceWithoutArgs(): void
    {
        $instance = $this->widget->getReportInstance();
        $this->assertSame(null, $instance);
    }

    public function testGetReportInstanceWithoutRenderAs(): void
    {
        $instance = $this->widget->getReportInstance(['config' => ['info' => ['renderAs' => null]]]);
        $this->assertSame(null, $instance);
    }

    public function testGetReportInstanceWithInvalidRenderAs(): void
    {
        $instance = $this->widget->getReportInstance(['config' => ['info' => ['renderAs' => 'invalid']]]);
        $this->assertSame(null, $instance);
    }

    public function testGetReportInstanceWithClassThatDoesNotImplementRequiredInterface(): void
    {
        $instance = $this->widget->getReportInstance(['config' => ['info' => ['renderAs' => 'fake_invalid']]]);
        $this->assertSame(null, $instance);
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

        $this->assertSame('graph_' . $config['config']['slug'], $instance->getContainerId());
        $this->assertSame([], $instance->getOptions());
        $this->assertSame([], $instance->getData());

        $instance->setConfig($config['config']);
        $this->assertSame($config['config'], $instance->getConfig());

        $dummyData = ['foo' => 'bar'];

        $instance->setData($dummyData);
        $this->assertSame($dummyData, $instance->getData());
        $this->assertSame([], $instance->getErrors());
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
                'x_axis' => 'b',
            ],
        ];

        $instance->requiredFields = [];

        $validated = $instance->validate($expectedReport);
        $this->assertSame(false, $validated['status']);
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
                'x_axis' => 'b',
            ],
        ];

        $validated = $instance->validate($expectedReport);
        $this->assertSame(true, $validated['status']);

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
            ],
        ];

        $validated = $instance->validate($expectedReport);

        $this->assertSame(false, $validated['status']);
        $this->assertContains('Required Field [y_axis] cannot be empty', array_keys(array_flip($validated['messages'])));
    }

    public function testGetInstanceChartData(): void
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
                'x_axis' => 'b',
            ],
        ];

        $validated = $instance->validate($expectedReport);
        $instance->setConfig($expectedReport);
        $this->assertSame(true, $validated['status']);

        $chartData = $instance->getChartData([]);
        $this->assertSame('A', $chartData['options']['dataChart']['data']['datasets'][0]['label']);
    }

    public function testGetReportsWithoutMock(): void
    {
        $result = $this->widget->getReports([]);
        $this->assertSame([], $result);
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
                ],
            ],
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
                'x_axis' => '',
            ],
        ];

        $entity = (object)[
            'widget_id' => '00000000-0000-0000-0000-000000000002',
        ];

        /** @var \Search\Widgets\ReportWidget&\PHPUnit\Framework\MockObject\MockObject */
        $widget = $this->getMockBuilder(ReportWidget::class)->getMock();

        $widget->expects($this->any())
            ->method('getReports')
            ->will($this->returnValue($dummyReports));

        $reports = $widget->getReports();

        $report = $this->widget->getReport(['entity' => $entity, 'reports' => $reports]);

        $this->assertSame($expectedReport, $report);
    }

    public function testGetResults(): void
    {
        $widgetId = '00000000-0000-0000-0000-000000000002';
        $entity = (object)['widget_id' => $widgetId];
        $reports = [
            'Articles' => [
                'report_slug' => [
                    'id' => $widgetId,
                    'model' => 'Foo',
                    'widget_type' => 'report',
                    'name' => 'Report Foo',
                    'query' => 'SELECT * FROM articles',
                    'columns' => 'title,status',
                    'renderAs' => 'barChart',
                    'y_axis' => 'a',
                    'x_axis' => 'b',
                ],
            ],
        ];

        $expected = [
            ['title' => 'First article title', 'status' => 'published'],
            ['title' => 'Second article title', 'status' => 'draft'],
            ['title' => 'Third article title', 'status' => 'trashed'],
        ];

        $this->assertSame($expected, $this->widget->getResults(['entity' => $entity, 'reports' => $reports]));
    }

    public function testGetResultsWithoutOptions(): void
    {
        $this->assertSame([], $this->widget->getResults());
    }

    public function testGetResultsWithInvalidReportOptions(): void
    {
        $widgetId = '00000000-0000-0000-0000-000000000002';
        $entity = (object)['widget_id' => $widgetId];
        $reports = [
            'Articles' => [
                'report_slug' => [
                    'id' => $widgetId,
                    'model' => 'Foo',
                    'widget_type' => 'report',
                    'name' => 'Report Foo',
                    // 'query' => 'SELECT * FROM articles', // Query is required
                    'columns' => 'title,status',
                    'renderAs' => 'barChart',
                    'y_axis' => 'a',
                    'x_axis' => 'b',
                ],
            ],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Report validation failed');

        $this->widget->getResults(['entity' => $entity, 'reports' => $reports]);
    }

    public function testGetQueryData(): void
    {
        $result = $this->widget->getQueryData([]);
        $this->assertSame([], $result);
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
                        'title' => 'First article title',
                    ],
                ],
                'y_axis' => 'total_amount',
                'x_axis' => 'quarter',
            ],
        ];

        $query = $this->widget->getQueryData($config);
        $expected = [['title' => 'First article title', 'content' => 'First article content.']];
        $this->assertSame($expected, $query);
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
            ],
        ];

        $result = $this->widget->getQueryData($config);
        $this->assertInternalType('array', $result);
        $this->assertCount($expectedCount, $result);
    }

    /**
     * @return mixed[]
     */
    public function getQueriesList(): array
    {
        return [
            ['SELECT id,created FROM qobo_search_widgets LIMIT 10', 6],
            ['SELECT id,created FROM qobo_search_widgets WHERE id = 1', 0],
        ];
    }
}

namespace Search\Widgets\Reports;

class FakeInvalidReportWidget
{
}
