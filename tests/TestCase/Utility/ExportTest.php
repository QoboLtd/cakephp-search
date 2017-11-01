<?php
namespace Search\Utility;

use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;
use Search\Utility\Export;

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
                ],
                'Articles.created' => [
                    'type' => 'datetime',
                    'label' => 'Created',
                    'operators' => [
                        'is' => ['label' => 'is', 'operator' => 'IN']
                    ]
                ],
                'Articles.modified' => [
                    'type' => 'datetime',
                    'label' => 'Modified',
                    'operators' => [
                        'is' => ['label' => 'is', 'operator' => 'IN']
                    ]
                ],
                'Authors.name' => [
                    'type' => 'string',
                    'label' => 'Name',
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
        $this->assertEquals(2, $this->Export->count());
    }

    public function testGetUrl()
    {
        $this->assertEquals('/uploads/export/Foobar.csv', $this->Export->getUrl());
    }

    public function testExecute()
    {
        $count = $this->Export->count();
        $limit = 1;

        for ($page = 1; $page <= $count; $page++) {
            $this->Export->execute($page, $limit);
        }

        $parts = explode('/', $this->Export->getUrl());
        $path = WWW_ROOT . 'uploads' . DS . 'export' . DS . end($parts);
        $this->assertTrue(file_exists($path));
        $this->assertTrue(is_readable($path));

        $fh = fopen($path, 'r');
        $data = [];
        while (!feof($fh)) {
            $data[] = fgetcsv($fh);
        }
        fclose($fh);
        unlink($path);

        // remove empty csv rows
        $data = array_filter($data);

        // csv rows must be equal to search records count, +1 for the headers row
        $this->assertEquals($count + 1, count($data));
    }
}
