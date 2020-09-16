<?php
namespace Search\Test\App\Model\Table;

use Cake\ORM\Query;
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
        $this->addBehavior('Search.Searchable');

        $this->belongsTo('Authors');
        $this->belongsToMany('Tags');
    }

    /**
     * Custom finder
     * @param  Query  $query   defult query
     * @param  mixed[]  $options where option
     * @return mixed[]
     */
    public function findTitle(Query $query, array $options): array
    {
        $query = $this->find()->enableHydration(false);
        $results = $query
                    ->select(['title', 'content'], true)
                    ->where($options)
                    ->all()
                    ->toArray();

        return $results;
    }
}
