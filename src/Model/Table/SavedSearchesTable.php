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

use ArrayObject;
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
            'foreignKey' => 'user_id',
            'className' => 'Search.Users'
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
            ->requirePresence('content', 'create')
            ->notEmpty('content')
            ->isArray('content')
            ->add('content', 'validateSaved', [
                'rule' => function ($value, $context) {
                    return is_array($value) ? array_key_exists('saved', $value) : false;
                },
                'message' => 'Missing required key "saved"'
            ])
            ->add('content', 'validateLatest', [
                'rule' => function ($value, $context) {
                    return is_array($value) ? array_key_exists('latest', $value) : false;
                },
                'message' => 'Missing required key "latest"'
            ]);

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
     * Structures "content" data to suported format.
     *
     * @param \Cake\Event\Event $event Event object
     * @param \ArrayObject $data Request data
     * @param \ArrayObject $options Marshaller options
     * @return void
     */
    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options) : void
    {
        // @todo this will be removed once saved searches table schema is adjusted
        $saved = Hash::get($data, 'content.saved', []);
        if (! empty($saved)) {
            $data['content']['latest'] = $saved;
        }
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
