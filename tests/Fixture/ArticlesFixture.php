<?php
namespace Qobo\Search\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ArticlesFixture
 *
 */
class ArticlesFixture extends TestFixture
{

    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'author_id' => ['type' => 'uuid', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'title' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'status' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'content' => ['type' => 'text', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'published' => ['type' => 'boolean', 'length' => null, 'null' => false, 'default' => false, 'comment' => '', 'precision' => null],
        'priority' => ['type' => 'decimal', 'length' => null, 'null' => false, 'default' => false, 'comment' => '', 'precision' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'utf8_general_ci'
        ],
    ];
    // @codingStandardsIgnoreEnd

    /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            'id' => '00000000-0000-0000-0000-000000000001',
            'author_id' => '00000000-0000-0000-0000-000000000002',
            'title' => 'First article title',
            'status' => 'published',
            'content' => 'First article content.',
            'created' => '2016-12-27 12:25:30',
            'modified' => '2017-05-22 08:21:50',
            'published' => 1,
            'priority' => 10,
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000002',
            'author_id' => '00000000-0000-0000-0000-000000000001',
            'title' => 'Second article title',
            'status' => 'draft',
            'content' => '&nbsp; &nbsp;&#39;&quot;<a>Fovi&#269;</a>&quot;&#39; &euro;&#8364; &nbsp;  ',
            'created' => '2016-04-27 08:21:54',
            'modified' => '2016-04-27 08:21:54',
            'published' => 0,
            'priority' => 20,
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000003',
            'author_id' => '00000000-0000-0000-0000-000000000002',
            'title' => 'Third article title',
            'status' => 'trashed',
            'content' => 'Third article title',
            'created' => '2019-07-25 16:00:13',
            'modified' => '2019-07-25 16:00:13',
            'published' => 1,
            'priority' => 30,
        ],
    ];
}
