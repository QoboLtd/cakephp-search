<?php
namespace Search\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AppWidgetsFixture
 *
 */
class AppWidgetsFixture extends TestFixture
{

    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'type' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'content' => ['type' => 'text', 'length' => 4294967295, 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'name' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null, 'fixed' => null],
        'trashed' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
            'UNIQUE_NAME' => ['type' => 'unique', 'columns' => ['name'], 'length' => []],
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
            'type' => 'app_widget',
            'content' => '{"model":"AppWidgets","path":"src\/Template\/Element\/Plugin\/Search\/Widgets\/hello_world.ctp","element":"Plugin\/Search\/Widgets\/hello_world"}',
            'created' => '2017-04-03 11:49:00',
            'modified' => '2017-04-03 11:49:00',
            'name' => 'Hello World',
            'trashed' => null
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000002',
            'type' => 'app_widget',
            'content' => '{"model":"AppWidgets","path":"src\/Template\/Element\/Plugin\/Search\/Widgets\/foobar.ctp","element":"Plugin\/Search\/Widgets\/foobar"}',
            'created' => '2017-04-03 11:49:00',
            'modified' => '2017-04-03 11:49:00',
            'name' => 'Foobar',
            'trashed' => '2017-04-03 11:49:00'
        ],
    ];
}
