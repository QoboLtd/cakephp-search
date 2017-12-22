<?php
namespace Search\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

/**
 * Search\Test\App\Controller\ArticlesController Test Case
 */
class ArticlesControllerTest extends IntegrationTestCase
{

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.search.articles',
        'plugin.search.saved_searches',
    ];

    public function setUp()
    {
        parent::setUp();

        $dir = dirname(dirname(__DIR__)) . DS . 'config' . DS . 'data' . DS;
        Configure::write('CsvMigrations.modules.path', $dir);
        // Configure::write('Search.dashboard.columns', ['Left Side', 'Right Side']);

        $this->session(['Auth.User.id' => '00000000-0000-0000-0000-000000000001']);

        // anonymous event listener that defines searchable fields
        EventManager::instance()->on('Search.Model.Search.searchabeFields', function ($event, $table) {
            return [
                'Articles.title' => [
                    'type' => 'string',
                    'label' => 'Title',
                    'operators' => [
                        'contains' => ['label' => 'contains', 'operator' => 'LIKE', 'pattern' => '%{{value}}%']
                    ]
                ]
            ];
        });
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    public function testSearch()
    {
        $this->get('/articles/search');

        $this->assertResponseOk();
        // search options
        $this->assertResponseContains('<select name="fields" class="form-control input-sm" id="addFilter">');
        $this->assertResponseContains('<form method="post" class="save-search-form"');
        $this->assertResponseContains('<ul id="availableColumns"');
        $this->assertResponseContains('<ul id="displayColumns"');
        $this->assertResponseContains('<select name="sort_by_field"');
        $this->assertResponseContains('<select name="sort_by_order"');
        $this->assertResponseContains('<select name="group_by"');
        $this->assertResponseContains('value="Articles.title"');
        $this->assertResponseNotContains('value="Articles.content"');
        $this->assertResponseNotContains('value="Articles.role_id"');
        $this->assertResponseNotContains('value="Articles.created"');
        $this->assertResponseNotContains('value="Articles.modified"');
        // search result
        $this->assertResponseContains('Articles</a>');
        $this->assertResponseContains('<table');
        $this->assertResponseContains('<th>Title</th>');
        $this->assertResponseContains('<th class="actions">Actions</th>');
    }

    public function testSearchAjax()
    {
        EventManager::instance()->on('Search.Model.Search.afterFind', function ($event, $entities, $table) {
            return $entities;
        });

        $this->configRequest([
            'headers' => ['Accept' => 'application/json'],
            'environment' => [
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
            ]
        ]);

        $this->get('/articles/search/00000000-0000-0000-0000-000000000003');
        $this->assertResponseOk();

        $this->assertResponseContains('data');
        $this->assertResponseContains('pagination');
        $this->assertResponseContains('First article title');

        $response = json_decode($this->_getBodyAsString());
        $this->assertTrue($response->success);
        $this->assertEquals(2, $response->pagination->count);
    }

    public function testSearchAjaxWithPrimaryKey()
    {
        EventManager::instance()->on('Search.Model.Search.afterFind', function ($event, $entities, $table) {
            return $entities;
        });

        $this->configRequest([
            'headers' => ['Accept' => 'application/json'],
            'environment' => [
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
            ]
        ]);

        $this->get('/articles/search/00000000-0000-0000-0000-000000000003');

        $response = json_decode($this->_getBodyAsString());
        $this->assertEquals('00000000-0000-0000-0000-000000000002', $response->data[0]->{'Articles.id'});
    }

    public function testSearchAjaxWithGroupBy()
    {
        EventManager::instance()->on('Search.Model.Search.afterFind', function ($event, $entities, $table) {
            return $entities;
        });

        $this->configRequest([
            'headers' => ['Accept' => 'application/json'],
            'environment' => [
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
            ]
        ]);

        $this->get('/articles/search/00000000-0000-0000-0000-000000000005');

        $response = json_decode($this->_getBodyAsString());
        $this->assertTrue($response->success);
        $this->assertEquals(2, $response->pagination->count);
    }

    public function testSearchPost()
    {
        $data = [
            'aggregator' => 'AND',
            'criteria' => [
                'Articles.title' => [
                    679 => ['type' => 'string', 'operator' => 'contains', 'value' => 'Second']
                ]
            ],
            'sort_by_field' => 'Articles.title',
            'sort_by_order' => 'desc',
            'group_by' => 'Articles.author_id',
            'display_columns' => ['Articles.title']
        ];

        $this->post('/articles/search', $data);
        $this->assertRedirect();
        $this->assertRedirectContains('/articles/search');
    }

    public function testSearchPostExisting()
    {
        $id = '00000000-0000-0000-0000-000000000003';
        $data = [
            'aggregator' => 'AND',
            'criteria' => [
                'Articles.title' => [
                    679 => ['type' => 'string', 'operator' => 'contains', 'value' => 'Second']
                ]
            ],
            'sort_by_field' => 'Articles.title',
            'sort_by_order' => 'desc',
            'display_columns' => ['Articles.title']
        ];

        $table = TableRegistry::get('Search.SavedSearches');
        $expected = $table->get($id);

        $this->post('/articles/search/' . $id, $data);
        $this->assertRedirect();
        $this->assertRedirectContains('/articles/search');

        $entity = $table->get($id);
        $this->assertEquals($expected->id, $entity->id);
        $this->assertEquals($expected->name, $entity->name);
        $this->assertEquals($expected->type, $entity->type);
        $this->assertEquals($expected->user_id, $entity->user_id);
        $this->assertEquals($expected->model, $entity->model);
        $this->assertEquals($expected->shared, $entity->shared);
        $this->assertNotEquals($expected->content, $entity->content);
    }

    public function testSaveSearch()
    {
        $id = '00000000-0000-0000-0000-000000000003';
        $data = ['name' => 'foo'];

        $table = TableRegistry::get('Search.SavedSearches');
        $expected = $table->get($id);

        $this->post('/articles/save-search/' . $id, $data);
        $this->assertRedirect();
        $this->assertRedirectContains('/articles/search');

        $entity = $table->get($id);
        $this->assertNotEquals($expected->name, $entity->name);
    }

    public function testEditSearch()
    {
        $id = '00000000-0000-0000-0000-000000000003';
        $preId = '00000000-0000-0000-0000-000000000002';
        $data = ['name' => 'foo'];

        $table = TableRegistry::get('Search.SavedSearches');
        $expected = $table->get($id);

        $this->post('/articles/edit-search/' . $preId . '/' . $id);
        $this->assertRedirect();
        $this->assertRedirectContains('/articles/search');

        $entity = $table->get($id);
        $this->assertEquals($expected->type, $entity->type);
        $this->assertEquals($expected->shared, $entity->shared);
        $this->assertEquals($expected->user_id, $entity->user_id);
        $this->assertNotEquals($expected->name, $entity->name);
        $this->assertNotEquals($expected->model, $entity->model);
        $this->assertNotEquals($expected->content, $entity->content);

        $expected = $table->get($preId);
        $this->assertEquals($expected->name, $entity->name);
        $this->assertEquals($expected->type, $entity->type);
        $this->assertEquals($expected->model, $entity->model);
        $this->assertEquals($expected->shared, $entity->shared);
        $this->assertEquals($expected->content, $entity->content);
        $this->assertEquals($expected->user_id, $entity->user_id);
    }

    public function testCopySearch()
    {
        $id = '00000000-0000-0000-0000-000000000003';

        $table = TableRegistry::get('Search.SavedSearches');
        $expected = $table->get($id);

        $this->post('/articles/copy-search/' . $id);
        $this->assertRedirect();
        $this->assertRedirectContains('/articles/search');

        $location = explode('/', $this->_response->getHeaderLine('Location'));
        $id = array_pop($location);
        $entity = $table->get($id);

        $this->assertEquals($expected->name, $entity->name);
        $this->assertEquals($expected->type, $entity->type);
        $this->assertEquals($expected->model, $entity->model);
        $this->assertEquals($expected->shared, $entity->shared);
        $this->assertEquals($expected->content, $entity->content);
        $this->assertEquals($expected->user_id, $entity->user_id);
    }

    public function testDeleteSearch()
    {
        $id = '00000000-0000-0000-0000-000000000003';

        $this->delete('/articles/delete-search/' . $id);
        $this->assertRedirect();
        $this->assertRedirectContains('/articles/search');

        $table = TableRegistry::get('Search.SavedSearches');
        $query = $table->find()->where(['SavedSearches.id' => $id]);

        $this->assertTrue($query->isEmpty());
    }

    public function testExportSearch()
    {
        $id = '00000000-0000-0000-0000-000000000003';

        $this->get('/articles/export-search/' . $id . '/lorem-ipsum');
        $this->assertResponseOk();

        $this->assertEquals('lorem-ipsum', $this->viewVariable('filename'));
        $this->assertEquals(2, $this->viewVariable('count'));
    }

    public function testExportSearchAjax()
    {
        $id = '00000000-0000-0000-0000-000000000003';

        $this->configRequest([
            'headers' => ['Accept' => 'application/json'],
            'environment' => [
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
            ]
        ]);

        $this->get('/articles/export-search/' . $id . '/lorem-ipsum');
        $this->assertResponseOk();

        $this->assertTrue($this->viewVariable('success'));
        $data = $this->viewVariable('data');
        $this->assertEquals('/uploads/export/lorem-ipsum.csv', $data['path']);

        $parts = explode('/', $data['path']);
        $path = WWW_ROOT . 'uploads' . DS . 'export' . DS . end($parts);
        $this->assertTrue(file_exists($path));

        unlink($path);
    }
}
