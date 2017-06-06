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
            'name' => 'Lorem ipsum dolor sit amet',
            'role_id' => '34cc7d4c-0b7e-4ff2-9e5d-4d48bcdb3d2e',
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
    ];
}
