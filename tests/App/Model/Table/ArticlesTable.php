<?php
namespace Search\Test\App\Model\Table;

use Cake\ORM\Table;

class ArticlesTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('articles');
        $this->primaryKey('id');
        $this->displayField('title');

        $this->addBehavior('Muffin/Trash.Trash');

        $this->belongsTo('Authors');
    }
}
