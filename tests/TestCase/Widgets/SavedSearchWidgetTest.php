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

    public function testGetType(): void
    {
        $this->assertEquals('saved_search', $this->widget->getType());
    }

    public function testGetOptions(): void
    {
        $this->assertEquals([], $this->widget->getOptions());
    }

    public function testGetData(): void
    {
        $this->assertEquals(null, $this->widget->getData());
    }

    public function testGetErrors(): void
    {
        $this->assertEquals([], $this->widget->getErrors());
    }

    public function testGetRenderElement(): void
    {
        $entity = $this->SavedSearches->get('00000000-0000-0000-0000-000000000001');

        $result = $this->widget->getContainerId();
        $this->assertEquals($result, 'default-widget-container');

        $this->widget->setContainerId($entity);
        $result = $this->widget->getContainerId();

        $expected = 'table-datatable-' . md5($entity->id);
        $this->assertEquals($result, $expected);
    }

    public function testGetResultsSavedResult(): void
    {
        $result = $this->widget->getResults([
            'entity' => $this->SavedSearches->get('00000000-0000-0000-0000-000000000001'),
            'user' => ['id' => '00000000-0000-0000-0000-000000000001']
        ]);

        $this->assertInstanceOf(SavedSearch::class, $result);
    }

    public function testGetResultsSavedCriteria(): void
    {
        $entity = $this->Widgets->get('00000000-0000-0000-0000-000000000005');
        $widget = new SavedSearchWidget(['entity' => $entity]);

        $result = $widget->getResults([
            'entity' => $this->SavedSearches->get('00000000-0000-0000-0000-000000000002'),
            'user' => ['id' => '00000000-0000-0000-0000-000000000001']
        ]);

        $this->assertInstanceOf(SavedSearch::class, $result);
    }
}
