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
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.CakeDC/Users.users',
        'plugin.search.saved_searches'
    ];

    private $SavedSearches;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->SavedSearches = TableRegistry::getTableLocator()->get('Search.SavedSearches');
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
            'user_id' => '00000000-0000-0000-0000-000000000002'
        ];

        $entity = $this->SavedSearches->newEntity($data);
        $saved = $this->SavedSearches->save($entity);

        $this->assertInstanceOf(SavedSearch::class, $saved);
    }

    public function testSaveWithInvalidData(): void
    {
        $entity = $this->SavedSearches->newEntity([]);

        $saved = (bool)$this->SavedSearches->save($entity);
        $this->assertFalse($saved);

        $expected = [
            'name' => [
                '_required' => 'This field is required'
            ],
            'model' => [
                '_required' => 'This field is required'
            ],
            'user_id' => [
                '_required' => 'This field is required'
            ]
        ];

        $this->assertEquals($expected, $entity->getErrors());
    }

    public function testIsEditable(): void
    {
        $data = [
            'name' => 'withName',
            'model' => 'Foobar',
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
