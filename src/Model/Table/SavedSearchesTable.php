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

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use InvalidArgumentException;
use Qobo\Utils\ModuleConfig\ConfigType;
use Qobo\Utils\ModuleConfig\ModuleConfig;
use Search\Model\Entity\SavedSearch;

/**
 * SavedSearches Model
 *
 * @property \Cake\ORM\Association\BelongsTo $Users
 */
class SavedSearchesTable extends Table
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

        $this->table('saved_searches');
        $this->displayField('name');
        $this->primaryKey('id');
        $this->addBehavior('Timestamp');
        $this->addBehavior('Muffin/Trash.Trash');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
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
            ->requirePresence('name', 'create')
            ->notEmpty('name', 'update')
            ->allowEmpty('name', 'create');

        $validator
            ->requirePresence('type', 'create')
            ->notEmpty('type', 'update')
            ->allowEmpty('name', 'create');

        $validator
            ->requirePresence('model', 'create')
            ->notEmpty('model');

        $validator
            ->requirePresence('shared', 'create')
            ->notEmpty('shared');

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
        # TODO : Temporary disabled
        #$rules->add($rules->existsIn(['user_id'], 'Users'));

        return $rules;
    }

    /**
     * Returns saved searches filtered by users and models.
     *
     * @param  array  $users  users ids
     * @param  array  $models models names
     * @return \Cake\ORM\ResultSet
     */
    public function getSavedSearches(array $users = [], array $models = [])
    {
        $conditions = [
            'SavedSearches.name IS NOT' => null
        ];

        if (!empty($users)) {
            $conditions['SavedSearches.user_id IN'] = $users;
        }

        if (!empty($models)) {
            $conditions['SavedSearches.model IN'] = $models;
        }

        $query = $this->find('all', [
            'conditions' => $conditions
        ]);

        return $query->toArray();
    }

    /**
     * Validate if search is editable.
     *
     * @param \Search\Model\Entity\SavedSearch $entity Search entity
     * @return bool
     */
    public function isEditable(SavedSearch $entity)
    {
        return (bool)$entity->get('name');
    }

    /**
     * Returns true if table is searchable, false otherwise.
     *
     * @param  string $tableName Table name.
     * @return bool
     */
    public function isSearchable($tableName)
    {
        if (!is_string($tableName)) {
            throw new InvalidArgumentException('Provided variable [tableName] must be a string.');
        }

        list(, $tableName) = pluginSplit($tableName);

        $config = new ModuleConfig(ConfigType::MODULE(), $tableName);

        $result = (bool)$config->parse()->table->searchable;

        return $result;
    }
}
