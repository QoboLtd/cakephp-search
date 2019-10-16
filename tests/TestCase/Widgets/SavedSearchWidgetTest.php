<?php
namespace Search\Test\TestCase\Widgets;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Search\Model\Entity\SavedSearch;
use Search\Widgets\SavedSearchWidget;

class SavedSearchWidgetTest extends TestCase
{
    public $fixtures = [
        'plugin.Search.saved_searches',
        'plugin.Search.widgets'
    ];

    private $SavedSearches;
    private $Widgets;
    private $widget;

    public function setUp()
    {
        parent::setUp();

        $this->SavedSearches = TableRegistry::getTableLocator()->get('Search.SavedSearches');
        $this->Widgets = TableRegistry::getTableLocator()->get('Search.Widgets');
        $this->widget = new SavedSearchWidget(['entity' => $this->Widgets->get('00000000-0000-0000-0000-000000000002')]);
    }

    public function tearDown()
    {
        unset($this->widget);
        unset($this->Widgets);
        unset($this->SavedSearches);

        parent::tearDown();
    }

    public function testGetTitle(): void
    {
        $this->assertSame('Saved search', $this->widget->getTitle());
    }

    public function testGetIcon(): void
    {
        $this->assertSame('table', $this->widget->getIcon());
    }

    public function testGetColor(): void
    {
        $this->assertSame('info', $this->widget->getColor());
    }

    public function testGetType(): void
    {
        $this->assertSame('saved_search', $this->widget->getType());
    }

    public function testGetOptions(): void
    {
        $this->assertSame([], $this->widget->getOptions());
    }

    public function testGetData(): void
    {
        $this->assertNull($this->widget->getData());

        $this->widget->getResults(['entity' => $this->SavedSearches->get('00000000-0000-0000-0000-000000000002')]);

        $this->assertInstanceOf(SavedSearch::class, $this->widget->getData());
    }

    public function testGetErrors(): void
    {
        $this->assertSame([], $this->widget->getErrors());

        // non-persisted widget entity
        $widget = new SavedSearchWidget(['entity' => $this->Widgets->newEntity()]);
        $widget->getResults(['entity' => $this->SavedSearches->newEntity()]);

        $this->assertSame(['No data found for this entity'], $widget->getErrors());
    }

    public function testGetRenderElement(): void
    {
        $entity = $this->SavedSearches->get('00000000-0000-0000-0000-000000000001');

        $result = $this->widget->getContainerId();
        $this->assertSame($result, 'default-widget-container');

        $this->widget->setContainerId($entity);
        $result = $this->widget->getContainerId();

        $expected = 'table-datatable-' . md5($entity->id);
        $this->assertSame($result, $expected);
    }

    public function testGetResults() : void
    {
        $result = $this->widget->getResults(['entity' => $this->SavedSearches->newEntity()]);
        $this->assertInstanceOf(SavedSearch::class, $result);

        // non-persisted widget entity
        $widget = new SavedSearchWidget(['entity' => $this->Widgets->newEntity()]);
        $this->assertNull($widget->getResults(['entity' => $this->SavedSearches->newEntity()]));
    }

    public function testGetContainerId() : void
    {
        $savedSearchId = '00000000-0000-0000-0000-000000000002';
        $expected = SavedSearchWidget::TABLE_PREFIX . md5($savedSearchId);

        $this->widget->getResults(['entity' => $this->SavedSearches->get($savedSearchId)]);

        $this->assertSame($expected, $this->widget->getContainerId());
    }
}
