<?php
namespace Search\Test\TestCase\Widgets;

use Cake\TestSuite\TestCase;
use Search\Widgets\Reports\DonutChartReportWidget;

class DonutChartReportWidgetTest extends TestCase
{
    public $widget;

    public function setUp()
    {
        $this->widget = new DonutChartReportWidget();
    }

    public function tearDown()
    {
        unset($this->widget);

        parent::tearDown();
    }

    public function testGetType()
    {
        $this->assertEquals('donutChart', $this->widget->getType());
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

    public function testSetContainerId()
    {
        $config = [
            'slug' => 'barChart',
        ];

        $containerId = $this->widget->setContainerId($config);
        $this->assertEquals($containerId, 'graph_' . $config['slug']);
    }

    public function testGetChartData()
    {
        $data = [
            [
                'name' => 'foo',
                'place' => 'bar'
            ]
        ];

        $expected = [
            'chart' => 'donutChart',
            'options' => [
                'element' => 'graph_bar_assigned_by_year',
                'resize' => true,
                'data' => [['value' => 'bar', 'label' => 'foo']]
            ]
        ];

        $config = [
            'modelName' => 'Reports',
            'slug' => 'bar_assigned_by_year',
            'info' => [
                'label' => 'name',
                'value' => 'place',
                'id' => '00000000-0000-0000-0000-000000000002',
                'model' => 'Bar',
                'widget_type' => 'report',
                'name' => 'Report Bar',
                'query' => '',
                'columns' => '',
                'renderAs' => 'donutChart',
                'y_axis' => '',
                'x_axis' => ''
            ]
        ];

        $this->widget->setConfig($config);
        $this->widget->setContainerId($config);

        $result = $this->widget->getChartData($data);
        $this->assertNotEmpty($result['options']['element']);

        $this->assertEquals($expected, $this->widget->getData());
    }
}
