<?php
namespace Search\Test\TestCase\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Validation\Validator;
use Search\Model\Entity\SavedSearch;

/**
 * Search\Model\Table\SavedSearchesTable Test Case
 */
class SavedSearchesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Search\Model\Table\SavedSearchesTable
     */
    public $SavedSearches;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.search.saved_searches'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $config = TableRegistry::exists('SavedSearches') ? [] : ['className' => 'Search\Model\Table\SavedSearchesTable'];
        $this->SavedSearches = TableRegistry::get('SavedSearches', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->SavedSearches);

        parent::tearDown();
    }

    public function testValidationDefault()
    {
        $validator = new Validator();
        $result = $this->SavedSearches->validationDefault($validator);

        $this->assertInstanceOf(Validator::class, $result);
    }

    public function testBuildRules()
    {
        $rules = new RulesChecker();
        $result = $this->SavedSearches->buildRules($rules);

        $this->assertInstanceOf(RulesChecker::class, $result);
    }

    public function testIsEditable()
    {
        $entity = $this->SavedSearches->get('00000000-0000-0000-0000-000000000001');
        $result = $this->SavedSearches->isEditable($entity);

        $this->assertTrue($result);
    }

    public function dataProviderGetBasicSearchCriteria()
    {
        return [
            [['query' => 'SELECT id,created FROM dashboards LIMIT 2', 'table' => 'Dashboards']],
        ];
    }

    public function testGetSavedSearchesFindAll()
    {
        $resultset = $this->SavedSearches->getSavedSearches();
        $this->assertInternalType('array', $resultset);
        $this->assertInstanceOf(SavedSearch::class, current($resultset));
    }

    public function testGetSavedSearchesByUser()
    {
        $records = $this->fixtureManager->loaded()['plugin.search.saved_searches']->records;
        $userId = current($records)['user_id'];
        $resultset = $this->SavedSearches->getSavedSearches([$userId]);
        $this->assertInternalType('array', $resultset);
        $this->assertInstanceOf(SavedSearch::class, current($resultset));

        foreach ($resultset as $entity) {
            $this->assertEquals($userId, $entity->user_id);
        }
    }

    public function testGetSavedSearchesByModel()
    {
        $records = $this->fixtureManager->loaded()['plugin.search.saved_searches']->records;
        $model = current($records)['model'];
        $resultset = $this->SavedSearches->getSavedSearches([], [$model]);
        $this->assertInternalType('array', $resultset);
        $this->assertInstanceOf(SavedSearch::class, current($resultset));

        foreach ($resultset as $entity) {
            $this->assertEquals($model, $entity->model);
        }
    }
}
