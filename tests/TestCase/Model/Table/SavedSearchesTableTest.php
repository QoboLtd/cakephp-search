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

        $config = TableRegistry::exists('Search.SavedSearches') ? [] : ['className' => 'Search\Model\Table\SavedSearchesTable'];
        /**
         * @var \Search\Model\Table\SavedSearchesTable $table
         */
        $table = TableRegistry::get('Search.SavedSearches', $config);
        $this->SavedSearches = $table;
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

    public function testValidationDefault(): void
    {
        $validator = new Validator();
        $result = $this->SavedSearches->validationDefault($validator);

        $this->assertInstanceOf(Validator::class, $result);
    }

    public function testBuildRules(): void
    {
        $rules = new RulesChecker();
        $result = $this->SavedSearches->buildRules($rules);

        $this->assertInstanceOf(RulesChecker::class, $result);
    }

    public function testIsEditable(): void
    {
        /**
         * @var \Search\Model\Entity\SavedSearch
         */
        $entity = $this->SavedSearches->get('00000000-0000-0000-0000-000000000001');
        $result = $this->SavedSearches->isEditable($entity);

        $this->assertTrue($result);
    }

    /**
     * @return mixed[]
     */
    public function dataProviderGetBasicSearchCriteria() : array
    {
        return [
            [['query' => 'SELECT id,created FROM dashboards LIMIT 2', 'table' => 'Dashboards']],
        ];
    }

    public function testGetSavedSearchesFindAll(): void
    {
        $resultset = $this->SavedSearches->getSavedSearches();
        $this->assertInternalType('array', $resultset);
        $this->assertInstanceOf(SavedSearch::class, current($resultset));
    }

    public function testGetSavedSearchesByUser(): void
    {
        /**
         * @var \Cake\Datasource\EntityInterface
         */
        $entity = $this->SavedSearches->find()->firstOrFail();
        $resultset = $this->SavedSearches->getSavedSearches([$entity->get('user_id')]);
        $this->assertInternalType('array', $resultset);
        $this->assertInstanceOf(SavedSearch::class, current($resultset));

        foreach ($resultset as $record) {
            $this->assertEquals($entity->get('user_id'), $record->user_id);
        }
    }

    public function testGetSavedSearchesByModel(): void
    {
        /**
         * @var \Cake\Datasource\EntityInterface
         */
        $entity = $this->SavedSearches->find()->firstOrFail();
        $resultset = $this->SavedSearches->getSavedSearches([], [$entity->get('model')]);
        $this->assertInternalType('array', $resultset);
        $this->assertInstanceOf(SavedSearch::class, current($resultset));

        foreach ($resultset as $record) {
            $this->assertEquals($entity->get('model'), $record->model);
        }
    }
}
