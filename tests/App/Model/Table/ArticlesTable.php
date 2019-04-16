<?php
namespace Search\Test\App\Model\Table;

use Cake\ORM\Table;

class ArticlesTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('articles');
        $this->setPrimaryKey('id');
        $this->setDisplayField('title');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Authors');
    }

    public function findTitle($query, array $options)
    {
        $query = $this->find()->enableHydration(false);
        $results = $query
                    ->select(['title',
                              'content'
                             ], true)
                    ->where($options)
                    ->all()
                    ->toArray();

        return $results;
    }
}
