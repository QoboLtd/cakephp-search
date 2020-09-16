<?php
namespace Qobo\Search\Test\TestCase\Widgets;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Qobo\Search\Widgets\AppWidget;

/**
 * @property \Search\Model\Table\AppWidgetsTable $AppWidgets
 * @property \Search\Model\Table\WidgetsTable $Widgets
 * @property \Search\Widgets\AppWidget $widget
 */
class AppWidgetTest extends TestCase
{
    public $fixtures = [
        'plugin.Search.AppWidgets',
        'plugin.Search.Widgets',
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

        $config = TableRegistry::getTableLocator()->exists('Qobo/Search.AppWidgets') ? [] : ['className' => 'Search\Model\Table\AppWidgetsTable'];
        /**
         * @var \Qobo\Search\Model\Table\AppWidgetsTable $table
         */
        $table = TableRegistry::getTableLocator()->get('Qobo/Search.AppWidgets', $config);
        $this->AppWidgets = $table;

        $config = TableRegistry::getTableLocator()->exists('Qobo/Search.Widgets') ? [] : ['className' => 'Search\Model\Table\WidgetsTable'];
        /**
         * @var \Qobo\Search\Model\Table\WidgetsTable $table
         */
        $table = TableRegistry::getTableLocator()->get('Qobo/Search.Widgets', $config);
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
        $this->assertEquals('', $this->widget->getRenderElement());
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

        $this->assertEquals(['Widget not found.'], $this->widget->getErrors());
    }
}
