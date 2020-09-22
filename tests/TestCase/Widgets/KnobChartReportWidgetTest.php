<?php
namespace Qobo\Search\Test\TestCase\Widgets;

use Cake\TestSuite\TestCase;
use Qobo\Search\Widgets\Reports\KnobChartReportWidget;

class KnobChartReportWidgetTest extends TestCase
{
    public $widget;

    public function setUp()
    {
        $this->widget = new KnobChartReportWidget();
    }

    public function tearDown()
    {
        unset($this->widget);

        parent::tearDown();
    }

    public function testGetType(): void
    {
        $this->assertEquals('knobChart', $this->widget->getType());
    }

    public function testGetScripts(): void
    {
        $content = $this->widget->getScripts([]);
        $this->assertInternalType('array', $content);
        $this->assertNotEmpty($content);
        $this->assertArrayHasKey('post', $content);
        $this->assertArrayHasKey('css', $content['post']);
        $this->assertArrayHasKey('javascript', $content['post']);
    }

    public function testGetChartData(): void
    {
        $data = [
            [
                'name' => 'foo',
                'place' => 'bar',
                'amount' => 100,
            ],
        ];

        $expected = [
            'chart' => 'knobChart',
            'options' => [
                'element' => 'graph_bar_assigned_by_year',
                'resize' => true,
                'data' => [['value' => 'foo', 'label' => 'bar', 'max' => 100]],
            ],
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
                'columns' => '',
                'renderAs' => 'knobChart',
                'y_axis' => '',
                'x_axis' => '',
                'max' => 'amount',
                'value' => 'name',
                'label' => 'place',
            ],
        ];

        $this->widget->setConfig($config);
        $this->widget->setContainerId($config);

        $result = $this->widget->getChartData($data);
        $this->assertNotEmpty($result['options']['element']);

        $this->assertEquals($expected, $this->widget->getData());
    }
}
