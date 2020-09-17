<?php
namespace Qobo\Search\Test\TestCase\Controller;

use Cake\Event\EventManager;
use Cake\TestSuite\IntegrationTestCase;
use Qobo\Search\Event\Model\WidgetsListener;

class WidgetsControllerTest extends IntegrationTestCase
{

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.Qobo/Search.Widgets',
        'plugin.Qobo/Search.AppWidgets',
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

        EventManager::instance()->on(new WidgetsListener());
    }

    public function testIndex(): void
    {
        $this->get('/search/widgets/index');
        $this->assertResponseCode(200);

        $responseBody = json_decode($this->_getBodyAsString(), true);
        $this->assertInternalType('array', $responseBody);
        $this->assertNotEmpty($responseBody);
    }

    public function testWithoutSession(): void
    {
        $this->_session = [];

        $this->get('/search/widgets/index');
        $this->assertResponseCode(403);
    }
}
