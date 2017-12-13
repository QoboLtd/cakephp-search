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
use Search\Event\EventName;

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
     * getWidgets method.
     *
     * @return array $result containing all widgets
     */
    public function getWidgets()
    {
        // get widgets through Event broadcast
        $event = new Event((string)EventName::MODEL_DASHBOARDS_GET_WIDGETS(), $this);
        $this->eventManager()->dispatch($event);

        $widgets = !empty($event->result) ? $event->result : [];

        if (empty($widgets)) {
            return [];
        }

        //assembling all widgets in one
        $result = [];
        foreach ($widgets as $k => $widgetsGroup) {
            if (empty($widgetsGroup['data'])) {
                continue;
            }

            foreach ($widgetsGroup['data'] as $widget) {
                array_push($result, [
                    'type' => $widgetsGroup['type'],
                    'data' => $widget
                ]);
            }
        }

        return $result;
    }

    /**
     * getWidgetPosition method
     *
     * @param mixed $widget array
     * @param array $options with extra configs
     *
     * @return array $options
     */
    public function getWidgetPosition($widget = null, $options = [])
    {
        $result = [];

        if (!empty($widget['widget_options'])) {
            $result = json_decode($widget['widget_options'], true);

            return $result;
        }

        $sequence = !empty($options['sequence']) ? $options['sequence'] : 0;

        $result['i'] = "$sequence";
        $result['x'] = ($widget['row'] > 0) ? 6 : 0;
        $result['y'] = $sequence;
        $result['h'] = 3;
        $result['w'] = 6;
        $result['id'] = $widget['id'];
        $result['type'] = !empty($widget['widget_type']) ? $widget['widget_type'] : $widget['data']['type'];

        return $result;
    }

    /**
     * Save Dashboard Widgets
     *
     * @param uuid $dashboardId of the instance
     * @param array $widgets of the dashboard
     *
     * @return bool $result of the save operation.
     */
    public function saveDashboardWidgets($dashboardId, $widgets = [])
    {
        $result = false;

        if (empty($widgets)) {
            return $result;
        }

        foreach ($widgets as $k => $item) {
            $widget = [
                'dashboard_id' => $dashboardId,
                'widget_id' => $item['id'],
                'widget_type' => $item['type'],
                'widget_options' => json_encode($item),
                'row' => 0,
                'column' => 0,
            ];

            $entity = $this->newEntity();
            $entity = $this->patchEntity($entity, $widget);

            if ($this->save($entity)) {
                $result = true;
            }
        }

        return $result;
    }
}
