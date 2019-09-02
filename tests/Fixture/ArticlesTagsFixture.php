<?php
namespace Search\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ArticlesTagsFixture extends TestFixture
{

    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'article_id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'tag_id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
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
            'article_id' => '00000000-0000-0000-0000-000000000001',
            'tag_id' => '00000000-0000-0000-0000-000000000001',
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000002',
            'article_id' => '00000000-0000-0000-0000-000000000001',
            'tag_id' => '00000000-0000-0000-0000-000000000002',
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000003',
            'article_id' => '00000000-0000-0000-0000-000000000002',
            'tag_id' => '00000000-0000-0000-0000-000000000002',
        ],
    ];
}
