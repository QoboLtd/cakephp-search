<?php
namespace Search\Test\TestCase\Widgets;

use Cake\Core\Configure;
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

    public function testGetType(): void
    {
        $this->assertEquals('bar', $this->widget->getType());
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
        // Configure::write('CsvMigrations.modules.path', ROOT . DS . 'test' . DS . 'config' . DS . 'data');

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

        $this->assertNotEmpty($result['id']);
        $this->assertNotEmpty($result['options']['dataChart']['data']['labels']);
        $this->assertNotEmpty($result['options']['dataChart']['data']['datasets']);

        //as the data passed in the method is empty
        $this->assertNotEmpty($this->widget->getData());
        $this->assertEquals('Y', $result['options']['dataChart']['data']['datasets'][0]['label']);
    }

    public function testSortListByLabel(): void
    {
        $result = [
            [
                'status' => 'Being Qualified',
                'total_amount' => '24'
            ],
            [
                'status' => 'Converted',
                'total_amount' => '4'
            ],
            [
                'status' => 'Dead',
                'total_amount' => '3'
            ],
            [
                'status' => 'Prospecting',
                'total_amount' => '27'
            ],
            [
                'status' => 'Qualified Opportunity',
                'total_amount' => '19'
            ],
            [
                'status' => 'Another status not listed',
                'total_amount' => '19'
            ]
        ];

        $list = [
            'very_dead' => [
                'label' => 'Very dead',
                'inactive' => true
            ],
            'dead' => [
                'label' => 'Dead',
                'inactive' => false
            ],
            'prospecting' => [
                'label' => 'Prospecting',
                'inactive' => false
            ],
            'being_qualified' => [
                'label' => 'Being Qualified',
                'inactive' => false
            ],
            'qualified_opportunity' => [
                'label' => 'Qualified Opportunity',
                'inactive' => false
            ],
            'converted' => [
                'label' => 'Converted',
                'inactive' => false
            ]
        ];

        $sort = [
            [
                'status' => 'Dead',
                'total_amount' => '3'
            ],
            [
                'status' => 'Prospecting',
                'total_amount' => '27'
            ],
            [
                'status' => 'Being Qualified',
                'total_amount' => '24'
            ],
            [
                'status' => 'Qualified Opportunity',
                'total_amount' => '19'
            ],
            [
                'status' => 'Converted',
                'total_amount' => '4'
            ],
            [
                'status' => 'Another status not listed',
                'total_amount' => '19'
            ]
        ];

        $data = $this->widget->sortListByLabel($result, $list, 'status');
        $this->assertEquals($sort, $data);
    }
}
