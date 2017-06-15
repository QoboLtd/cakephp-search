<?php
namespace Search\Test\TestCase\Widgets;

use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Search\Widgets\SavedSearchWidget;

class SavedSearchWidgetTest extends TestCase
{
    protected $widget;

    public $Widgets;

    public $fixtures = [
        'plugin.search.widgets',
        'plugin.search.saved_searches',
        'plugin.CakeDC/Users.users',
    ];

    public function setUp()
    {
        parent::setUp();

        $config = TableRegistry::exists('SavedSearches') ? [] : ['className' => 'Search\Model\Table\SavedSearchesTable'];
        $this->SavedSearches = TableRegistry::get('SavedSearches', $config);

        $config = TableRegistry::exists('Widgets') ? [] : ['className' => 'Search\Model\Table\WidgetsTable'];
        $this->Widgets = TableRegistry::get('Widgets', $config);

        $widget = $this->Widgets->get('00000000-0000-0000-0000-000000000002');
        $this->widget = new SavedSearchWidget(['entity' => $widget]);

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

    public function testGetType()
    {
        $this->assertEquals('saved_search', $this->widget->getType());
    }

    public function testGetOptions()
    {
        $this->assertEquals([], $this->widget->getOptions());
    }

    public function testGetData()
    {
        $this->assertEquals([], $this->widget->getData());
    }

    public function testGetErrors()
    {
        $this->assertEquals([], $this->widget->getErrors());
    }

    public function testGetRenderElement()
    {
        $entity = $this->SavedSearches->get('00000000-0000-0000-0000-000000000001');

        $result = $this->widget->getContainerId();
        $this->assertEquals($result, 'default-widget-container');

        $this->widget->setContainerId($entity);
        $result = $this->widget->getContainerId();

        $expected = 'table-datatable-' . md5($entity->id);
        $this->assertEquals($result, $expected);
    }

    public function testGetResultsSavedResult()
    {
        $result = $this->widget->getResults([
            'entity' => $this->SavedSearches->get('00000000-0000-0000-0000-000000000001'),
            'user' => ['id' => '00000000-0000-0000-0000-000000000001']
        ]);
    }

    public function testGetResultsSavedCriteria()
    {
        $entity = $this->Widgets->get('00000000-0000-0000-0000-000000000005');
        $widget = new SavedSearchWidget(['entity' => $entity]);

        $result = $widget->getResults([
            'entity' => $this->SavedSearches->get('00000000-0000-0000-0000-000000000002'),
            'user' => ['id' => '00000000-0000-0000-0000-000000000001']
        ]);
    }

    public function testGetSavedSearchType()
    {
        $entity = $this->SavedSearches->get('00000000-0000-0000-0000-000000000001');

        $result = $this->widget->getResults([
            'entity' => $entity,
            'user' => ['id' => '00000000-0000-0000-0000-000000000001']
        ]);

        $this->assertEquals('result', $this->widget->getSavedSearchType());
    }
}
