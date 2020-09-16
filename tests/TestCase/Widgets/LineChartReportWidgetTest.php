<?php
namespace Qobo\Search\Test\TestCase\Widgets;

use Cake\TestSuite\TestCase;
use Qobo\Search\Widgets\Reports\LineChartReportWidget;

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

    public function testGetType(): void
    {
        $this->assertEquals($this->widget->getType(), 'line');
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
            'slug' => 'testLineChartGraph',
        ];

        $this->widget->setContainerId($config);
        $this->assertEquals('graph_testLineChartGraph', $this->widget->getContainerId());
    }

    public function testGetChartData(): void
    {
        $data = ['name' => 'foo'];

        $expected = [
            'chart' => 'line',
            'id' => 'graph_bar_assigned_by_year',
            'options' => [
                'resize' => true,
                'hideHover' => true,
                'dataChart' => [
                    'type' => 'line',
                    'data' => [
                        'labels' => [],
                        'datasets' => [
                            [
                                'label' => 'City',
                                'data' => [],
                                'borderColor' => '#f68787',
                                'fill' => false,
                            ],
                            [
                                'label' => 'Country',
                                'data' => [],
                                'borderColor' => '#ff165d',
                                'fill' => false,
                            ],
                            [
                                'label' => 'Post Code',
                                'data' => [],
                                'borderColor' => '#30e3ca',
                                'fill' => false,
                            ],
                        ],
                    ],
                ],
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
                'columns' => 'city,country,post_code',
                'renderAs' => 'lineChart',
                'y_axis' => 'id,title',
                'x_axis' => 'name',
            ],
        ];

        $this->widget->setConfig($config);
        $this->widget->setContainerId($config);

        $result = $this->widget->getChartData($data);

        $this->assertEquals($expected, $this->widget->getData());
    }
}
