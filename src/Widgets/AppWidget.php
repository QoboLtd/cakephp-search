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
namespace Qobo\Search\Widgets;

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
     * {@inheritDoc}
     */
    protected $title = 'App';

    /**
     * {@inheritDoc}
     */
    protected $icon = 'gears';

    /**
     * {@inheritDoc}
     */
    protected $color = 'danger';

    /**
     * Constructor method.
     *
     * @param mixed[] $options containing widget entity.
     * @return void
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Widget options getter.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * {@inheritDoc}
     */
    public function getResults(array $options = [])
    {
        $table = TableRegistry::getTableLocator()->get('Qobo/Search.AppWidgets');

        /**
         * @var \Cake\Datasource\EntityInterface|null
         */
        $entity = $table->find()
            ->where(['id' => $options['entity']->widget_id])
            ->enableHydration()
            ->first();

        if (null === $entity) {
            $this->errors[] = 'Widget not found.';

            return [];
        }

        $this->renderElement = $entity->get('content')['element'];

        return [];
    }

    /**
     * Widget loading errors.
     *
     * @return mixed[] $errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
