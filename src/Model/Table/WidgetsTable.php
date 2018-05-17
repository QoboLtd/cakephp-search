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
use Search\Model\Entity\Widget;
use Search\Widgets\WidgetFactory;

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

        $this->table('qobo_search_widgets');
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

        if (empty($event->result)) {
            return [];
        }

        // assembling all widgets in one
        $result = [];
        foreach ((array)$event->result as $widget) {
            if (empty($widget['data'])) {
                continue;
            }

            $instance = WidgetFactory::create($widget['type']);

            foreach ($widget['data'] as $data) {
                array_push($result, [
                    'type' => $widget['type'],
                    'title' => $instance->getTitle(),
                    'icon' => $instance->getIcon(),
                    'color' => $instance->getColor(),
                    'data' => $data
                ]);
            }
        }

        return $result;
    }

    /**
     * getWidgetOptions method
     *
     * @param \Search\Model\Entity\Widget $entity Widget entity
     * @param array $options Optional extra configuration
     *
     * @return array $options
     */
    public function getWidgetOptions(Widget $entity, array $options = [])
    {
        $widget = WidgetFactory::create($entity->get('widget_type'));

        $defaults = [
            'title' => $widget->getTitle(),
            'icon' => $widget->getIcon(),
            'color' => $widget->getColor()
        ];

        if ($entity->get('widget_options')) {
            return array_merge(
                $defaults,
                json_decode($entity->get('widget_options'), true)
            );
        }

        return array_merge($defaults, [
            'i' => (string)(empty($options['sequence']) ? 0 : $options['sequence']),
            'x' => ($entity->get('row') > 0) ? 6 : 0,
            'y' => empty($options['sequence']) ? 0 : $options['sequence'],
            'h' => 3,
            'w' => 6,
            'id' => $entity->get('id'),
            'type' => $entity->get('widget_type')
        ]);
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
