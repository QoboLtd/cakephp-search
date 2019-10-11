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

use CakeDC\Users\Controller\Traits\CustomUsersTableTrait;
use Cake\Database\Schema\TableSchema;
use Cake\Event\Event;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use Qobo\Utils\ModuleConfig\ConfigType;
use Qobo\Utils\ModuleConfig\ModuleConfig;

/**
 * SavedSearches Model
 *
 * @property \Cake\ORM\Association\BelongsTo $Users
 */
class SavedSearchesTable extends Table
{
    use CustomUsersTableTrait;

    /**
     * Initialize method
     *
     * @param mixed[] $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('saved_searches');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->addBehavior('Muffin/Trash.Trash');

        $this->belongsTo('Users', [
            'className' => $this->getUsersTable()->getRegistryAlias(),
            'foreignKey' => 'user_id'
        ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function _initializeSchema(TableSchema $schema) : TableSchema
    {
        $schema->setColumnType('content', 'json');
        $schema->setColumnType('criteria', 'json');
        $schema->setColumnType('fields', 'json');

        return $schema;
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
            ->requirePresence('model', 'create')
            ->notEmpty('model');

        $validator
            ->requirePresence('user_id', 'create')
            ->notEmpty('user_id');

        $validator
            ->allowEmpty('criteria')
            ->isArray('criteria')

            ->inList('conjunction', \Search\Criteria\Conjunction::CONJUNCTIONS)

            ->allowEmpty('fields')
            ->isArray('fields')

            ->inList('sort_by_order', \Search\Criteria\Direction::DIRECTIONS);

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
        $rules->add($rules->existsIn(['user_id'], $this->getUsersTable()));

        return $rules;
    }

    /**
     * Returns true if table is searchable, false otherwise.
     *
     * @param  string $tableName Table name.
     * @return bool
     * @deprecated 20.0.0 This should be handled by the application/business logic.
     */
    public function isSearchable(string $tableName): bool
    {
        deprecationWarning(
            __METHOD__ . '() is deprecated. This should be handled by the application/business logic.'
        );

        list(, $tableName) = pluginSplit($tableName);

        $config = (new ModuleConfig(ConfigType::MODULE(), $tableName))->parse();
        if (! property_exists($config, 'table')) {
            return false;
        }

        if (! property_exists($config->table, 'searchable')) {
            return false;
        }

        return (bool)$config->table->searchable;
    }
}
