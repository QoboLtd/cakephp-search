<?php
namespace Search\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Qobo\Utils\TestSuite\JsonIntegrationTestCase;

/**
 * Search\Test\App\Controller\ArticlesController Test Case
 */
class ArticlesControllerTest extends JsonIntegrationTestCase
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

    public function testSearch(): void
    {
        $this->get('/articles/search');

        $this->assertResponseOk();
        // search options
        $this->assertResponseContains('<select name="fields" class="form-control input-sm" id="addFilter">');
        $this->assertResponseContains('<form method="post" class="save-search-form"');
        $this->assertResponseContains('id="available-columns"');
        $this->assertResponseContains('name="display_columns[]"');
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

    public function testSearchAjax(): void
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
        $this->assertJsonResponseOk();

        $this->assertResponseContains('pagination');
        $this->assertResponseContains('First article title');

        $response = $this->getParsedResponse();
        $this->assertEquals(2, $response->pagination->count);
    }

    public function testSearchAjaxWithPrimaryKey(): void
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
        $this->assertJsonResponseOk();

        $response = $this->getParsedResponse();
        $this->assertEquals('00000000-0000-0000-0000-000000000002', $response->data[0]->{'Articles.id'});
    }

    public function testSearchAjaxWithGroupBy(): void
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
        $this->assertJsonResponseOk();

        $response = $this->getParsedResponse();
        $this->assertEquals(2, $response->pagination->count);
    }

    public function testSearchPost(): void
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

    public function testSearchPostExisting(): void
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
        $this->assertEquals($expected->get('name'), $entity->get('name'));
        $this->assertEquals($expected->get('type'), $entity->get('type'));
        $this->assertEquals($expected->get('user_id'), $entity->get('user_id'));
        $this->assertEquals($expected->get('model'), $entity->get('model'));
        $this->assertEquals($expected->get('shared'), $entity->get('shared'));
        $this->assertNotEquals($expected->get('content'), $entity->get('content'));
    }

    public function testSaveSearch(): void
    {
        $id = '00000000-0000-0000-0000-000000000003';
        $data = ['name' => 'foo'];

        $table = TableRegistry::get('Search.SavedSearches');
        $expected = $table->get($id);

        $this->post('/articles/save-search/' . $id, $data);
        $this->assertRedirect();
        $this->assertRedirectContains('/articles/search');

        $entity = $table->get($id);
        $this->assertNotEquals($expected->get('name'), $entity->get('name'));
    }

    public function testEditSearch(): void
    {
        $id = '00000000-0000-0000-0000-000000000003';
        $preId = '00000000-0000-0000-0000-000000000002';
        $data = ['name' => 'some reAlLy R@nd0m N@me'];

        $table = TableRegistry::get('Search.SavedSearches');
        // before edit
        $entityBefore = $table->get($id);

        $this->post('/articles/edit-search/' . $preId . '/' . $id, $data);
        $this->assertRedirect();
        $this->assertRedirectContains('/articles/search');

        // after edit
        $entityAfter = $table->get($id);
        $this->assertEquals($data['name'], $entityAfter->get('name'));
        $this->assertEquals($entityBefore->get('user_id'), $entityAfter->get('user_id'));
        $this->assertEquals($entityBefore->get('model'), $entityAfter->get('model'));
        $this->assertEquals($entityBefore->get('system'), $entityAfter->get('system'));
        $this->assertEquals($entityBefore->get('trashed'), $entityAfter->get('trashed'));
        $this->assertEquals($entityBefore->get('created'), $entityAfter->get('created'));
        $this->assertNotEquals($entityBefore->get('name'), $entityAfter->get('name'));
        $this->assertNotEquals($entityBefore->get('content'), $entityAfter->get('content'));
        $this->assertNotEquals($entityBefore->get('modified'), $entityAfter->get('modified'));

        $preSaved = $table->get($preId);
        $this->assertEquals($preSaved->get('content'), $entityAfter->get('content'));
        $this->assertNotEquals($preSaved->get('user_id'), $entityAfter->get('user_id'));
        $this->assertNotEquals($preSaved->get('model'), $entityAfter->get('model'));
        $this->assertNotEquals($preSaved->get('system'), $entityAfter->get('system'));
        $this->assertNotEquals($preSaved->get('created'), $entityAfter->get('created'));
        $this->assertNotEquals($preSaved->get('modified'), $entityAfter->get('modified'));
    }

    public function testCopySearch(): void
    {
        $id = '00000000-0000-0000-0000-000000000003';

        $table = TableRegistry::get('Search.SavedSearches');
        $expected = $table->get($id);

        $this->post('/articles/copy-search/' . $id);
        $this->assertRedirect();
        $this->assertRedirectContains('/articles/search');

        /**
         * @var \Cake\Http\Response
         */
        $response = $this->_response;
        $location = explode('/', $response->getHeaderLine('Location'));
        $id = array_pop($location);
        $entity = $table->get($id);

        $this->assertEquals($expected->get('name'), $entity->get('name'));
        $this->assertEquals($expected->get('type'), $entity->get('type'));
        $this->assertEquals($expected->get('model'), $entity->get('model'));
        $this->assertEquals($expected->get('shared'), $entity->get('shared'));
        $this->assertEquals($expected->get('content'), $entity->get('content'));
        $this->assertEquals($expected->get('user_id'), $entity->get('user_id'));
    }

    public function testDeleteSearch(): void
    {
        $id = '00000000-0000-0000-0000-000000000003';

        $this->delete('/articles/delete-search/' . $id);
        $this->assertRedirect();
        $this->assertRedirectContains('/articles/search');

        $table = TableRegistry::get('Search.SavedSearches');
        $query = $table->find()->where(['SavedSearches.id' => $id]);

        $this->assertTrue($query->isEmpty());
    }

    public function testExportSearch(): void
    {
        $id = '00000000-0000-0000-0000-000000000003';

        $this->get('/articles/export-search/' . $id . '/lorem-ipsum');
        $this->assertResponseOk();

        $this->assertEquals('lorem-ipsum', $this->viewVariable('filename'));
        $this->assertEquals(2, $this->viewVariable('count'));
    }

    public function testExportSearchAjax(): void
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
