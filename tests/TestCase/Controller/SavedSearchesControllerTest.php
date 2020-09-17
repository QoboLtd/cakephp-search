<?php
namespace Qobo\Search\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

/**
 * Search\Controller\SavedSearchesController Test Case
 */
class SavedSearchesControllerTest extends IntegrationTestCase
{

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.CakeDC/Users.Users',
        'plugin.Qobo/Search.SavedSearches',
    ];

    public function setUp()
    {
        parent::setUp();

        $this->session(['Auth.User.id' => '00000000-0000-0000-0000-000000000001']);

        $this->configRequest([
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ],
        ]);
    }

    /**
     * Test index method
     *
     * @return void
     */
    public function testIndex(): void
    {
        $this->get('/search/saved-searches/index');

        $this->assertResponseCode(200);

        $response = json_decode($this->_getBodyAsString());

        $this->assertTrue($response->success);
        $this->assertTrue(is_array($response->data));
        $this->assertNotEmpty($response->data);
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testView(): void
    {
        $id = '00000000-0000-0000-0000-000000000001';

        $this->get('/search/saved-searches/view/' . $id);

        $this->assertResponseCode(200);

        $response = json_decode($this->_getBodyAsString());

        $this->assertTrue($response->success);
        $this->assertSame($id, $response->data->id);
    }

    public function testViewWithInvalidId(): void
    {
        $this->get('/search/saved-searches/view/INVALID_ID');

        $this->assertResponseCode(200);

        $response = json_decode($this->_getBodyAsString());

        $this->assertFalse($response->success);
        $this->assertSame('Failed to fetch saved search for record with ID "INVALID_ID".', $response->error);
    }

    /**
     * Test add method
     *
     * @return void
     */
    public function testAdd(): void
    {
        $data = [
            'name' => 'Foobar',
            'model' => 'Foo',
        ];

        $this->post('/search/saved-searches/add', $data);

        $this->assertResponseCode(200);

        $response = json_decode($this->_getBodyAsString());

        $this->assertTrue($response->success);
        $this->assertSame(36, strlen($response->data->id));
    }

    public function testAddWithMissingRequiredData(): void
    {
        $this->post('/search/saved-searches/add', []);

        $this->assertResponseCode(200);

        $response = json_decode($this->_getBodyAsString(), true);
        $this->assertFalse($response['success']);
        $expected = [
            'name' => ['_required' => 'This field is required'],
            'model' => ['_required' => 'This field is required'],
        ];
        $this->assertSame($expected, $response['error']);
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit(): void
    {
        $id = '00000000-0000-0000-0000-000000000001';

        $data = ['name' => 'Foobar'];

        $this->put('/search/saved-searches/edit/' . $id, $data);

        $this->assertResponseCode(200);

        $response = json_decode($this->_getBodyAsString());

        $this->assertTrue($response->success);
        $this->assertSame([], $response->data);
    }

    public function testEditWithInvalidData(): void
    {
        $id = '00000000-0000-0000-0000-000000000001';

        $this->put('/search/saved-searches/edit/' . $id, ['model' => null]);

        $this->assertResponseCode(200);

        $response = json_decode($this->_getBodyAsString());

        $this->assertFalse($response->success);
        $this->assertSame('The saved search could not be saved. Please, try again.', $response->error);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete(): void
    {
        $id = '00000000-0000-0000-0000-000000000001';

        $this->delete('/search/saved-searches/delete/' . $id);

        $this->assertResponseCode(200);

        $response = json_decode($this->_getBodyAsString());

        $this->assertTrue($response->success);
        $this->assertSame([], $response->data);
    }

    public function testDeleteWithInvalidId(): void
    {
        $this->delete('/search/saved-searches/delete/INVALID_ID');

        $this->assertResponseCode(200);

        $response = json_decode($this->_getBodyAsString());

        $this->assertFalse($response->success);
        $this->assertSame('The saved search could not be deleted. Please, try again.', $response->error);
    }
}
