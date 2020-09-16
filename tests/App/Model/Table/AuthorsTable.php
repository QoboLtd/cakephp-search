<?php
namespace Search\Test\App\Model\Table;

use Cake\ORM\Table;

class AuthorsTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('authors');
        $this->setPrimaryKey('id');
        $this->setDisplayField('name');

        $this->addBehavior('Timestamp');

        $this->hasMany('Articles');
    }
}
