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
namespace Qobo\Search\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\Utility\Hash;
use Qobo\Search\Service\Search;
use Qobo\Search\Transformer\QueryDataTransformer;

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
     * @param mixed[] $options List of options to pass to the finder
     * @return \Cake\ORM\Query
     */
    public function findSearch(Query $query, array $options): Query
    {
        $data = QueryDataTransformer::transform($query, $options);

        $search = new Search($this->getTable());
        if (null !== $data->getGroup()) {
            $search->setGroupBy($data->getGroup());
        }

        if (null !== $data->getOrder()) {
            $search->setOrderBy($data->getOrder());
        }

        if (null !== $data->getConjunction()) {
            $search->setConjunction($data->getConjunction());
        }

        foreach ($data->getSelect() as $item) {
            $search->addSelect($item);
        }

        foreach ($data->getCriteria() as $item) {
            $search->addCriteria($item);
        }

        return $search->execute();
    }
}
