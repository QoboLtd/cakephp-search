<?php
namespace Search\Test\TestCase\Widgets;

use Cake\TestSuite\TestCase;
use Search\Widgets\Reports\BarChartReportWidget;

class BarChartReportWidgetTest extends TestCase
{
    public $widget;

    public function setUp()
    {
        $this->widget = new BarChartReportWidget();
    }

    public function tearDown()
    {
        unset($this->widget);

        parent::tearDown();
    }

    public function testGetType()
    {
        $this->assertEquals('barChart', $this->widget->getType());
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
            'slug' => 'TestGraph',
        ];

        $result = $this->widget->setContainerId($config);
        $this->assertEquals($result, 'graph_' . 'TestGraph');
    }

    public function testSetConfig()
    {
        $data = [
            'foo' => 'bar',
        ];

        $this->widget->setConfig($data);

        $this->assertEquals($data, $this->widget->getConfig());
    }

    public function testGetChartData()
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
                'columns' => '',
                'renderAs' => 'barChart',
                'y_axis' => '',
                'x_axis' => ''
            ]
        ];

        $this->widget->setConfig($config);
        $this->widget->setContainerId($config);

        $result = $this->widget->getChartData([]);
        $this->assertNotEmpty($result['options']['element']);
        $this->assertNotEmpty($result['options']['barColors']);

        //as the data passed in the method is empty
        $this->assertEquals([], $this->widget->getData());
    }

    public function testGetChartDataWithData()
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
                'columns' => 'x,y',
                'renderAs' => 'barChart',
                'y_axis' => 'y',
                'x_axis' => 'x'
            ]
        ];

        $data = [
            [ 'x' => '1', 'y' => '2'],
            [ 'x' => '2', 'y' => '3'],
        ];

        $this->widget->setConfig($config);
        $this->widget->setContainerId($config);

        $result = $this->widget->getChartData($data);
        $this->assertNotEmpty($result['options']['element']);
        $this->assertNotEmpty($result['options']['barColors']);

        //as the data passed in the method is empty
        $this->assertNotEmpty($this->widget->getData());
        $this->assertEquals(['X', 'Y'], $result['options']['labels']);
    }
}
