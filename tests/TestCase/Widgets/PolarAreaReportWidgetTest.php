<?php
namespace Search\Test\TestCase\Widgets;

use Cake\TestSuite\TestCase;
use Search\Widgets\Reports\PolarAreaReportWidget;

class PolarAreaReportWidgetTest extends TestCase
{
    public $widget;

    public function setUp()
    {
        $this->widget = new PolarAreaReportWidget();
    }

    public function tearDown()
    {
        unset($this->widget);

        parent::tearDown();
    }

    public function testGetType(): void
    {
        $this->assertEquals('polarArea', $this->widget->getType());
    }

    public function testGetScripts(): void
    {
        $content = $this->widget->getScripts([]);
        $this->assertInternalType('array', $content);
        $this->assertNotEmpty($content);
        $this->assertArrayHasKey('post', $content);
        $this->assertArrayHasKey('javascript', $content['post']);
    }

    public function testGetContainerId(): void
    {
        $config = [
            'slug' => 'barChart',
        ];

        $this->widget->setContainerId($config);
        $this->assertEquals('graph_barChart', $this->widget->getContainerId());
    }

    public function testGetChartData(): void
    {
        $data = [
            [
                'name' => 'foo',
                'place' => 'bar'
            ]
        ];

        $expected = [
            'chart' => 'polarArea',
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
                'columns' => 'name,place',
                'renderAs' => 'polarArea',
                'y_axis' => '',
                'x_axis' => ''
            ]
        ];

        $this->widget->setConfig($config);
        $this->widget->setContainerId($config);

        $result = $this->widget->getChartData($data);

        $this->assertNotEmpty($result['id']);
        $this->assertNotEmpty($result['options']['dataChart']['data']['labels']);
        $this->assertNotEmpty($result['options']['dataChart']['data']['datasets']);

        //as the data passed in the method is empty
        $this->assertNotEmpty($this->widget->getData());
        $this->assertEquals(['bar'], $result['options']['dataChart']['data']['labels']);
    }
}
