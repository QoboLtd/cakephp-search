<?php
namespace Search\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

/**
 * Search\Controller\DashboardsController Test Case
 */
class DashboardsControllerTest extends IntegrationTestCase
{

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.CakeDC/Users.users',
        'plugin.groups.groups',
        'plugin.groups.groups_users',
        'plugin.search.dashboards',
        'plugin.search.saved_searches',
        'plugin.search.widgets',
        'plugin.roles_capabilities.groups_roles',
        'plugin.roles_capabilities.roles'
    ];

    public function setUp()
    {
        Configure::write('Search.dashboard.columns', ['Left Side', 'Right Side']);

        $this->session(['Auth.User.id' => '00000000-0000-0000-0000-000000000001']);

        $table = TableRegistry::get('Search.SavedSearches');
        // anonymous event listener that defines searchable fields
        $table->eventManager()->on('Search.Model.Search.searchabeFields', function ($event, $table) {
            return [
                'Dashboards.name' => [
                    'type' => 'string',
                    'label' => 'Name',
                    'operators' => [
                        'contains' => ['label' => 'contains', 'operator' => 'LIKE', 'pattern' => '%{{value}}%']
                    ]
                ]
            ];
        });
    }

    /**
     * @todo find out why this test fails: https://travis-ci.org/QoboLtd/cakephp-search/jobs/167079767
     */
    public function testSearchNonSearchableModel()
    {
        // $this->post('/search/dashboards/search');

        // $this->assertResponseError();
    }

    public function testIndex()
    {
        // remove dashboards fixtures
        $this->fixtureManager->unload($this);

        $this->get('/search/dashboards');

        $this->assertResponseOk();
        $this->assertResponseContains(
            'There are no configured Dashboards for you. Please contact the system administrator.'
        );
    }

    public function testIndexRedirect()
    {
        $this->get('/search/dashboards');

        $this->assertRedirect();
        $this->assertRedirectContains('/search/dashboards/view');
    }

    public function testView()
    {
        $this->get('/search/dashboards/view/00000000-0000-0000-0000-000000000001');

        $this->assertResponseOk();

        $this->assertResponseContains('<h4>Lorem ipsum dolor sit amet</h4>');
        $this->assertResponseContains('Saved search criteria</a>');
        $this->assertResponseContains('<table');
        $this->assertResponseContains('<th>Name</th>');
        $this->assertResponseContains('<th class="actions">Actions</th>');
    }

    public function testAdd()
    {
        $this->get('/search/dashboards/add');

        $this->assertResponseOk();
        $this->assertResponseContains('Create Dashboard');
        $this->assertResponseContains('Submit');
    }

    public function testAddPost()
    {
        $data = [
            'name' => 'Test Dashboard',
            'role_id' => '79928943-0016-4677-869a-e37728ff6564',
            'widgets' => [
                'widget_id' => ['00000000-0000-0000-0000-000000009999'],
                'widget_type' => ['saved_search'],
                'row' => ['0'],
                'column' => ['0']
            ]
        ];

        $this->post('/search/dashboards/add', $data);

        $this->assertRedirect();
        $this->assertRedirectContains('/search/dashboards/view');
    }

    public function testEdit()
    {
        $this->get('/search/dashboards/edit/00000000-0000-0000-0000-000000000001');

        $this->assertResponseOk();
        $this->assertResponseContains('Edit Dashboard');
        $this->assertResponseContains('Submit');
    }

    public function testEditPost()
    {
        EventManager::instance()->on('Search.Dashboards.getWidgets', function ($event) {
            return [
                [
                    'type' => 'saved_search',
                    'data' => [
                        '00000000-0000-0000-0000-000000000002' => [
                            'id' => '00000000-0000-0000-0000-000000000002',
                            'name' => 'Test Saved Search',
                            'type' => 'criteria',
                            'user_id' => '5a5271e5-b1e6-4135-939a-e4576acbc557',
                            'model' => 'Contacts',
                            'shared' => 'private',
                            'content' => '',
                            'trashed' => null
                        ]
                    ]
                ]
            ];
        });

        $data = [
            'name' => 'Test Dashboard',
            'role_id' => null,
            'widgets' => [
                'widget_id' => ['00000000-0000-0000-0000-000000009999'],
                'widget_type' => ['saved_search'],
                'row' => ['0'],
                'column' => ['0']
            ]
        ];

        $this->put('/search/dashboards/edit/00000000-0000-0000-0000-000000000001', $data);

        $this->assertRedirect();
        $this->assertRedirectContains('/search/dashboards/view');

        $this->patch('/search/dashboards/edit/00000000-0000-0000-0000-000000000001', $data);

        $this->assertRedirect();
        $this->assertRedirectContains('/search/dashboards/view');

        $this->post('/search/dashboards/edit/00000000-0000-0000-0000-000000000001', $data);

        $this->assertRedirect();
        $this->assertRedirectContains('/search/dashboards/view');

        $table = \Cake\ORM\TableRegistry::get('Search.Dashboards');
        $entity = $table->get('00000000-0000-0000-0000-000000000001');

        $this->assertEquals('Test Dashboard', $entity->get('name'));
        $this->assertNull($entity->get('role_id'));
    }

    public function testDelete()
    {
        $this->delete('/search/dashboards/delete/00000000-0000-0000-0000-000000000001');

        $this->assertRedirect();
        $this->assertRedirectContains('/search/dashboards');

        $table = \Cake\ORM\TableRegistry::get('Search.Dashboards');
        $query = $table->find()->where(['Dashboards.id' => '00000000-0000-0000-0000-000000000001']);

        $this->assertTrue($query->isEmpty());
    }
}
