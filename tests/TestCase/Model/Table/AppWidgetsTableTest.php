<?php
namespace Search\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Search\Model\Table\AppWidgetsTable Test Case
 */
class AppWidgetsTableTest extends TestCase
{
    public $fixtures = ['plugin.search.app_widgets'];

    private $table;

    public function setUp(): void
    {
        parent::setUp();

        $this->table = TableRegistry::getTableLocator()->get('Search.AppWidgets');
    }

    public function tearDown(): void
    {
        unset($this->table);

        parent::tearDown();
    }

    public function testInitialize(): void
    {
        $result = $this->table->find('list')->toArray();

        $this->assertContains('Another Test Widget', $result);
        $this->assertContains('Hello World', $result);
    }

    public function testSaveAppWidgets(): void
    {
        $widgetId = '00000000-0000-0000-0000-000000000001';

        // trash specific widget
        $this->table->delete($this->table->get($widgetId));

        $this->assertCount(1, $this->table->find(), sprintf('Widget with id %s was not trashed', $widgetId));

        TableRegistry::getTableLocator()->clear();
        TableRegistry::getTableLocator()->get('Search.AppWidgets');

        // validate that the widget was restored
        $this->assertCount(2, $this->table->find(), sprintf('Widget with id %s was not restored', $widgetId));

        $widget = $this->table->find()
            ->where(['name' => 'Hello World'])
            ->first();

        $this->assertSame($widgetId, $widget->get('id'), 'Widget id does not match, the widget was re-created');
    }

    public function testValidationDefault(): void
    {
        $validator = new \Cake\Validation\Validator();
        $result = $this->table->validationDefault($validator);

        $this->assertInstanceOf('\Cake\Validation\Validator', $result);
    }

    public function testBuildRules(): void
    {
        $rules = new \Cake\ORM\RulesChecker();
        $result = $this->table->buildRules($rules);

        $this->assertInstanceOf('\Cake\ORM\RulesChecker', $result);
    }
}
