<?php
namespace Search\Controller;

use Cake\Event\Event;
use Cake\Filesystem\File;
use Cake\Network\Exception\BadRequestException;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Search\Event\EventName as SearchEventName;
use Search\Utility;
use Zend\Diactoros\Stream;

trait SearchTrait
{
    /**
     * Table name for Saved Searches model.
     *
     * @var string
     */
    protected $_tableSearch = 'Search.SavedSearches';

    /**
     * Element to be used as Search template.
     *
     * @var string
     */
    protected $_elementSearch = 'Search.Search/search';

    /**
     * Search action
     *
     * @param  string $id Saved search id
     * @return \Cake\Network\Response|void
     */
    public function search($id = null)
    {
        $model = $this->modelClass;

        $searchTable = TableRegistry::get($this->_tableSearch);
        $table = TableRegistry::get($model);

        if (!$searchTable->isSearchable($model)) {
            throw new BadRequestException('You cannot search in ' . implode(' - ', pluginSplit($model)) . '.');
        }

        // redirect on POST requests (PRG pattern)
        if ($this->request->is('post')) {
            $searchData = $searchTable->prepareData($this->request, $table, $this->Auth->user());

            if ($id) {
                $searchTable->updateSearch($table, $this->Auth->user(), $searchData, $id);
            } else {
                $id = $searchTable->createSearch($table, $this->Auth->user(), $searchData);
            }

            list($plugin, $controller) = pluginSplit($model);

            return $this->redirect(['plugin' => $plugin, 'controller' => $controller, 'action' => __FUNCTION__, $id]);
        }

        $entity = $searchTable->getSearch($table, $this->Auth->user(), $id);

        $searchData = json_decode($entity->content, true);

        // return json response and skip any further processing.
        if ($this->request->accepts('application/json')) {
            $this->_ajaxResponse($searchData, $table);

            return;
        }

        $searchData = $searchTable->validateData($table, $searchData['latest'], $this->Auth->user());

        // reset should only be applied to current search id (url parameter)
        // and NOT on newly pre-saved searches and that's we do the ajax
        // request check above, to prevent resetting the pre-saved search.
        $searchTable->resetSearch($entity, $table, $this->Auth->user());

        $this->set('searchableFields', Utility::instance()->getSearchableFields($table, $this->Auth->user()));
        $this->set('savedSearches', $searchTable->getSavedSearches([$this->Auth->user('id')], [$model]));
        $this->set('model', $model);
        $this->set('searchData', $searchData);
        $this->set('savedSearch', $entity);
        $this->set('preSaveId', $searchTable->createSearch($table, $this->Auth->user(), $searchData));
        // INFO: this is valid when a saved search was modified and the form was re-submitted
        $this->set('isEditable', $searchTable->isEditable($entity));
        $this->set('searchOptions', $searchTable->getSearchOptions());
        $this->set('associationLabels', Utility::instance()->getAssociationLabels($table));

        $this->render($this->_elementSearch);
    }

    /**
     * Ajax response.
     *
     * @param array $data Search data
     * @param \Cake\ORM\Table $table Table instance
     * @return void
     */
    protected function _ajaxResponse(array $data, Table $table)
    {
        if (!$this->request->accepts('application/json')) {
            return;
        }

        $searchTable = TableRegistry::get($this->_tableSearch);

        $searchData = $data['latest'];

        $displayColumns = $searchData['display_columns'];

        $sortField = $this->request->query('order.0.column') ?: 0;
        $sortField = array_key_exists($sortField, $displayColumns) ?
            $displayColumns[$sortField] :
            current($displayColumns);
        $searchData['sort_by_field'] = $sortField;

        $searchData['sort_by_order'] = $this->request->query('order.0.dir') ?: $searchTable->getDefaultSortByOrder();

        $query = $searchTable->search($table, $this->Auth->user(), $searchData);

        $event = new Event((string)SearchEventName::MODEL_SEARCH_AFTER_FIND(), $this, [
            'entities' => $this->paginate($query),
            'table' => $table
        ]);
        $this->eventManager()->dispatch($event);

        $data = [];
        if ($event->result) {
            $data = Utility::instance()->toDatatables($event->result, $displayColumns, $table);
        }

        $pagination = [
            'count' => $query->count()
        ];

        $this->set([
            'success' => true,
            'data' => $data,
            'pagination' => $pagination,
            '_serialize' => ['success', 'data', 'pagination']
        ]);
    }

