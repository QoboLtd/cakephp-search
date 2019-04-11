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
namespace Search\Widgets;

use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;
use Search\Model\Entity\SavedSearch;

final class SavedSearchWidget extends BaseWidget
{
    const TABLE_PREFIX = 'table-datatable-';

    /**
     * Widget entity.
     *
     * @var \Search\Model\Entity\Widget
     */
    private $widget;

    /**
     * Saved search entity.
     *
     * @var \Search\Model\Entity\SavedSearch|null
     */
    private $data = null;

    public $renderElement = 'Search.Widgets/table';

    public $type = 'saved_search';
    public $errors = [];

    /**
     * {@inheritDoc}
     */
    protected $title = 'Saved search';

    /**
     * {@inheritDoc}
     */
    protected $icon = 'table';

    /**
     * {@inheritDoc}
     */
    protected $color = 'info';

    /**
     * construct method
     *
     * @param mixed[] $options containing widget entity.
     * @return void.
     */
    public function __construct(array $options)
    {
        if (! empty($options['entity'])) {
            $this->widget = $options['entity'];
        }
    }

    /**
     * @return \Search\Model\Entity\SavedSearch|null
     */
    public function getData() : ?SavedSearch
    {
        return $this->data;
    }

    /**
     * Retrieve SavedSearch results for the widget
     *
     * @param array $options containing entity and view params.
     * @return \Search\Model\Entity\SavedSearch|null
     */
    public function getResults(array $options = []) : ?SavedSearch
    {
        $this->setContainerId($options['entity']);

        $table = TableRegistry::get('Search.SavedSearches');

        /** @var \Search\Model\Entity\SavedSearch|null */
        $savedSearch = $table->find()
            ->where(['id' => $this->widget->get('widget_id')])
            ->enableHydration(true)
            ->first();

        if (null === $savedSearch) {
            $this->errors[] = 'No data found for this entity';

            return null;
        }

        $this->data = $savedSearch;

        return $this->data;
    }

    /**
     * setContainerId method.
     *
     * Setting unique identifier of the widget.
     *
     * @param \Cake\Datasource\EntityInterface $entity used for setting id of widget.
     * @return void
     */
    public function setContainerId(EntityInterface $entity) : void
    {
        $this->containerId = self::TABLE_PREFIX . md5($entity->id);
    }

    /**
     * @return string[] $errors in case of validation
     */
    public function getErrors() : array
    {
        return $this->errors;
    }
}
