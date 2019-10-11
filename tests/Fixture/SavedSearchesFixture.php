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
        'user_id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'model' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'content' => ['type' => 'text', 'length' => 4294967295, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'created' => ['type' => 'datetime', 'null' => false],
        'modified' => ['type' => 'datetime', 'null' => false],
        'trashed' => ['type' => 'datetime', 'null' => true],
        'conjunction' => ['type' => 'string', 'length' => 10, 'null' => false, 'default' => 'AND', 'comment' => '', 'precision' => null, 'fixed' => null],
        'criteria' => ['type' => 'text', 'length' => 4294967295, 'null' => false, 'default' => '\'\'', 'comment' => '', 'precision' => null],
        'fields' => ['type' => 'text', 'length' => 4294967295, 'null' => false, 'default' => '\'\'', 'comment' => '', 'precision' => null],
        'group_by' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => '', 'comment' => '', 'precision' => null, 'fixed' => null],
        'order_by_field' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => '', 'comment' => '', 'precision' => null, 'fixed' => null],
        'order_by_direction' => ['type' => 'string', 'length' => 10, 'null' => false, 'default' => 'DESC', 'comment' => '', 'precision' => null, 'fixed' => null],
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
            'user_id' => '00000000-0000-0000-0000-000000000001',
            'model' => 'Search.Dashboards',
            'content' => '{
                "saved": {
                    "criteria": {
                        "Dashboards.first_name": {
                            "799": {
                                "type": "string",
                                "operator": "contains",
                                "value": "jo"
                            }
                        }
                    },
                    "aggregator": "OR",
                    "display_columns": [
                        "first_name",
                        "last_name",
                        "street",
                        "city"
                    ],
                    "sort_by_field": "Dashboards.first_name",
                    "sort_by_order": "desc",
                    "limit": "10"
                }
            }',
            'conjunction' => 'OR',
            'criteria' => '{ "Dashboards.first_name": [{ "type": "string", "operator": "contains", "value": "jo" }] }',
            'fields' => '["first_name", "last_name", "street", "city"]',
            'group_by' => '',
            'order_by_field' => 'Dashboards.first_name',
            'order_by_direction' => 'desc',
            'created' => '2016-07-01 10:39:23',
            'modified' => '2016-07-01 10:41:31',
            'trashed' => null,
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000002',
            'name' => 'Saved search criteria',
            'user_id' => '00000000-0000-0000-0000-000000000002',
            'model' => 'Search.Dashboards',
            'content' => '{
                "saved": {
                    "criteria": {
                        "Dashboards.name": [
                            {
                                "type": "string",
                                "operator": "contains",
                                "value": "user"
                            }
                        ]
                    },
                    "aggregator": "OR",
                    "display_columns": [
                        "Dashboards.name"
                    ],
                    "sort_by_field": "Dashboards.name",
                    "sort_by_order": "desc",
                    "limit": 10
                },
                "latest": {
                    "criteria": {
                        "Dashboards.name": [
                            {
                                "type": "string",
                                "operator": "contains",
                                "value": "foo"
                            }
                        ]
                    },
                    "aggregator": "OR",
                    "display_columns": [
                        "Dashboards.name"
                    ],
                    "sort_by_field": "Dashboards.name",
                    "sort_by_order": "desc",
                    "limit": 10
                }
            }',
            'conjunction' => 'OR',
            'criteria' => '{ "Dashboards.name": [{ "type": "string", "operator": "contains", "value": "foo" }] }',
            'fields' => '["Dashboards.name"]',
            'group_by' => '',
            'order_by_field' => 'Dashboards.name',
            'order_by_direction' => 'desc',
            'created' => '2016-07-01 10:39:22',
            'modified' => '2016-07-01 10:41:31',
            'trashed' => null,
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000003',
            'name' => 'Articles saved criteria',
            'user_id' => '00000000-0000-0000-0000-000000000001',
            'model' => 'Articles',
            'content' => '{
                "saved": {
                    "criteria": {
                        "Articles.title": [
                            {
                                "type": "string",
                                "operator": "contains",
                                "value": "article"
                            }
                        ]
                    },
                    "aggregator": "OR",
                    "display_columns": [
                        "Articles.title"
                    ],
                    "sort_by_field": "title",
                    "sort_by_order": "desc"
                },
                "latest": {
                    "criteria": {
                        "Articles.title": [
                            {
                                "type": "string",
                                "operator": "contains",
                                "value": "article"
                            }
                        ]
                    },
                    "aggregator": "OR",
                    "display_columns": [
                        "Articles.title"
                    ],
                    "sort_by_field": "title",
                    "sort_by_order": "desc"
                }
            }',
            'conjunction' => 'OR',
            'criteria' => '{ "Articles.title": [{ "type": "string", "operator": "contains", "value": "article" }] }',
            'fields' => '["Articles.title"]',
            'group_by' => '',
            'order_by_field' => 'title',
            'order_by_direction' => 'desc',
            'created' => '2016-07-01 10:39:23',
            'modified' => '2016-07-01 10:41:31',
            'trashed' => null,
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000404',
            'name' => 'Deleted saved search criteria',
            'user_id' => '00000000-0000-0000-0000-000000000001',
            'model' => 'Search.Dashboards',
            'content' => '{
                "saved": {
                    "criteria": {
                        "Dashboards.name": [
                            {
                                "type": "string",
                                "operator": "contains",
                                "value": "user"
                            }
                        ]
                    },
                    "aggregator": "OR",
                    "display_columns": [
                        "Dashboards.name"
                    ],
                    "sort_by_field": "Dashboards.name",
                    "sort_by_order": "desc",
                    "limit": 10
                }
            }',
            'conjunction' => 'OR',
            'criteria' => '{ "Dashboards.name": [{ "type": "string", "operator": "contains", "value": "user" }] }',
            'fields' => '["Dashboards.name"]',
            'group_by' => '',
            'order_by_field' => 'Dashboards.name',
            'order_by_direction' => 'desc',
            'created' => '2016-07-01 10:39:23',
            'modified' => '2016-07-01 10:41:31',
            'trashed' => '2016-07-01 10:41:31',
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000005',
            'name' => 'Articles saved criteria with Group by',
            'user_id' => '00000000-0000-0000-0000-000000000001',
            'model' => 'Articles',
            'content' => '{
                "saved": {
                    "criteria": {
                        "Articles.title": [
                            {
                                "type": "string",
                                "operator": "contains",
                                "value": "article"
                            }
                        ]
                    },
                    "aggregator": "OR",
                    "display_columns": [
                        "Articles.title"
                    ],
                    "sort_by_field": "title",
                    "sort_by_order": "desc",
                    "group_by": "Articles.author_id"
                },
                "latest": {
                    "criteria": {
                        "Articles.title": [
                            {
                                "type": "string",
                                "operator": "contains",
                                "value": "article"
                            }
                        ]
                    },
                    "aggregator": "OR",
                    "display_columns": [
                        "Articles.title"
                    ],
                    "sort_by_field": "title",
                    "sort_by_order": "desc",
                    "group_by": "Articles.author_id"
                }
            }',
            'conjunction' => 'OR',
            'criteria' => '{ "Articles.title": [{ "type": "string", "operator": "contains", "value": "article" }] }',
            'fields' => '["Articles.author_id","COUNT(Articles.title)"]',
            'group_by' => 'Articles.author_id',
            'order_by_field' => 'title',
            'order_by_direction' => 'desc',
            'created' => '2016-07-01 10:39:23',
            'modified' => '2016-07-01 10:41:31',
            'trashed' => null,
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000006',
            'name' => 'Articles saved criteria',
            'user_id' => '00000000-0000-0000-0000-000000000001',
            'model' => 'Articles',
            'content' => '{"saved":{"criteria":{"Articles.title":[{"type":"string","operator":"contains","value":"article"}]},"aggregator":"OR","display_columns":["Articles.title","Articles.content"],"sort_by_field":"title","sort_by_order":"desc"},"latest":{"criteria":{"Articles.title":[{"type":"string","operator":"contains","value":"article"}]},"aggregator":"OR","display_columns":["Articles.title","Articles.content"],"sort_by_field":"title","sort_by_order":"desc"}}',
            'conjunction' => 'OR',
            'criteria' => '{ "Articles.title":[{ "type":"string", "operator":"contains", "value":"article" }] }',
            'fields' => '["Articles.title","Articles.author_id","Articles.content","Authors.name","Articles.status","Authors.type"]',
            'group_by' => '',
            'order_by_field' => 'title',
            'order_by_direction' => 'desc',
            'created' => '2016-07-01 10:39:23',
            'modified' => '2016-07-01 10:41:31',
            'trashed' => null,
        ],
    ];
}
