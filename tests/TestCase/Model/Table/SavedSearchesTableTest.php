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
        'plugin.CakeDC/Users.users',
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

    public function testSave(): void
    {
        $data = [
            'name' => 'withName',
            'model' => 'Foobar',
            'content' => [
                'saved' => 'foo',
                'latest' => 'bar'
            ],
            'user_id' => '00000000-0000-0000-0000-000000000001'
        ];

        $entity = $this->SavedSearches->newEntity($data);

        $saved = $this->SavedSearches->save($entity);

        $this->assertInstanceOf(SavedSearch::class, $saved);
    }

    public function testSaveWithInvalidDataStructure(): void
    {
        $data = [
            'name' => 'withName',
            'model' => 'Foobar',
            'content' => 'foo', // invalid content strucutre
            'user_id' => '00000000-0000-0000-0000-000000000001'
        ];

        $entity = $this->SavedSearches->newEntity($data);

        $saved = (bool)$this->SavedSearches->save($entity);
        $this->assertFalse($saved);

        $expected = [
            'content' => [
                'isArray' => 'The provided value is invalid',
                'validateSaved' => 'Missing required key "saved"',
                'validateLatest' => 'Missing required key "latest"'
            ]
        ];

        $this->assertEquals($expected, $entity->getErrors());
    }

    public function testIsEditable(): void
    {
        $data = [
            'name' => 'withName',
            'model' => 'Foobar',
            'content' => [
                'saved' => 'foo',
                'latest' => 'bar'
            ],
            'user_id' => '00000000-0000-0000-0000-000000000001'
        ];

        $entity = $this->SavedSearches->newEntity($data);

        $this->assertTrue($entity->get('is_editable'));

        // reset name
        $data['name'] = null;
        $entity = $this->SavedSearches->newEntity($data);

        $this->assertFalse($entity->get('is_editable'));
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
}