    /**
     * Save action
     *
     * @param string|null $id Search id.
     * @return \Cake\Network\Response|null
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function saveSearch($id = null)
    {
        $this->request->allowMethod(['patch', 'post', 'put']);

        $table = TableRegistry::get($this->_tableSearch);

        $search = $table->get($id);
        $search = $table->patchEntity($search, $this->request->data);
        if ($table->save($search)) {
            $this->Flash->success(__('The search has been saved.'));
        } else {
            $this->Flash->error(__('The search could not be saved. Please, try again.'));
        }

        return $this->redirect(['action' => 'search', $id]);
    }

    /**
     * Edit action
     *
     * @param string|null $preId Presaved Search id.
     * @param string|null $id Search id.
     * @return \Cake\Network\Response|null
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function editSearch($preId = null, $id = null)
    {
        $this->request->allowMethod(['patch', 'post', 'put']);

        $table = TableRegistry::get($this->_tableSearch);

        // get pre-saved search
        $preSaved = $table->get($preId);
        // merge pre-saved search and request data
        $data = array_merge($preSaved->toArray(), $this->request->data);

        $search = $table->get($id);
        $search = $table->patchEntity($search, $data);
        if ($table->save($search)) {
            $this->Flash->success(__('The search has been edited.'));
        } else {
            $this->Flash->error(__('The search could not be edited. Please, try again.'));
        }

        return $this->redirect(['action' => 'search', $id]);
    }
    /**
     * Copy action
     *
     * @param string|null $id Search id.
     * @return \Cake\Network\Response|null
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function copySearch($id = null)
    {
        $this->request->allowMethod(['patch', 'post', 'put']);

        $table = TableRegistry::get($this->_tableSearch);

        // get saved search
        $savedSearch = $table->get($id);

        $search = $table->newEntity();

        // patch new entity with saved search data
        $search = $table->patchEntity($search, $savedSearch->toArray());
        if ($table->save($search)) {
            $this->Flash->success(__('The search has been copied.'));
        } else {
            $this->Flash->error(__('The search could not be copied. Please, try again.'));
        }

        return $this->redirect(['action' => 'search', $search->id]);
    }

    /**
     * Delete method
     *
     * @param string|null $id Saved search id.
     * @return \Cake\Network\Response|null Redirects to referer.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function deleteSearch($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $table = TableRegistry::get($this->_tableSearch);
        $savedSearch = $table->get($id);
        if ($table->delete($savedSearch)) {
            $this->Flash->success(__('The saved search has been deleted.'));
        } else {
            $this->Flash->error(__('The saved search could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'search']);
    }

    /**
     * Export Search results
     *
     * Method responsible for exporting search results
     * into a CSV file and forcing file download.
     *
     * @param string $id Pre-saved search id
     * @param string $name Saved search name
     * @return \Cake\Http\Response
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function exportSearch($id, $name = null)
    {
        $this->autoRender = false;
        $this->request->allowMethod(['patch', 'post', 'put']);

        $searchTable = TableRegistry::get($this->_tableSearch);

        // get saved search
        $savedSearch = $searchTable->get($id);

        // extract info
        $searchData = json_decode($savedSearch->content, true);
        $searchData = $searchData['latest'];

        $table = TableRegistry::get($savedSearch->model);

        // execute search
        $entities = $searchTable->search($table, $this->Auth->user(), $searchData)->all();

        $event = new Event((string)SearchEventName::MODEL_SEARCH_AFTER_FIND(), $this, [
            'entities' => $entities,
            'table' => $table
        ]);
        $this->eventManager()->dispatch($event);
        if ($event->result) {
            $entities = $event->result;
        }

        $entities = Utility::instance()->toCsv($entities, $searchData['display_columns'], $table);

        $content = [];
        foreach ($entities as $k => $entity) {
            $content[$k] = [];
            foreach ($searchData['display_columns'] as $column) {
                // @todo this is temporary fix to stripping out html tags from results columns
                $value = trim(strip_tags($entity[$column]));
                // end of temporary fix
                $content[$k][] = $value;
            }
        }

        // create temporary file
        $path = TMP . uniqid($this->request->param('action') . '_') . '.csv';
        $file = new File($path, true);

        $associationLabels = Utility::instance()->getAssociationLabels($table);
        $searchableFields = Utility::instance()->getSearchableFields($table, $this->Auth->user());
        $columns = [];
        foreach ($searchData['display_columns'] as $column) {
            $tableName = substr($column, 0, strpos($column, '.'));
            $label = array_key_exists($tableName, $associationLabels) ?
                $associationLabels[$tableName] :
                $tableName;

            list(, $modelName) = pluginSplit($savedSearch->model);
            $suffix = $modelName === $label ? '' : ' (' . $label . ')';
            $columns[] = $searchableFields[$column]['label'] . $suffix;
        }

        // write to temporary file
        $handler = fopen($path, 'w');
        fputcsv($handler, $columns);
        foreach ($content as $row) {
            fputcsv($handler, $row);
        }
        fclose($handler);

        // create a stream from file
        $stream = new Stream($path, 'rb');

        // prepare response body
        $response = $this->response;
        $response = $response->withBody($stream);
        $response = $response->withType('csv');

        // custom filename
        $filename = $name ? $name : $this->name;
        $filename .= ' ' . date('Y-m-d H-m-s') . '.csv';

        // force file download
        $response = $response->withDownload($filename);

        // delete temporary file
        unlink($path);

        return $response;
    }
}
