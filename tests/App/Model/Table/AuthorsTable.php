<?php
namespace Search\Test\App\Model\Table;

use Cake\ORM\Table;

class AuthorsTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('authors');
        $this->primaryKey('id');
        $this->displayField('name');

        $this->addBehavior('Muffin/Trash.Trash');

        $this->hasMany('Articles');
    }
}
