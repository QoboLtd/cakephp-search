<?php
namespace Search\Test\TestCase\Widgets;

use Cake\TestSuite\TestCase;
use Cake\View\View;
use Search\Widgets\ReportWidget;
use Search\Widgets\SavedSearchWidget;
use Search\Widgets\WidgetFactory;

/**
 * @property \Cake\View\View $appView
 */
class WidgetFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->appView = new View();
    }

    /**
     * @dataProvider dataProviderWidgets
     * @param mixed[] $widgetConfig
     * @param string $expectedClass
     */
    public function testCreate(array $widgetConfig, string $expectedClass): void
    {
        $entity = (object)[
            'widget_type' => $widgetConfig['widget_type'],
        ];

        $widget = WidgetFactory::create($widgetConfig['widget_type'], ['entity' => $entity]);

        $this->assertInstanceOf($expectedClass, $widget);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCreateException(): void
    {
        $config = ['widget_type' => 'foobar'];

        $entity = (object)[
            'widget_type' => $config['widget_type'],
        ];

        WidgetFactory::create($config['widget_type'], ['entity' => $entity]);
    }

    /**
     * @dataProvider dataProviderWidgetTypes
     * @param mixed[] $widgetConfig
     * @param string $expectedClass
     */
    public function testGetType(array $widgetConfig, string $expectedClass): void
    {
        $entity = (object)[
            'widget_type' => $widgetConfig['widget_type'],
        ];

        $widget = WidgetFactory::create($widgetConfig['widget_type'], ['entity' => $entity]);

        $this->assertInstanceOf($expectedClass, $widget);
        $this->assertEquals($widgetConfig['widget_type'], $widget->getType());
    }

    /**
     * @return mixed[]
     */
    public function dataProviderWidgets(): array
    {
        return [
            [['widget_type' => 'saved_search'], SavedSearchWidget::class],
            [['widget_type' => 'report'], ReportWidget::class],
        ];
    }

    /**
     * @return mixed[]
     */
    public function dataProviderWidgetTypes(): array
    {
        return [
            [['widget_type' => 'saved_search'], SavedSearchWidget::class],
        ];
    }
}
