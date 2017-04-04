<?php
namespace Search\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * WidgetsFixture
 *
 */
class WidgetsFixture extends TestFixture
{

    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'dashboard_id' => ['type' => 'string', 'length' => 36, 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'widget_id' => ['type' => 'string', 'length' => 36, 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'widget_type' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'widget_options' => ['type' => 'text', 'length' => 4294967295, 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null],
        'column' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'row' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'trashed' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        '_indexes' => [
            'dashboard_id' => ['type' => 'index', 'columns' => ['dashboard_id'], 'length' => []],
            'widget_id' => ['type' => 'index', 'columns' => ['widget_id'], 'length' => []],
            'widget_type' => ['type' => 'index', 'columns' => ['widget_type'], 'length' => []],
        ],
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
            'dashboard_id' => '00000000-0000-0000-0000-000000000100',
            'widget_id' => '00000000-0000-0000-0000-000000000010',
            'widget_type' => 'report',
            'widget_options' => 'Lorem Ipsum',
            'column' => 1,
            'row' => 1,
            'created' => '2016-10-19 12:08:59',
            'modified' => '2016-10-19 12:08:59',
            'trashed' => null
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000002',
            'dashboard_id' => '00000000-0000-0000-0000-000000000100',
            'widget_id' => '00000000-0000-0000-0000-000000000011',
            'widget_type' => 'saved_search',
            'widget_options' => 'Lorem Ipsum',
            'column' => 0,
            'row' => 1,
            'created' => '2016-10-19 12:08:59',
            'modified' => '2016-10-19 12:08:59',
            'trashed' => null
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000003',
            'dashboard_id' => '00000000-0000-0000-0000-000000000100',
            'widget_id' => '00000000-0000-0000-0000-000000000001',
            'widget_type' => 'app',
            'widget_options' => 'Lorem Ipsum',
            'column' => 0,
            'row' => 2,
            'created' => '2016-10-19 12:08:59',
            'modified' => '2016-10-19 12:08:59',
            'trashed' => null
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000004',
            'dashboard_id' => '00000000-0000-0000-0000-000000000100',
            'widget_id' => '00000000-0000-0000-0000-000000000002',
            'widget_type' => 'app',
            'widget_options' => 'Lorem Ipsum',
            'column' => 1,
            'row' => 2,
            'created' => '2016-10-19 12:08:59',
            'modified' => '2016-10-19 12:08:59',
            'trashed' => null
        ],
    ];
}
