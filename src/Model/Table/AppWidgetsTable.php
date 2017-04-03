<?php
namespace Search\Model\Table;

use Cake\Core\App;
use Cake\Database\Schema\Table as Schema;
use Cake\Filesystem\Folder;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;

/**
 * AppWidgets Model
 *
 * @method \Search\Model\Entity\AppWidget get($primaryKey, $options = [])
 * @method \Search\Model\Entity\AppWidget newEntity($data = null, array $options = [])
 * @method \Search\Model\Entity\AppWidget[] newEntities(array $data, array $options = [])
 * @method \Search\Model\Entity\AppWidget|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Search\Model\Entity\AppWidget patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Search\Model\Entity\AppWidget[] patchEntities($entities, array $data, array $options = [])
 * @method \Search\Model\Entity\AppWidget findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class AppWidgetsTable extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('app_widgets');
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

    protected function _initializeSchema(Schema $schema)
    {
        $schema->columnType('content', 'json');

        return $schema;
    }

    protected function _saveAppWidgets()
    {
        $widgets = $this->_getAppWidgets();

        $found = [];
        foreach ($widgets as $widget) {
            $found[] = $widget['name'];

            // skip adding existing app widgets
            if ($this->exists(['AppWidgets.name' => $widget['name']])) {
                continue;
            }

            $entity = $this->newEntity();
            $entity = $this->patchEntity($entity, $widget);
            $this->save($entity);
        }

        // soft delete non-existing app widgets
        $this->trashAll(['AppWidgets.name NOT IN' => $found]);
    }

    /**
     * Get widgets defined in the Application level (src/Template/Plugin/Search/AppWidgets).
     *
     * @return array
     */
    protected function _getAppWidgets()
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
                    'type' => 'app_widget',
                    'content' => [
                        'model' => $this->alias(),
                        'path' => $path . $file,
                        'element' => $element
                    ]
                ];
            }
        }

        return $result;
    }
}
