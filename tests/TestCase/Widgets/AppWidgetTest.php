<?php
namespace Search\Test\TestCase\Widgets;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Search\Widgets\AppWidget;

/**
 * @property \Search\Model\Table\AppWidgetsTable $AppWidgets
 * @property \Search\Model\Table\WidgetsTable $Widgets
 * @property \Search\Widgets\AppWidget $widget
 */
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

        $config = TableRegistry::exists('Search.AppWidgets') ? [] : ['className' => 'Search\Model\Table\AppWidgetsTable'];
        /**
         * @var \Search\Model\Table\AppWidgetsTable $table
         */
        $table = TableRegistry::get('Search.AppWidgets', $config);
        $this->AppWidgets = $table;

        $config = TableRegistry::exists('Search.Widgets') ? [] : ['className' => 'Search\Model\Table\WidgetsTable'];
        /**
         * @var \Search\Model\Table\WidgetsTable $table
         */
        $table = TableRegistry::get('Search.Widgets', $config);
        $this->Widgets = $table;
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

    public function testGetType(): void
    {
        $this->assertEquals('app', $this->widget->getType());
    }

    public function testGetOptions(): void
    {
        $this->assertEquals(['foo' => 'bar'], $this->widget->getOptions());
    }

    public function testGetResults(): void
    {
        $widget = $this->Widgets->get('00000000-0000-0000-0000-000000000003');

        $this->assertEquals([], $this->widget->getResults(['entity' => $widget]));
        $this->assertEquals('Plugin/Search/Widgets/hello_world', $this->widget->getRenderElement());
    }

    public function testGetResultsWithDeletedWidget(): void
    {
        $widget = $this->Widgets->get('00000000-0000-0000-0000-000000000004');

        $this->assertEquals([], $this->widget->getResults(['entity' => $widget]));
        $this->assertEquals('Plugin/Search/Widgets/foobar', $this->widget->getRenderElement());
    }

    public function testGetRenderElement(): void
    {
        $widget = $this->Widgets->get('00000000-0000-0000-0000-000000000003');

        $this->widget->getResults(['entity' => $widget]);
        $this->assertEquals('Plugin/Search/Widgets/hello_world', $this->widget->getRenderElement());
    }

    public function testGetErrors(): void
    {
        $widget = $this->Widgets->get('00000000-0000-0000-0000-000000000004');

        $this->assertEquals([], $this->widget->getErrors());

        $this->widget->getResults(['entity' => $widget]);

        $this->assertEquals(['Widget "Foobar" has been deleted.'], $this->widget->getErrors());
    }
}
