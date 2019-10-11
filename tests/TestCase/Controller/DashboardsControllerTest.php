<?php
namespace Search\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;
use Search\Event\Model\WidgetsListener;

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
        'plugin.search.app_widgets',
        'plugin.search.articles',
        'plugin.search.dashboards',
        'plugin.search.saved_searches',
        'plugin.search.widgets',
        'plugin.roles_capabilities.groups_roles',
        'plugin.roles_capabilities.roles'
    ];

    public function setUp()
    {
        parent::setUp();

        Configure::write('Search.dashboard.columns', ['Left Side', 'Right Side']);
        $this->session(['Auth.User.id' => '00000000-0000-0000-0000-000000000001']);
        EventManager::instance()->on(new WidgetsListener());
    }

    public function testIndex(): void
    {
        // remove dashboards fixtures
        if (!empty($this->fixtureManager) && is_object($this->fixtureManager)) {
            $this->fixtureManager->unload($this);
        }

        $this->get('/search/dashboards');

        $this->assertResponseOk();
        $this->assertResponseContains(
            'There are no configured Dashboards for you. Please contact the system administrator.'
        );
    }

    public function testIndexRedirect(): void
    {
        $this->get('/search/dashboards');

        $this->assertRedirect();
        $this->assertRedirectContains('/search/dashboards/view');
    }

    public function testView(): void
    {
        $this->get('/search/dashboards/view/00000000-0000-0000-0000-000000000002');

        $this->assertResponseOk();

        $this->assertResponseContains('<h4>Lorem ipsum dolor sit amet</h4>');
    }

    public function testViewNonAdminUser(): void
    {
        $this->get('/search/dashboards/view/00000000-0000-0000-0000-000000000003');

        $this->assertResponseCode(403);
    }

    public function testViewAdminUser(): void
    {
        // admin user
        $this->session(['Auth.User.id' => '00000000-0000-0000-0000-000000000002']);
        $this->get('/search/dashboards/view/00000000-0000-0000-0000-000000000003');

        $this->assertResponseOk();
        $this->assertResponseContains('<h4>Everyone Dashboard</h4>');
    }

    public function testViewWithSavedSearch(): void
    {
        $this->session(['Auth.User.id' => '00000000-0000-0000-0000-000000000002']);
        $this->get('/search/dashboards/view/00000000-0000-0000-0000-000000000001');

        $this->assertResponseOk();
        $this->assertResponseContains('The rendering part of this widget needs');
    }

    public function testViewWithGroupBySavedSearch(): void
    {
        $this->session(['Auth.User.id' => '00000000-0000-0000-0000-000000000002']);
        $this->get('/search/dashboards/view/00000000-0000-0000-0000-000000000004');

        $this->assertResponseOk();
        $this->assertResponseContains('The rendering part of this widget needs');
    }

    public function testAdd(): void
    {
        $this->get('/search/dashboards/add');

        $this->assertResponseOk();
        $this->assertResponseContains('Create Dashboard');
        $this->assertResponseContains('Submit');
    }

    public function testAddPost(): void
    {
        $data = [
            'name' => 'Test Dashboard',
            'role_id' => '79928943-0016-4677-869a-e37728ff6564',
            'widgets' => [
                'widget_id' => ['00000000-0000-0000-0000-000000009999'],
                'widget_type' => ['saved_search'],
                'widget_options' => json_encode(['x' => 0, 'y' => 0, 'i' => '999', 'h' => '2', 'w' => '2']),
                'row' => ['0'],
                'column' => ['0']
            ]
        ];

        $this->post('/search/dashboards/add', $data);

        $this->assertRedirect();
        $this->assertRedirectContains('/search/dashboards/view');
    }

    public function testEdit(): void
    {
        $this->get('/search/dashboards/edit/00000000-0000-0000-0000-000000000001');

        $this->assertResponseOk();
        $this->assertResponseContains('Edit Dashboard');
        $this->assertResponseContains('Submit');
    }

    public function testEditPost(): void
    {
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

        $table = TableRegistry::getTableLocator()->get('Search.Dashboards');
        $entity = $table->get('00000000-0000-0000-0000-000000000001');

        $this->assertEquals('Test Dashboard', $entity->get('name'));
        $this->assertNull($entity->get('role_id'));
    }

    public function testDelete(): void
    {
        $this->delete('/search/dashboards/delete/00000000-0000-0000-0000-000000000001');

        $this->assertRedirect();
        $this->assertRedirectContains('/search/dashboards');

        $table = TableRegistry::getTableLocator()->get('Search.Dashboards');
        $query = $table->find()->where(['Dashboards.id' => '00000000-0000-0000-0000-000000000001']);

        $this->assertTrue($query->isEmpty());
    }
}
