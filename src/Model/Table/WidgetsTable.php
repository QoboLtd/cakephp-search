<?php
namespace Search\Model\Table;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Filesystem\Folder;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Cake\Validation\Validator;

/**
 * Widgets Model
 *
 * @property \Cake\ORM\Association\BelongsTo $Dashboards
 * @property \Cake\ORM\Association\BelongsTo $Widgets
 * @property \Cake\ORM\Association\HasMany $Widgets
 *
 * @method \Search\Model\Entity\Widget get($primaryKey, $options = [])
 * @method \Search\Model\Entity\Widget newEntity($data = null, array $options = [])
 * @method \Search\Model\Entity\Widget[] newEntities(array $data, array $options = [])
 * @method \Search\Model\Entity\Widget|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Search\Model\Entity\Widget patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Search\Model\Entity\Widget[] patchEntities($entities, array $data, array $options = [])
 * @method \Search\Model\Entity\Widget findOrCreate($search, callable $callback = null)
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class WidgetsTable extends Table
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

        $this->table('widgets');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Muffin/Trash.Trash');

        $this->belongsTo('Dashboards', [
            'foreignKey' => 'dashboard_id',
            'joinType' => 'INNER',
            'className' => 'Search.Dashboards'
        ]);
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
            ->requirePresence('dashboard_id', 'create')
            ->notEmpty('dashboard_id');

        $validator
            ->requirePresence('widget_id', 'create')
            ->notEmpty('widget_id');

        $validator
            ->requirePresence('widget_type', 'create')
            ->notEmpty('widget_type');

        $validator
            ->integer('column')
            ->requirePresence('column', 'create')
            ->notEmpty('column');

        $validator
            ->integer('row')
            ->requirePresence('row', 'create')
            ->notEmpty('row');

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
        $rules->add($rules->existsIn(['dashboard_id'], 'Dashboards'));

        return $rules;
    }

    /**
     * getWidgets method
     * @return array $result containing all widgets
     */
    public function getWidgets()
    {
        $result = $widgets = [];

        $widgets[] = ['type' => 'app', 'data' => $this->_getAppWidgets()];

        // $dashboardsTable = TableRegistry::get('Search.Dashboards');
        $savedSearchesTable = TableRegistry::get('Search.SavedSearches');

        $allSavedSearches = $savedSearchesTable->find('all')
            ->where(['SavedSearches.name IS NOT' => null])
            ->order(['SavedSearches.model', 'SavedSearches.name']);

        $savedSearches = $savedSearchesTable
                        ->find()
                        ->select()
                        ->where(['SavedSearches.name IS NOT' => null])
                        ->hydrate(false)
                        ->indexBy('id')
                        ->toArray();

        foreach ($savedSearches as $id => $savedSearch) {
            $table = TableRegistry::get($savedSearch['model']);
            if (method_exists($table, 'moduleAlias')) {
                $savedSearches[$id]['model'] = $table->moduleAlias();
            }
        }

        $widgets[] = ['type' => 'saved_search', 'data' => $savedSearches];
        $event = new Event('Search.Report.getReports', $this);
        $this->eventManager()->dispatch($event);

        if (!empty($event->result)) {
            $data = [];
            foreach ($event->result as $model => $reports) {
                foreach ($reports as $reportSlug => $reportInfo) {
                    $data[$reportInfo['id']] = $reportInfo;
                }
            }
            if (!empty($data)) {
                $widgets[] = [ 'type' => 'report', 'data' => $data ];
            }
        }

        //assembling all widgets in one
        if (!empty($widgets)) {
            foreach ($widgets as $k => $widgetsGroup) {
                if (!empty($widgetsGroup['data'])) {
                    foreach ($widgetsGroup['data'] as $widget) {
                        array_push($result, [
                            'type' => $widgetsGroup['type'],
                            'data' => $widget
                        ]);
                    }
                }
            }
        }

        return $result;
    }

    protected function _getAppWidgets()
    {
        $table = TableRegistry::get('Search.AppWidgets');

        $query = $table->find('all');

        $entities = $query->toArray();

        $result = [];
        foreach ($entities as $entity) {
            $result[] = [
                'id' => $entity->id,
                'model' => $entity->content['model'],
                'name' => $entity->name,
                'path' => $entity->content['path']
            ];
        }

        return $result;
    }
}
