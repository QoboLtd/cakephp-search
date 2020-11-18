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

use Cake\Core\Configure;
use Cake\Datasource\QueryInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Groups\Model\Table\GroupsTable;

/**
 * Dashboards Model
 *
 * @property GroupsTable $Groups
 * @property \Qobo\Search\Model\Table\SavedSearches $SavedSearches
 */
class DashboardsTable extends Table
{

    /**
     * Initialize method
     *
     * @param mixed[] $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('qobo_search_dashboards');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Muffin/Trash.Trash');

        $this->belongsTo('Groups.Groups', [
            'foreignKey' => 'group_id',
        ]);

        $this->hasMany('Widgets', [
            'foreignKey' => 'dashboard_id',
            'className' => 'Qobo/Search.Widgets',
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
            ->requirePresence('name', 'create')
            ->notEmpty('name');

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
        $rules->add($rules->existsIn(['group_id'], 'Groups'));

        return $rules;
    }

    /**
     * Get specified user accessible dashboards.
     *
     * @param  mixed[] $user user details
     * @return \Cake\Datasource\QueryInterface
     */
    public function getUserDashboards(array $user): QueryInterface
    {
        // get all dashboards
        $query = $this->find('all')->order('name');

        // return all dashboards if current user is superuser
        if (isset($user['is_superuser']) && $user['is_superuser']) {
            return $query;
        }

        $groups = $this->Groups->find()->matching('Users', function ($q) use ($user) {
            return $q->where(['Users.Id' => $user['id']]);
        })->all()->toArray();

        if (count($groups) === 0) {
            return $query->where('Dashboards.group_id IS NULL');
        }

        // get group(s) dashboards
        $query->where(['OR' => [
            ['Dashboards.group_id IN' => array_keys($groups)],
            ['Dashboards.group_id IS NULL'],
        ]]);

        return $query;
    }
}
