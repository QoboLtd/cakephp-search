<?php
namespace Search\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * SavedSearchesFixture
 *
 */
class SavedSearchesFixture extends TestFixture
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
        'type' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'user_id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'model' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'shared' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'content' => ['type' => 'text', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'created' => ['type' => 'datetime', 'null' => false],
        'modified' => ['type' => 'datetime', 'null' => false],
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
            'name' => 'Saved search result',
            'type' => 'result',
            'user_id' => '00000000-0000-0000-0000-000000000001',
            'model' => 'foo',
            'shared' => 'private',
            'content' => '{"saved":{"fields":"","aggregator":"OR","criteria":{"first_name":{"799":{"type":"string","operator":"contains","value":"jo"}}},"sort_by_field":"first_name","sort_by_order":"desc","limit":"10","display_columns":["first_name","last_name","street","city"],"result":[{"id":"f06f12a8-8e6b-460a-b595-195e6fcb3beb","first_name":"John","last_name":"Smith","street":"Oxford St. 34","city":"London"},{"id":"6829ace8-c520-47b9-a58e-b8f3ecf3fc7e","first_name":"Joe","last_name":"Doe","street":"Lincon St. 25","city":"New York"}]}}',
            'created' => '2016-07-01 10:39:23',
            'modified' => '2016-07-01 10:41:31',
            'trashed' => null,
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000002',
            'name' => 'Saved search criteria',
            'type' => 'criteria',
            'user_id' => '00000000-0000-0000-0000-000000000001',
            'model' => 'search_users',
            'shared' => 'private',
            'content' => '{"saved":{"criteria":{"name":[{"type":"string","operator":"contains","value":"user"}]},"aggregator":"OR","display_columns":["name"],"sort_by_field":"name","sort_by_order":"desc","limit":10}}',
            'created' => '2016-07-01 10:39:23',
            'modified' => '2016-07-01 10:41:31',
            'trashed' => null,
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000404',
            'name' => 'Deleted saved search criteria',
            'type' => 'criteria',
            'user_id' => '00000000-0000-0000-0000-000000000001',
            'model' => 'search_users',
            'shared' => 'private',
            'content' => '{"saved":{"criteria":{"name":[{"type":"string","operator":"contains","value":"user"}]},"aggregator":"OR","display_columns":["name"],"sort_by_field":"name","sort_by_order":"desc","limit":10}}',
            'created' => '2016-07-01 10:39:23',
            'modified' => '2016-07-01 10:41:31',
            'trashed' => '2016-07-01 10:41:31',
        ],
    ];
}
