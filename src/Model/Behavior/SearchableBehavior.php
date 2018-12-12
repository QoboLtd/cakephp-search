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
namespace Search\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\Utility\Hash;
use Search\Service\Criteria;
use Search\Service\Search;

class SearchableBehavior extends Behavior
{
    /**
     * {@inheritDoc}
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        if (! Hash::get($config, 'fields')) {
            $this->setConfig(
                'fields',
                $this->getTable()
                    ->getSchema()
                    ->columns()
            );
        }
    }

    /**
     * Search finder method.
     *
     * @param \Cake\ORM\Query $query The query object to apply the finder options to
     * @param array $options List of options to pass to the finder
     * @return \Cake\ORM\Query
     */
    public function findSearch(Query $query, array $options) : Query
    {
        // $aggregator = Hash::get($options, 'aggerator', self::DEFAULT_AGGREGATOR);

        $search = new Search($query, $this->getTable());

        foreach (Hash::get($options, 'data', []) as $criteria) {
            if (! is_array($criteria)) {
                throw new \InvalidArgumentException(sprintf(
                    'Search criteria must be an array, %s provided instead',
                    gettype($criteria)
                ));
            }

            $search->addCriteria(new Criteria($criteria));
        }

        return $search->execute();
    }
}
