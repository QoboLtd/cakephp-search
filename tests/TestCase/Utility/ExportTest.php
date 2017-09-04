<?php
namespace Search\Utility;

use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;

/**
 * Search\Utility\Export Test Case
 */
class ExportTest extends TestCase
{
    public $fixtures = [
        'plugin.search.articles',
        'plugin.search.saved_searches'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

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

        $this->Export = new Export(
            '00000000-0000-0000-0000-000000000003',
            'Foobar',
            ['id' => '00000000-0000-0000-0000-000000000001']
        );
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Export);

        parent::tearDown();
    }

    public function testCount()
    {
        $this->assertEquals(1, $this->Export->count());
    }

    public function testGetUrl()
    {
        $this->assertEquals('/uploads/export/Foobar.csv', $this->Export->getUrl());
    }

    public function testExecute()
    {
        $this->Export->execute(1, 10);

        $path = WWW_ROOT . 'uploads' . DS . 'export' . DS . 'Foobar.csv';
        $this->assertTrue(file_exists($path));

        unlink($path);
    }
}
