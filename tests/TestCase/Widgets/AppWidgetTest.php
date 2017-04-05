<?php
namespace Search\Test\TestCase\Widgets;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Search\Widgets\AppWidget;

class AppWidgetTest extends TestCase
{
    public $fixtures = [
        'plugin.search.app_widgets',
        'plugin.search.widgets',
    ];

    public $widget;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        $this->widget = new AppWidget(['foo' => 'bar']);

        $config = TableRegistry::exists('AppWidgets') ? [] : ['className' => 'Search\Model\Table\AppWidgetsTable'];
        $this->AppWidgets = TableRegistry::get('AppWidgets', $config);

        $config = TableRegistry::exists('Widgets') ? [] : ['className' => 'Search\Model\Table\WidgetsTable'];
        $this->Widgets = TableRegistry::get('Widgets', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->widget);
        unset($this->AppWidgets);
        unset($this->Widgets);

        parent::tearDown();
    }

    public function testGetType()
    {
        $this->assertEquals('app', $this->widget->getType());
    }

    public function testGetOptions()
    {
        $this->assertEquals(['foo' => 'bar'], $this->widget->getOptions());
    }

    public function testGetResults()
    {
        $widget = $this->Widgets->get('00000000-0000-0000-0000-000000000003');

        $this->assertEquals([], $this->widget->getResults(['entity' => $widget]));
        $this->assertEquals('Plugin/Search/Widgets/hello_world', $this->widget->getRenderElement());
    }

    public function testGetResultsWithDeletedWidget()
    {
        $widget = $this->Widgets->get('00000000-0000-0000-0000-000000000004');

        $this->assertEquals([], $this->widget->getResults(['entity' => $widget]));
        $this->assertEquals('Plugin/Search/Widgets/foobar', $this->widget->getRenderElement());
    }

    public function testGetRenderElement()
    {
        $widget = $this->Widgets->get('00000000-0000-0000-0000-000000000003');

        $this->widget->getResults(['entity' => $widget]);
        $this->assertEquals('Plugin/Search/Widgets/hello_world', $this->widget->getRenderElement());
    }

    public function testGetErrors()
    {
        $widget = $this->Widgets->get('00000000-0000-0000-0000-000000000004');

        $this->assertEquals([], $this->widget->getErrors());

        $this->widget->getResults(['entity' => $widget]);

        $this->assertEquals(['Widget "Foobar" has been deleted.'], $this->widget->getErrors());
    }
}
