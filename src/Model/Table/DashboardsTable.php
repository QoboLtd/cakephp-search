<?php
namespace Search\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Search\Model\Entity\Dashboard;

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

        $this->belongsTo('Roles', [
            'foreignKey' => 'role_id',
            'className' => 'Search.Roles'
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
     * Prepare saved searches to be passed to the View.
     *
     * @param  array  $savedSearches Dashboard's saved searches
     * @param  array  $user          User
     * @return array
     */
    public function prepareSavedSearches(array $savedSearches, array $user)
    {
        $result = [];

        foreach ($savedSearches as $savedSearch) {
            switch ($savedSearch->type) {
                case $this->SavedSearches->getCriteriaType():
                    $search = $this->SavedSearches->search(
                        $savedSearch->model,
                        $user,
                        json_decode($savedSearch->content, true)
                    );
                    $savedSearch->entities = $search['entities'];
                    break;

                case $this->SavedSearches->getResultType():
                    $search = $this->SavedSearches->get($savedSearch->id);
                    $savedSearch->entities = json_decode($search->content, true);
                    break;
            }
            // filter out skipped display fields
            $savedSearch->entities['display_columns'] = array_diff(
                $savedSearch->entities['display_columns'],
                $this->SavedSearches->getSkippedDisplayFields()
            );

            $result[] = $savedSearch;
        }

        return $result;
    }

    /**
     * Prepare associated saved searches data to be stored in the joined DB table.
     *
     * @param  array $savedSearches post request saved searches related data
     * @return array
     */
    public function prepareToSaveSavedSearches($savedSearches)
    {
        $result = [];
        foreach ($savedSearches['_ids'] as $k => $id) {
            $result[] = ['id' => $id,
                '_joinData' => [
                    'row' => $savedSearches['_rows'][$k],
                    'column' => $savedSearches['_columns'][$k]
                ]
            ];
        }

        return $result;
    }

    /**
     * Get specified user accessible dashboards.
     *
     * @param  array $user user details
     * @return \Cake\ORM\Query
     */
    public function getUserDashboards($user)
    {
        $groupsTable = TableRegistry::get('Groups.Groups');
        $capsTable = TableRegistry::get('RolesCapabilities.Capabilities');

        // get all dashboards
        $query = $this->find('all')->order('name');

        // return all dashboards if current user is superuser
        if (isset($user['is_superuser']) && $user['is_superuser']) {
            return $query;
        }

        $userGroups = [];
        if (method_exists($groupsTable, 'getUserGroups') && !empty($user)) {
            $userGroups = $groupsTable->getUserGroups($user['id']);
        }

        $userRoles = [];
        if (!empty($userGroups) && method_exists($capsTable, 'getGroupsRoles')) {
            $userRoles = $capsTable->getGroupsRoles($userGroups);
        }

        // get role(s) dashboards
        if (!empty($userRoles)) {
             $query = $query->where(['Dashboards.role_id IN' => array_keys($userRoles)]);
        }

        // get all dashboards not assigned to any role
        $query = $query->orWhere(['Dashboards.role_id IS NULL']);

        return $query;
    }
}
