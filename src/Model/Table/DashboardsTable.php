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

use Cake\Core\Configure;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * Dashboards Model
 *
 * @property \Cake\ORM\Association\BelongsTo $Roles
 * @property \Cake\ORM\Association\BelongsToMany $SavedSearches
 */
class DashboardsTable extends Table
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

        $this->table('dashboards');
        $this->displayField('name');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Muffin/Trash.Trash');

        $this->belongsTo('RolesCapabilities.Roles', [
            'foreignKey' => 'role_id'
        ]);

        $this->hasMany('Widgets', [
            'foreignKey' => 'dashboard_id',
            'className' => 'Search.Widgets'
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
        $rules->add($rules->existsIn(['role_id'], 'Roles'));

        return $rules;
    }

    /**
     * Get specified user accessible dashboards.
     *
     * @param  array $user user details
     * @return \Cake\ORM\Query
     */
    public function getUserDashboards(array $user)
    {
        // get all dashboards
        $query = $this->find('all')->order('name');

        // return all dashboards if current user is superuser
        if (isset($user['is_superuser']) && $user['is_superuser']) {
            return $query;
        }

        $roles = [];
        $groups = $this->Roles->Groups->getUserGroups($user['id']);
        // get group(s) roles
        if (!empty($groups)) {
            $roles = $this->Roles->Capabilities->getGroupsRoles($groups);
        }

        if (empty($roles)) {
            // get all dashboards not assigned to any role
            $query->where(['Dashboards.role_id IS NULL']);

            return $query;
        }

        // return all dashboards for Admins
        if (in_array(Configure::read('RolesCapabilities.Roles.Admin.name'), $roles)) {
            return $query;
        }

        // get role(s) dashboards
        $query->where(['OR' => [
            ['Dashboards.role_id IN' => array_keys($roles)],
            ['Dashboards.role_id IS NULL']
        ]]);

        return $query;
    }
}
