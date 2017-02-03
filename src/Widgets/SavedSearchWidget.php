<?php
namespace Search\Widgets;

use Cake\ORM\TableRegistry;
use Search\Widgets\BaseWidget;

class SavedSearchWidget extends BaseWidget
{
    const TABLE_PREFIX = 'table-datatable-';

    protected $_entity = null;

    protected $_tableName = 'Search.SavedSearches';

    protected $_tableInstance = null;

    protected $_data = [];

    public $renderElement = 'table';

    public $options = [];

    public $type = 'saved_search';

    /**
     * construct method
     * @param array $options containing widget entity.
     * @return void.
     */
    public function __construct($options = [])
    {
        if (!empty($options['entity'])) {
            $this->_entity = $options['entity'];
        }
        $this->_tableInstance = TableRegistry::get($this->_tableName);
    }

    /**
     * getOptions method.
     * @return array $options of the widget.
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return string $type of the widget.
     */
    public function getSavedSearchType()
    {
        return $this->getData()->type;
    }

    /**
     * @return array $_data of the widget.
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * getResults method
     * @param array $options containing entity and view params.
     * @return array $results from $this->_data.
     */
    public function getResults(array $options = [])
    {
        $results = [];

        $this->setContainerId($options['entity']);

        try {
            $query = $this->_tableInstance->findById($this->_entity->widget_id);
            $resultSet = $query->first();
            if (!empty($resultSet)) {
                $results = $resultSet;
            }
        } catch (\Exception $e) {
            debug($e->getMessage());
        }

        if (empty($this->_entity)) {
            return $results;
        }

        switch ($results->type) {
            case $this->_tableInstance->getCriteriaType():
                $search = $this->_tableInstance->search(
                    $results->model,
                    $options['user'],
                    json_decode($results->content, true),
                    true
                );
                $results->entities = $search['entities'];
                break;
            case $this->_tableInstance->getResultType():
                $results->entities = json_decode($results->content, true);
                break;
        }
        $results->entities['display_columns'] = array_diff(
            $results->entities['display_columns'],
            $this->_tableInstance->getSkippedDisplayFields()
        );

        $this->_data = $results;
        $this->options['scripts'] = $this->getScripts(['data' => $this->_data]);

        return $results;
    }

    /**
     * setContainerId method.
     * Setting unique identifier of the widget.
     * @param array $entity used for setting id of widget.
     * @return string $containerId of the widget.
     */
    public function setContainerId($entity = null)
    {
        $this->containerId = self::TABLE_PREFIX . md5($entity->id);

        return $this->containerId;
    }

    /**
     * prepareChartOptions
     * @param array $data passed
     * @return array $content with CSS/JS libs.
     */
    public function getScripts(array $options = [])
    {
        $entities = $options['data']->entities;

        $uid = $this->getContainerId();

        $content = [
            'post' => [
                'css' => [
                    'type' => 'css',
                    'content' => [
                        'AdminLTE./plugins/datatables/dataTables.bootstrap',
                    ],
                    'block' => 'css',
                ],
                'javascript' => [
                    'type' => 'script',
                    'content' => [
                        'AdminLTE./plugins/datatables/jquery.dataTables.min',
                        'AdminLTE./plugins/datatables/dataTables.bootstrap.min',
                        'Search.view-search-result',
                    ],
                    'block' => 'scriptBotton',
                ],
                'scriptBlock' => [
                    'type' => 'scriptBlock',
                    'content' => 'view_search_result.init({
                        table_id: \'#table-datatable-' . $uid . '\',
                        sort_by_field: \'' . (int)array_search($entities['sort_by_field'], $entities['display_columns']) . '\',
                        sort_by_order: \'' . $entities['sort_by_order'] . '\'
                        });',
                    'block' => 'scriptBotton',
                ],
            ]
        ];

        return $content;
    }
}
