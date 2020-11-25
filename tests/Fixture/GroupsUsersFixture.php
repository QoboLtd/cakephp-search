<?php
namespace Qobo\Search\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * GroupsUsersFixture
 */
class GroupsUsersFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'uuid', 'null' => false ],
        'group_id' => ['type' => 'string', 'length' => 36, 'null' => false ],
        'user_id' => ['type' => 'string', 'length' => 36, 'null' => false ],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
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
            'group_id' => '00000000-0000-0000-0000-000000000001',
            'user_id' => '00000000-0000-0000-0000-000000000001',
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000002',
            'group_id' => '00000000-0000-0000-0000-000000000002',
            'user_id' => '00000000-0000-0000-0000-000000000002',
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000013',
            'group_id' => '00000000-0000-0000-0000-000000000003',
            'user_id' => '00000000-0000-0000-0000-000000000001',
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000023',
            'group_id' => '00000000-0000-0000-0000-000000000003',
            'user_id' => '00000000-0000-0000-0000-000000000002',
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000033',
            'group_id' => '00000000-0000-0000-0000-000000000003',
            'user_id' => '00000000-0000-0000-0000-000000000003',
        ],
    ];
}
