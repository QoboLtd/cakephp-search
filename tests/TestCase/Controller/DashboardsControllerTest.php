<?php
namespace Qobo\Search\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;
use Qobo\Search\Event\Model\WidgetsListener;
use Qobo\Search\Model\Entity\Dashboard;

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
        'plugin.CakeDC/Users.Users',
        'plugin.Qobo/Search.Groups',
        'plugin.Qobo/Search.GroupsUsers',
        'plugin.Qobo/Search.AppWidgets',
        'plugin.Qobo/Search.Articles',
        'plugin.Qobo/Search.Dashboards',
        'plugin.Qobo/Search.SavedSearches',
        'plugin.Qobo/Search.Widgets',
    ];

    public function setUp()
    {
        parent::setUp();

        Configure::write('Search.dashboard.columns', ['Left Side', 'Right Side']);
        $this->session(['Auth.User.id' => '00000000-0000-0000-0000-000000000003']);
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

        $this->assertResponseContains('<h4>Dashboard without group 1</h4>');
    }

    public function testViewNonAdminUser(): void
    {
        $this->get('/search/dashboards/view/00000000-0000-0000-0000-000000000003');

        $this->assertResponseOk();
        $this->assertResponseContains('<h4>Everyone Dashboard</h4>');
    }

    public function testViewFailNonAdminUser(): void
    {
        $this->get('/search/dashboards/view/00000000-0000-0000-0000-000000000001');

        $this->assertResponseCode(404);
    }

    public function testViewAdminUser(): void
    {
        // admin user
        $this->session(['Auth.User.id' => '00000000-0000-0000-0000-000000000001']);
        $this->get('/search/dashboards/view/00000000-0000-0000-0000-000000000001');

        $this->assertResponseOk();
        $this->assertResponseContains('<h4>Admins Dashboard</h4>');
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
            'group_id' => '00000000-0000-0000-0000-000000000003',
            'widgets' => [
                'widget_id' => ['00000000-0000-0000-0000-000000009999'],
                'widget_type' => ['saved_search'],
                'widget_options' => json_encode(['x' => 0, 'y' => 0, 'i' => '999', 'h' => '2', 'w' => '2']),
                'row' => ['0'],
                'column' => ['0'],
            ],
        ];

        $this->post('/search/dashboards/add', $data);

        $this->assertRedirect();
        $this->assertRedirectContains('/search/dashboards/view');
    }

    public function testAddFail(): void
    {
        $this->enableRetainFlashMessages();

        // prevent save
        EventManager::instance()->on('Model.beforeSave', function () {
            return false;
        });

        $data = [
            'name' => 'Test Dashboard',
            'group_id' => '79928943-0016-4677-869a-e37728ff6564',
        ];

        $this->post('/search/dashboards/add', $data);

        $this->assertResponseCode(200);
        $this->assertSession('The dashboard could not be saved. Please, try again.', 'Flash.flash.0.message');
    }

    public function testEdit(): void
    {
        $this->get('/search/dashboards/edit/00000000-0000-0000-0000-000000000001');

        $this->assertResponseOk();
        $this->assertResponseContains('Edit Dashboard');
        $this->assertResponseContains('Submit');
    }

    public function testEditFail(): void
    {
        $this->enableRetainFlashMessages();

        // prevent save
        EventManager::instance()->on('Model.beforeSave', function () {
            return false;
        });

        $data = [
            'name' => 'Test Dashboard',
            'group_id' => '79928943-0016-4677-869a-e37728ff6564',
        ];

        $this->put('/search/dashboards/edit/00000000-0000-0000-0000-000000000001', $data);

        $this->assertResponseCode(200);
        $this->assertSession('The dashboard could not be saved. Please, try again.', 'Flash.flash.0.message');
    }

    public function testEditPost(): void
    {
        $data = [
            'name' => 'Test Dashboard',
            'group_id' => null,
            'widgets' => [
                'widget_id' => ['00000000-0000-0000-0000-000000009999'],
                'widget_type' => ['saved_search'],
                'row' => ['0'],
                'column' => ['0'],
            ],
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

        $table = TableRegistry::getTableLocator()->get('Qobo/Search.Dashboards');
        $entity = $table->get('00000000-0000-0000-0000-000000000001');

        $this->assertEquals('Test Dashboard', $entity->get('name'));
        $this->assertNull($entity->get('group_id'));
    }

    public function testDelete(): void
    {
        $this->delete('/search/dashboards/delete/00000000-0000-0000-0000-000000000001');

        $this->assertRedirect();
        $this->assertRedirectContains('/search/dashboards');

        $table = TableRegistry::getTableLocator()->get('Qobo/Search.Dashboards');
        $query = $table->find()->where(['Dashboards.id' => '00000000-0000-0000-0000-000000000001']);

        $this->assertTrue($query->isEmpty());
    }

    public function testDeleteFail(): void
    {
        $this->enableRetainFlashMessages();

        // prevent save
        EventManager::instance()->on('Model.beforeDelete', function () {
            return false;
        });

        $this->delete('/search/dashboards/delete/00000000-0000-0000-0000-000000000001');

        $this->assertRedirect();
        $this->assertRedirectContains('/search/dashboards');

        $this->assertSession('The dashboard could not be deleted. Please, try again.', 'Flash.flash.0.message');

        $table = TableRegistry::getTableLocator()->get('Qobo/Search.Dashboards');
        $this->assertInstanceOf(Dashboard::class, $table->get('00000000-0000-0000-0000-000000000001'));
    }
}
