<?php
namespace Search\Test\TestCase\Shell;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\ConsoleIntegrationTestCase;

/**
 * Search\Shell\SearchShell Test Case
 */
class SearchShellTest extends ConsoleIntegrationTestCase
{
    public $fixtures = [
        'plugin.CakeDC/Users.users',
        'plugin.Search.saved_searches'
    ];

    private $table;

    public function setUp(): void
    {
        parent::setUp();

        $this->table = TableRegistry::getTableLocator()->get('Search.SavedSearches');

        $this->table->deleteAll([]);

        $data = [
            'name' => null,
            'model' => 'Foobar',
            'content' => ['saved' => 'Lorem', 'latest' => 'Ipsum'],
            'user_id' => '00000000-0000-0000-0000-000000000001',
            'modified' => date('Y-m-d H:i:s', time() - WEEK)
        ];

        // create test data
        $entities = $this->table->newEntities([$data, $data, $data, $data]);
        foreach ($entities as $entity) {
            $this->table->save($entity);
        }
    }

    public function tearDown(): void
    {
        unset($this->table);

        parent::tearDown();
    }

    public function testCleanup(): void
    {
        $this->assertSame(4, $this->table->find('all')->count());

        $this->exec('search cleanup');

        $this->assertOutputContains('4 outdated pre-saved searches removed.');
        $this->assertSame(0, $this->table->find('all')->count());
    }

    public function testCleanupWithTime(): void
    {
        $this->assertSame(4, $this->table->find('all')->count());

        $this->exec('search cleanup yesterday');

        $this->assertOutputContains('4 outdated pre-saved searches removed.');
        $this->assertSame(0, $this->table->find('all')->count());
    }

    public function testCleanupWithInvalidTime(): void
    {
        $this->exec('search cleanup foobar');

        $this->assertErrorContains('Failed to remove pre-saved searches, unsupported time value provided: foobar');
        $this->assertSame(4, $this->table->find('all')->count());
    }
}
