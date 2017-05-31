<?php
namespace Search\Test\TestCase\Widgets;

use Cake\TestSuite\TestCase;
use Search\Widgets\Reports\TableReportWidget;

class TableReportWidgetTest extends TestCase
{
    public $widget;

    public function setUp()
    {
        $this->widget = new TableReportWidget();
    }

    public function tearDown()
    {
        unset($this->widget);

        parent::tearDown();
    }

    public function testGetType()
    {
        $this->assertEquals('table', $this->widget->getType());
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
                'renderAs' => 'table',
                'y_axis' => '',
                'x_axis' => ''
            ]
        ];

        $this->widget->setConfig($config);
        $this->widget->setContainerId($config);

        $result = $this->widget->getChartData(['foo']);
        $this->assertNotEmpty($result['options']['element']);

        $expected = [
            'chart' => 'table',
            'options' => [
                'element' => 'graph_bar_assigned_by_year',
                'resize' => true,
                'data' => ['foo']
            ]
        ];
        $this->assertEquals($expected, $this->widget->getData());
    }
}
