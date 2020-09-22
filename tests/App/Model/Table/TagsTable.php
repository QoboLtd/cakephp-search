<?php
namespace Qobo\Search\Test\App\Model\Table;

use Cake\ORM\Table;

class TagsTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('tags');
        $this->setPrimaryKey('id');
        $this->setDisplayField('name');

        $this->belongsToMany('Articles');
    }
}
