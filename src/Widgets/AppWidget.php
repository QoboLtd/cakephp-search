<?php
namespace Search\Widgets;

use Cake\ORM\TableRegistry;

class AppWidget extends BaseWidget
{
    const TABLE_PREFIX = 'table-datatable-';

    public $options = [];

    public $type = 'app';

    public $errors = [];

    /**
     * construct method
     *
     * @param array $options containing widget entity.
     * @return void.
     */
    public function __construct($options = [])
    {
        $this->options = $options;

        $table = TableRegistry::get('Search.AppWidgets');
        $entity = $table->findById($options['entity']->widget_id)->first();

        if (!$entity) {
            return;
        }

        $this->renderElement = $entity->content['element'];
    }

    /**
     * getOptions method.
     *
     * @return array $options of the widget.
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Retrieve SavedSearch results for the widget
     *
     * @param array $options containing entity and view params.
     * @return array $results from $this->_data.
     */
    public function getResults(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * @return array $errors in case of validation
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
