<?php
namespace Search\Test\TestCase\Widgets;

use Cake\TestSuite\TestCase;
use Search\Widgets\Reports\LineChartReportWidget;

class LineChartReportWidgetTest extends TestCase
{
    public $widget;

    public function setUp()
    {
        $this->widget = new LineChartReportWidget();
    }

    public function tearDown()
    {
        unset($this->widget);

        parent::tearDown();
    }

    public function getType()
    {
        $this->assertEquals($this->widget->getType(), 'lineChart');
    }

    public function testGetScripts()
    {
        $content = $this->widget->getScripts([]);
        $this->assertInternalType('array', $content);
        $this->assertNotEmpty($content);
        $this->assertArrayHasKey('post', $content);
        $this->assertArrayHasKey('css', $content['post']);
        $this->assertArrayHasKey('javascript', $content['post']);
    }

    public function testGetContainerId()
    {
        $config = [
            'slug' => 'testLineChartGraph',
        ];

        $containerId = $this->widget->setContainerId($config);
        $this->assertEquals($containerId, 'graph_' . 'testLineChartGraph');
    }

    public function testGetChartColorsEmptyColorsConfig()
    {
        $config = [
            'modelName' => 'Reports',
            'slug' => 'bar_assigned_by_year',
            'info' => [
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

        $this->widget->setConfig($config);
        $this->widget->setContainerId($config);

        $result = $this->widget->getChartColors();

        $this->assertEquals($result, $this->widget->chartColors);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetChartColorsExpectionException()
    {
        $config = [
            'modelName' => 'Reports',
            'slug' => 'bar_assigned_by_year',
            'info' => [
                'id' => '00000000-0000-0000-0000-000000000002',
                'model' => 'Bar',
                'widget_type' => 'report',
                'name' => 'Report Bar',
                'query' => '',
                'colors' => '#08c6ba,#08c6b',
                'columns' => '',
                'renderAs' => 'barChart',
                'y_axis' => '',
                'x_axis' => ''
            ]
        ];

        $this->widget->setConfig($config);
        $this->widget->setContainerId($config);

        $result = $this->widget->getChartColors();

        $this->assertEquals($result, ['#08c6ba', '#08c6bc']);
    }

    public function testGetChartColors()
    {
        $config = [
            'modelName' => 'Reports',
            'slug' => 'bar_assigned_by_year',
            'info' => [
                'id' => '00000000-0000-0000-0000-000000000002',
                'model' => 'Bar',
                'widget_type' => 'report',
                'name' => 'Report Bar',
                'query' => '',
                'colors' => '#08c6ba,#08c6bc',
                'columns' => '',
                'renderAs' => 'barChart',
                'y_axis' => '',
                'x_axis' => ''
            ]
        ];

        $this->widget->setConfig($config);
        $this->widget->setContainerId($config);

        $result = $this->widget->getChartColors();

        $this->assertEquals($result, ['#08c6ba', '#08c6bc']);
    }

    public function testGetChartData()
    {
        $data = ['name' => 'foo'];

        $expected = [
            'chart' => 'lineChart',
            'options' => [
                'lineColors' => ['#0874c7', '#04645e', '#5661f8', '#8298c1', '#c6ba08', '#07ada3'],
                'element' => 'graph_bar_assigned_by_year',
                'resize' => true,
                'labels' => ['City', 'Country', 'Post Code'],
                'xkey' => ['name'],
                'ykeys' => ['id', 'title'],
                'data' => $data,
                'hideHover' => true,
            ]
        ];

        $config = [
            'modelName' => 'Reports',
            'slug' => 'bar_assigned_by_year',
            'info' => [
                'id' => '00000000-0000-0000-0000-000000000002',
                'model' => 'Bar',
                'widget_type' => 'report',
                'name' => 'Report Bar',
                'query' => '',
                'columns' => 'city,country,post_code',
                'renderAs' => 'lineChart',
                'y_axis' => 'id,title',
                'x_axis' => 'name'
            ]
        ];

        $this->widget->setConfig($config);
        $this->widget->setContainerId($config);

        $result = $this->widget->getChartData($data);
        $this->assertNotEmpty($result['options']['element']);
        $this->assertNotEmpty($result['options']['lineColors']);
        $this->assertNotEmpty($this->widget->getChartColors());

        $this->assertEquals($expected, $this->widget->getData());
    }
}
