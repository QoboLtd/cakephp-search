<?php
namespace Qobo\Search\Test\TestCase\Utility;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Qobo\Search\Utility\Export;

/**
 * Search\Utility\Export Test Case
 *
 * @property \Qobo\Search\Utility\Export $Export
 */
class ExportTest extends TestCase
{
    public $fixtures = [
        'plugin.Qobo/Search.Articles',
        'plugin.Qobo/Search.Authors',
        'plugin.Qobo/Search.SavedSearches',
    ];

    private $user;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->user = ['id' => '00000000-0000-0000-0000-000000000001'];
        Configure::write('CsvMigrations.modules.path', TESTS . 'config' . DS . 'data' . DS);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($user);

        parent::tearDown();
    }

    public function testCount(): void
    {
        $export = new Export('00000000-0000-0000-0000-000000000006', 'test_export', $this->user);

        $this->assertSame(3, $export->count());
    }

    public function testGetUrl(): void
    {
        $export = new Export('00000000-0000-0000-0000-000000000006', 'test_export', $this->user);
        $this->assertSame('/uploads/export/test_export.csv', $export->getUrl());
    }

    public function testExecute(): void
    {
        $export = new Export('00000000-0000-0000-0000-000000000006', 'test_export', $this->user);

        $count = $export->count();
        for ($page = 1; $page <= $count; $page++) {
            $export->execute($page, 1);
        }

        $path = self::getCsvPathFromUrl($export->getUrl());
        $this->assertTrue(file_exists($path));
        $this->assertTrue(is_readable($path));

        $data = self::readFromCsv($path);
        unlink($path);

        // csv rows must be equal to search records count, +2 for the headers row and last empty line
        $this->assertSame(3 + 2, count($data));
        $this->assertSame(['Article Title', 'Author Id', 'Content', 'Name (Author Id)', 'Status', 'Author type (Author Id)', 'Created'], $data[0]);
        $this->assertSame([
            'Second article title',
            'Stephen King', // validates that related UUID is converted to display field.
            '\'"Fovič"\' €€', // validates that value strips out html entities, html tags and trims spaces.
            'Stephen King',
            'Draft', // validates that list value is converted to its label.
            'Internal', // validates that list value from association field is converted to its label.
            '2016-04-27 08:21:54',
        ], $data[2]);
    }

    public function testExecuteWithGroupBy(): void
    {
        $export = new Export('00000000-0000-0000-0000-000000000005', 'test_group_export', $this->user);

        $export->execute(1, $export->count());

        $path = self::getCsvPathFromUrl($export->getUrl());
        $this->assertTrue(file_exists($path));
        $this->assertTrue(is_readable($path));

        $data = self::readFromCsv($path);
        unlink($path);

        // csv rows must be equal to search records count, +2 for the headers row and last empty line
        $this->assertSame(2 + 2, count($data));
        $this->assertSame(['Author Id', 'Article Title (COUNT)'], $data[0]);
        $this->assertSame(['Stephen King', '1'], $data[1]);
        $this->assertSame(['Mark Twain', '2'], $data[2]);
    }

    /**
     * @dataProvider validMagicValues
     */
    public function testGetMagicValue(string $value, string $expected): void
    {
        $export = new Export('00000000-0000-0000-0000-000000000005', 'test_group_export', $this->user);

        $this->assertSame($expected, $export->getMagicValue($value));
    }

    /**
     * @return mixed[]
     */
    public function validMagicValues(): array
    {
        return [
            ['%%me%%', '00000000-0000-0000-0000-000000000001'],
            ['%%today%%', date('Y-m-d')],
            ['%%yesterday%%', date('Y-m-d', time() - (24 * 60 * 60))],
            ['%%tomorrow%%', date('Y-m-d', time() + (24 * 60 * 60))],
            ['no-change', 'no-change'],
            ['%%no-change%%', '%%no-change%%'],
        ];
    }

    private static function getCsvPathFromUrl(string $url): string
    {
        $parts = explode('/', $url);

        return WWW_ROOT . 'uploads' . DS . 'export' . DS . end($parts);
    }

    /**
     * @return mixed[]
     */
    private static function readFromCsv(string $path): array
    {
        $result = [];

        /**
         * @var resource
         */
        $fh = fopen($path, 'r');
        while (!feof($fh)) {
            $result[] = fgetcsv($fh);
        }
        fclose($fh);

        return $result;
    }
}
