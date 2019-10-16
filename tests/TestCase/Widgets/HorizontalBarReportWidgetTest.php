<?php
namespace Search\Test\TestCase\Widgets;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Search\Widgets\Reports\HorizontalBarReportWidget;

class HorizontalBarReportWidgetTest extends TestCase
{
    public $widget;

    public function setUp()
    {
        parent::setUp();

        $this->widget = new HorizontalBarReportWidget();
        Configure::write('CsvMigrations.modules.path', TESTS . 'config' . DS . 'data' . DS);
    }

    public function tearDown()
    {
        unset($this->widget);

        parent::tearDown();
    }

    public function testGetType(): void
    {
        $this->assertEquals('horizontalBar', $this->widget->getType());
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
            'slug' => 'TestGraph',
        ];

        $this->widget->setContainerId($config);

        $this->assertEquals('graph_TestGraph', $this->widget->getContainerId());
    }

    public function testSetConfig(): void
    {
        $data = [
            'foo' => 'bar',
        ];

        $this->widget->setConfig($data);

        $this->assertEquals($data, $this->widget->getConfig());
    }

    public function testGetChartData(): void
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
                'renderAs' => 'horizontalBar',
                'y_axis' => '',
                'x_axis' => ''
            ]
        ];

        $this->widget->setConfig($config);
        $this->widget->setContainerId($config);

        $result = $this->widget->getChartData([]);

        $this->assertNotEmpty($result['id']);
        $this->assertNotEmpty($result['options']['dataChart']);

        //as the data passed in the method is empty
        $this->assertEquals([], $this->widget->getData());
    }

    public function testGetChartDataWithData(): void
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
                'renderAs' => 'horizontalBar',
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

        $this->assertNotEmpty($result['id']);
        $this->assertNotEmpty($result['options']['dataChart']['data']['labels']);
        $this->assertNotEmpty($result['options']['dataChart']['data']['datasets']);

        //as the data passed in the method is empty
        $this->assertNotEmpty($this->widget->getData());
        $this->assertEquals('Y', $result['options']['dataChart']['data']['datasets'][0]['label']);
    }
}
