<?php
namespace Search\Utility;

use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Search\Utility\Options;

/**
 * Search\Utility\Options Test Case
 */
class OptionsTest extends TestCase
{
    public $fixtures = [
        'plugin.search.dashboards'
    ];

    public function testGetSearchableAssociations()
    {
        $result = Options::getSearchableAssociations();
        $this->assertEquals($result, ['manyToOne']);
    }

    public function testGetSortByOrders()
    {
        $result = Options::getSortByOrders();
        $this->assertEquals($result, ['asc' => 'Ascending', 'desc' => 'Descending']);
    }

    public function testGetAggregators()
    {
        $result = Options::getAggregators();
        $this->assertEquals($result, ['AND' => 'Match all filters', 'OR' => 'Match any filter']);
    }

    public function testGetBasicSearchFieldTypes()
    {
        $result = Options::getBasicSearchFieldTypes();
        $this->assertEquals($result, ['string', 'text', 'textarea', 'related', 'email', 'url', 'phone']);
    }

    public function testGet()
    {
        $result = Options::get();
        $this->assertEquals($result, [
            'sortByOrder' => ['asc' => 'Ascending', 'desc' => 'Descending'],
            'aggregators' => ['AND' => 'Match all filters', 'OR' => 'Match any filter']
        ]);
    }

    public function testGetListingFields()
    {
        $model = 'Dashboards';

        $expected = [$model . '.name', $model . '.modified', $model . '.created'];

        $result = Options::getListingFields(TableRegistry::get($model));
        $this->assertEquals($result, $expected);
    }

    public function testGetListingFieldsFromEvent()
    {
        $model = 'Dashboards';
        $expected = [$model . '.foobar'];

        EventManager::instance()->on('Search.Model.Search.displayFields', function ($event, $table) use ($expected) {
            return $expected;
        });

        $result = Options::getListingFields(TableRegistry::get($model));
        $this->assertEquals($result, $expected);
    }

    public function testGetListingFieldsDatabaseColumns()
    {
        $model = 'Dashboards';

        $table = TableRegistry::get($model);
        $table->setDisplayField('virtual_field');

        $result = Options::getListingFields($table);
        $this->assertEquals($result, [$model . '.modified', $model . '.created']);
    }
}
