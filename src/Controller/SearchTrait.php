<?php
namespace Search\Controller;

use Cake\Event\Event;
use Cake\Filesystem\File;
use Cake\Network\Exception\BadRequestException;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Search\Controller\Traits\SearchableTrait;
use Search\Model\Entity\SavedSearch;
use Search\Model\Table\SavedSearchesTable;
use Zend\Diactoros\Stream;

trait SearchTrait
{
    use SearchableTrait;

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

        if (!$this->_isSearchable($model)) {
            throw new BadRequestException('You cannot search in ' . implode(' - ', pluginSplit($model)) . '.');
        }

        $table = TableRegistry::get($this->_tableSearch);

        // redirect on POST requests (PRG pattern)
        if ($this->request->is('post')) {
            $searchData = $table->prepareData($this->request, $model, $this->Auth->user());

            if ($id) {
                $table->updateSearch($model, $this->Auth->user(), $searchData, $id);
            } else {
                $id = $table->createSearch($model, $this->Auth->user(), $searchData);
            }

            list($plugin, $controller) = pluginSplit($model);

            return $this->redirect([
                'plugin' => $plugin,
                'controller' => $controller,
                'action' => __FUNCTION__,
                $id
            ]);
        }

        $entity = $table->getSearch($model, $this->Auth->user(), $id);

        $searchData = json_decode($entity->content, true);

        if ($this->request->accepts('application/json')) {
            $this->_ajaxResponse($entity, $searchData, $model);

            return;
        }

        $searchData = $table->validateData($model, $searchData['latest']);

        $this->set('searchFields', $table->getSearchableFields($model));
        $this->set('savedSearches', $table->getSavedSearches([$this->Auth->user('id')], [$model]));
        $this->set('model', $model);
        $this->set('searchData', $searchData);
        $this->set('savedSearch', $entity);
        $this->set('preSaveId', $table->createSearch($model, $this->Auth->user(), $searchData));
        // INFO: this is valid when a saved search was modified and the form was re-submitted
        $this->set('isEditable', $table->isEditable($entity));
        $this->set('searchOptions', $table->getSearchOptions());

        $this->render($this->_elementSearch);
    }

    /**
     * Ajax response.
     *
     * @param \Cake\ORM\EntitySavedSearch $entity Search entity
     * @param array $data Search data
     * @param string $model Model name
     * @return void
     */
    protected function _ajaxResponse(SavedSearch $entity, array $data, $model)
    {
        if (!$this->request->accepts('application/json')) {
            return;
        }

        $table = TableRegistry::get($this->_tableSearch);

        $searchData = $data['latest'];

        $displayColumns = $searchData['display_columns'];

        $sortField = $this->request->query('order.0.column') ?: 0;
        $sortField = array_key_exists($sortField, $displayColumns) ?
            $displayColumns[$sortField] :
            current($displayColumns);
        $searchData['sort_by_field'] = $sortField;

        $searchData['sort_by_order'] = $this->request->query('order.0.dir') ?: $table->getDefaultSortByOrder();

        $query = $table->search($model, $this->Auth->user(), $searchData);

        $event = new Event('Search.Model.Search.afterFind', $this, [
            'entities' => $this->paginate($query),
            'table' => TableRegistry::get($model)
        ]);
        $this->eventManager()->dispatch($event);

        $data = $table->toDatatables($event->result, $displayColumns, $model);

        $pagination = [
            'count' => $query->count()
        ];

        $table->resetSearch($entity, $model, $this->Auth->user());

        $this->set([
            'success' => true,
            'data' => $data,
            'pagination' => $pagination,
            '_serialize' => ['success', 'preSaveId', 'data', 'pagination']
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

        $table = TableRegistry::get($this->_tableSearch);

        $savedSearch = $table->get($id);

        $content = json_decode($savedSearch->content, true);

        // @todo this is temporary fix to stripping out html tags from results columns
        foreach ($content['result'] as &$row) {
            foreach ($row as &$column) {
                $column = trim(strip_tags($column));
            }
        }
        reset($content['result']);
        // end of temporary fix

        // create temporary file
        $path = TMP . uniqid($this->request->param('action') . '_') . '.csv';
        $file = new File($path, true);

        // write to temporary file
        $handler = fopen($path, 'w');
        fputcsv($handler, array_keys(current($content['result'])));
        foreach ($content['result'] as $row) {
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
