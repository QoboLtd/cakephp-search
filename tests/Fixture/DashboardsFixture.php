<?php
namespace Search\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * DashboardsFixture
 *
 */
class DashboardsFixture extends TestFixture
{

    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'name' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'role_id' => ['type' => 'uuid', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'trashed' => ['type' => 'datetime', 'null' => true],
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
            'name' => 'Admins Dashboard',
            'role_id' => '00000000-0000-0000-0000-000000000001',
            'created' => '2016-04-27 08:21:53',
            'modified' => '2016-04-27 08:21:53',
            'trashed' => null,
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000002',
            'name' => 'Lorem ipsum dolor sit amet',
            'role_id' => null,
            'created' => '2016-04-27 08:21:54',
            'modified' => '2016-04-27 08:21:54',
            'trashed' => null,
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000003',
            'name' => 'Everyone Dashboard',
            'role_id' => '00000000-0000-0000-0000-000000000002',
            'created' => '2016-04-27 08:21:56',
            'modified' => '2016-04-27 08:21:56',
            'trashed' => null,
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000004',
            'name' => 'Example Dashboard',
            'role_id' => '00000000-0000-0000-0000-000000000009',
            'created' => '2016-04-27 08:21:57',
            'modified' => '2016-04-27 08:21:57',
            'trashed' => null,
        ],
    ];
}
