<?php
/**
 * Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Qobo\Search\Model\Table;

use ArrayObject;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\QueryInterface;
use Cake\Event\Event;
use Cake\Filesystem\Folder;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Webmozart\Assert\Assert;

/**
 * AppWidgets Model
 *
 * @method \Qobo\Search\Model\Entity\AppWidget get($primaryKey, $options = [])
 * @method \Qobo\Search\Model\Entity\AppWidget newEntity($data = null, array $options = [])
 * @method \Qobo\Search\Model\Entity\AppWidget[] newEntities(array $data, array $options = [])
 * @method \Qobo\Search\Model\Entity\AppWidget|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Qobo\Search\Model\Entity\AppWidget patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Qobo\Search\Model\Entity\AppWidget[] patchEntities($entities, array $data, array $options = [])
 * @method \Qobo\Search\Model\Entity\AppWidget findOrCreate($search, callable $callback = null, $options = [])
 * @method \Muffin\Trash\Model\Behavior\TrashBehavior trashAll($conditions)
 * @method \Muffin\Trash\Model\Behavior\TrashBehavior restoreTrash(\Cake\Datasource\EntityInterface $entity, $options = [])
 *
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class AppWidgetsTable extends Table
{
    /**
     * Widget type identifier.
     */
    const WIDGET_TYPE = 'app_widget';

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('qobo_search_app_widgets');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Muffin/Trash.Trash');

        $this->_saveAppWidgets();
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->uuid('id')
            ->allowEmpty('id', 'create');

        $validator
            ->requirePresence('type', 'create')
            ->notEmpty('type');

        $validator
            ->requirePresence('content', 'create')
            ->notEmpty('content');

        $validator
            ->requirePresence('name', 'create')
            ->notEmpty('name')
            ->add('name', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->dateTime('trashed')
            ->allowEmpty('trashed');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->isUnique(['name']));

        return $rules;
    }

    /**
     * Map content column to json custom type.
     *
     * @param \Cake\Database\Schema\TableSchema $schema Table schema object
     * @return \Cake\Database\Schema\TableSchema
     */
    protected function _initializeSchema(TableSchema $schema)
    {
        $schema->setColumnType('content', 'json');

        return $schema;
    }

    /**
     * Store application scope widgets into the database.
     *
     * @return void
     */
    protected function _saveAppWidgets(): void
    {
        $widgets = $this->_getAppWidgets();

        $found = [];
        foreach ($widgets as $widget) {
            $found[] = $widget['name'];

            // skip adding existing app widgets.
            $appWidgetEntity = $this->find('withTrashed')->where(['AppWidgets.name' => $widget['name']])->first();
            Assert::nullOrIsInstanceOf($appWidgetEntity, \Qobo\Search\Model\Entity\AppWidget::class);
            if (!empty($appWidgetEntity) && !empty($appWidgetEntity->get('trashed'))) {
                $this->restoreTrash($appWidgetEntity);
                continue;
            }

            $entity = $this->newEntity();
            $entity = $this->patchEntity($entity, $widget);
            $this->save($entity);
        }

        // soft delete non-existing app widgets
        $this->trashAll(! empty($found) ? ['AppWidgets.name NOT IN' => $found] : []);
    }

    /**
     * Get widgets defined in the Application level (src/Template/Plugin/Search/AppWidgets).
     *
     * @return mixed[]
     */
    protected function _getAppWidgets(): array
    {
        $result = [];

        $paths = App::path('Template');
        $tree = ['Plugin', 'Search', 'Widgets'];
        foreach ($paths as $path) {
            $path .= 'Element' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $tree) . DIRECTORY_SEPARATOR;

            $dir = new Folder($path);
            $files = $dir->find('.*\.ctp');
            if (empty($files)) {
                continue;
            }

            foreach ($files as $file) {
                $element = implode('/', $tree) . '/' . str_replace('.ctp', '', $file);

                $name = str_replace('.ctp', '', $file);
                $name = Inflector::humanize($name);
                $result[] = [
                    'name' => $name,
                    'type' => static::WIDGET_TYPE,
                    'content' => [
                        'model' => $this->getAlias(),
                        'path' => str_replace(APP, '', $path) . $file,
                        'element' => $element,
                    ],
                ];
            }
        }

        return $result;
    }

    /**
     * @param \Cake\Event\Event $event Event object
     * @param \Cake\Datasource\QueryInterface $query Query object
     * @param \ArrayObject $options Query options
     * @param bool $primary Primary Standalone Query flag
     * @return void
     */
    public function beforeFind(Event $event, QueryInterface $query, ArrayObject $options, bool $primary): void
    {
        $enable = Configure::read('Search.enabledWidgets');

        if (!empty($enable) && is_array($enable)) {
            $query->where(["name in" => $enable]);

            return;
        }

        $query->limit(0);
    }
}
