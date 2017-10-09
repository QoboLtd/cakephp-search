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

use Cake\ORM\TableRegistry;

class AppWidget extends BaseWidget
{
    /**
     * Widget type.
     *
     * @var string
     */
    public $type = 'app';

    /**
     * Widget options.
     *
     * @var array
     */
    public $options = [];

    /**
     * Widget loading error messages.
     *
     * @var array
     */
    public $errors = [];

    /**
     * Constructor method.
     *
     * @param array $options containing widget entity.
     * @return void
     */
    public function __construct($options = [])
    {
        $this->options = $options;
    }

    /**
     * Widget options getter.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@inheritDoc}
     */
    public function getResults(array $options = [])
    {
        $table = TableRegistry::get('Search.AppWidgets');
        $entity = $table->findById($options['entity']->widget_id)->first();

        if ($entity) {
            $this->renderElement = $entity->content['element'];

            return [];
        }

        // get trashed record to display appropriate error message
        $entity = $table->find('withTrashed')
            ->where([$table->aliasField($table->getPrimaryKey()) => $options['entity']->widget_id])
            ->first();
        $this->renderElement = $entity->content['element'];
        $this->errors[] = 'Widget "' . $entity->name . '" has been deleted.';

        return [];
    }

    /**
     * Widget loading errors.
     *
     * @return array $errors
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
