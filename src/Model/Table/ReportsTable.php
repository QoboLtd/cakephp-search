<?php
namespace Search\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Search\Widgets\WidgetFactory;
use Search\Widgets\ReportWidget;

/**
 * Reports Model
 *
 * @property \Cake\ORM\Association\BelongsTo $Users
 *
 * @method \Search\Model\Entity\Report get($primaryKey, $options = [])
 * @method \Search\Model\Entity\Report newEntity($data = null, array $options = [])
 * @method \Search\Model\Entity\Report[] newEntities(array $data, array $options = [])
 * @method \Search\Model\Entity\Report|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Search\Model\Entity\Report patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Search\Model\Entity\Report[] patchEntities($entities, array $data, array $options = [])
 * @method \Search\Model\Entity\Report findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ReportsTable extends Table
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

        $this->setTable('reports');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
            'className' => 'Search.Users'
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
            ->allowEmpty('name');

        $validator
            ->requirePresence('type', 'create')
            ->notEmpty('type');

        $validator
            ->requirePresence('model', 'create')
            ->notEmpty('model');

        $validator
            ->requirePresence('content', 'create')
            ->notEmpty('content');

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
        $rules->add($rules->existsIn(['user_id'], 'Users'));

        return $rules;
    }

    /**
     * beforeSave callback
     *
     * @param \Cake\Event\Event $event                      CakePHP event
     * @param \Cake\Datasource\EntityInterface $entity      Entity data
     * @param array $options                                array with options
     * @return bool                                         true on success
     */
    public function beforeSave(Event $event, EntityInterface $entity, $options)
    {
        if (!$this->_validateContent($entity->content)) {
            return false;
        }

        return true;
    }

    /**
     *  getActiveReports() method
     *
     *  returns a list of active reports in the system
     *
     * @return array    list of active reports
     */
    public function getActiveReports()
    {
        $query = $this->find('all', ['conditions' => ['is_active' => true]]);
        $result = [];
        foreach ($query as $row) {
            $result[$row->model][Inflector::tableize($row->name)] = [
                'id' => $row->id,
                'model' => $row->model,
                'name' => $row->name,
                'renderAs' => $row->type,
                'widget_type' => 'report',
                'query' => $row->content,
                'columns' => $row->columns,
                'label' => $row->chart_label,
                'value' => $row->chart_value,
                'max' => $row->chart_max,
                'x_axis' => $row->x_axis,
                'y_axis' => $row->y_axis,
            ];
        }

        return $result;
    }
    
    /**
     *  getChartReportTypes() method
     *
     * @return array    list - key and value - of available charts
     */
    public function getChartReportTypes()
    {
        $chartTypes = WidgetFactory::getChartReportTypes();        
        asort($chartTypes);

        return $chartTypes;
    }

    /**
     *  getChartFields() method
     *
     * @param string $type      type of chart report
     * @return array            list of required fields
     */
    public function getChartFields($type, $associative=false)
    {
        $result = [];
        $report = new ReportWidget();
        $widget = $report->createReportWidget($type);
        if (is_object($widget)) {
            $chartFields = $widget->requiredFields;
            foreach ($chartFields as $field) {
                if (!in_array($field, $widget->commonFields)) {
                    if ($associative) {
                        $result[$field] = '';
                    } else {
                        array_push($result, $field);
                    }    
                }
            }
        }
        return $result;
    }

    /**
     *  _validateContent() method
     *
     * @param string $content   query to check
     * @return bool             true in case of correct select query and false in case of any non-select operator presented
     */
    private function _validateContent($content)
    {
        if (preg_match('/(insert|update|delete|truncate|drop|create)\s/', $content)) {
            return false;
        }

        return true;
    }
}
