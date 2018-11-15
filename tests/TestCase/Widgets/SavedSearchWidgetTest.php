<?php
namespace Search\Test\TestCase\Widgets;

use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Search\Model\Entity\SavedSearch;
use Search\Widgets\SavedSearchWidget;

/**
 * @property \Search\Model\Table\SavedSearchesTable $SavedSearches
 * @property \Search\Model\Table\Widgets $Widgets
 * @property \Search\Widgets\SavedSearchWidget $widget
 */
class SavedSearchWidgetTest extends TestCase
{
    protected $widget;

    public $Widgets;

    public $fixtures = [
        'plugin.Search.dashboards',
        'plugin.Search.saved_searches',
        'plugin.Search.widgets',
        'plugin.CakeDC/Users.users',
    ];

    public function setUp()
    {
        parent::setUp();

        $config = TableRegistry::exists('Search.SavedSearches') ? [] : ['className' => 'Search\Model\Table\SavedSearchesTable'];
        /**
         * @var \Search\Model\Table\SavedSearchesTable $table
         */
        $table = TableRegistry::get('Search.SavedSearches', $config);
        $this->SavedSearches = $table;

        $config = TableRegistry::exists('Search.Widgets') ? [] : ['className' => 'Search\Model\Table\WidgetsTable'];
        /**
         * @var \Search\Model\Table\WidgetsTable $table
         */
        $table = TableRegistry::get('Search.Widgets', $config);
        $this->Widgets = $table;

        $this->widget = new SavedSearchWidget(['entity' => $this->Widgets->get('00000000-0000-0000-0000-000000000002')]);

        // anonymous event listener that passes some dummy searchable fields
        EventManager::instance()->on(
            'Search.Model.Search.searchabeFields',
            function ($event, $table) {
                return [
                    'name' => [
                        'operators' => [
                            'contains' => [
                                'label' => 'contains',
                                'operator' => 'LIKE',
                                'pattern' => '%{{value}}%',
                            ],
                        ],
                    ],
                    'first_name' => [],
                    'last_name' => [],
                    'street' => [],
                    'city' => [],
                ];
            }
        );
    }

    public function tearDown()
    {
        unset($this->SavedSearches);
        unset($this->widget);
        unset($this->Widgets);

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
