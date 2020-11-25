<?php
namespace Qobo\Search\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * DashboardsFixture
 *
 */
class DashboardsFixture extends TestFixture
{
    public $table = 'qobo_search_dashboards';
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'uuid', 'null' => false ],
        'name' => ['type' => 'string', 'length' => 255, 'null' => true ],
        'group_id' => ['type' => 'uuid', 'null' => true ],
        'created' => ['type' => 'datetime', 'null' => false ],
        'modified' => ['type' => 'datetime', 'null' => false ],
        'trashed' => ['type' => 'datetime', 'null' => true ],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ]
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
            'group_id' => '00000000-0000-0000-0000-000000000001',
            'created' => '2016-04-27 08:21:53',
            'modified' => '2016-04-27 08:21:53',
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000002',
            'name' => 'Dashboard without group 1',
            'group_id' => null,
            'created' => '2016-04-27 08:21:54',
            'modified' => '2016-04-27 08:21:54',
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000022',
            'name' => 'Dashboard without group 2',
            'group_id' => null,
            'created' => '2016-05-27 08:21:54',
            'modified' => '2016-05-27 08:21:54',
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000003',
            'name' => 'Everyone Dashboard',
            'group_id' => '00000000-0000-0000-0000-000000000003',
            'created' => '2016-04-27 08:21:56',
            'modified' => '2016-04-27 08:21:56',
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000004',
            'name' => 'Example Dashboard',
            'group_id' => '00000000-0000-0000-0000-000000000002',
            'created' => '2016-04-27 08:21:57',
            'modified' => '2016-04-27 08:21:57',
        ],
    ];
}
