<?php
namespace Search\Controller;

use Cake\Event\Event;
use Cake\Network\Exception\BadRequestException;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Search\Controller\AppController;

class SearchController extends AppController
{
    /**
     * Before filter
     *
     * @param  Event  $event Event object
     * @return void
     */
    public function beforeFilter(Event $event)
    {
        $this->loadModel('Search.SavedSearches');
    }

    /**
     * Advanced search action
     *
     * @param string $model model name
     * @return void
     */
    public function advanced($model = null)
    {
        $this->_searchAction($model, true);
    }

    /**
     * Basic search action
     *
     * @param string $model model name
     * @return void
     */
    public function basic($model = null)
    {
        $this->_searchAction($model);
    }

    /**
     * Save action
     *
     * @param string|null $id Search id.
     * @return void Redirects to advanced action.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function save($id = null)
    {
        $this->request->allowMethod(['patch', 'post', 'put']);
        $search = $this->SavedSearches->get($id);
        $search = $this->SavedSearches->patchEntity($search, $this->request->data);
        if ($this->SavedSearches->save($search)) {
            $this->Flash->success(__('The search has been saved.'));
        } else {
            $this->Flash->error(__('The search could not be saved. Please, try again.'));
        }
        return $this->redirect(['action' => 'advanced', $search->model]);
    }

    /**
     * Saved result action
     *
     * @param  string $model model name
     * @param  string $id    record id
     * @return void
     */
    public function savedResult($model, $id)
    {
        $search = $this->SavedSearches->get($id);
        $this->set('entities', json_decode($search->content));
        $this->set('fields', $this->Searchable->getListingFields($model));
    }

    /**
     * Search action
     *
     * @param  string $model model name
     * @param  bool   $advanced advanced search flag
     * @return void
     */
    protected function _searchAction($model, $advanced = false)
    {
        if (is_null($model)) {
            throw new BadRequestException();
        }
        $modelName = Inflector::pluralize(Inflector::classify($model));

        if ($this->request->is('post')) {
            $data = $this->request->data;
            $where = $this->Searchable->prepareWhereStatement($data, $modelName, $advanced);
            $table = TableRegistry::get($modelName);
            $query = $table->find('all')->where($where);

            /*
            if in advanced mode, pre-save search criteria and results
             */
            if ($advanced) {
                $preSaveIds = $this->SavedSearches->preSaveSearchCriteriaAndResults(
                    $model,
                    $query,
                    $data,
                    $this->Auth->user('id')
                );
                $this->set('saveSearchCriteriaId', $preSaveIds['saveSearchCriteriaId']);
                $this->set('saveSearchResultsId', $preSaveIds['saveSearchResultsId']);
            }
            $this->set('entities', $this->paginate($query));
            $this->set('fields', $this->Searchable->getListingFields($model));
        }

        $searchFields = [];
        if ($this->Searchable->isSearchable($modelName)) {
            $searchFields = $this->Searchable->getSearchableFields($modelName);
            $searchFields = $this->Searchable->getSearchableFieldProperties($modelName, $searchFields);
            $searchFields = $this->Searchable->getSearchableFieldLabels($searchFields);
        }

        $searchOperators = [];
        if (!empty($searchFields)) {
            $searchOperators = $this->Searchable->getFieldTypeOperators();
        }

        $savedSearches = $this->Searchable->getSavedSearches([$this->Auth->user('id')], [$model]);

        $this->set(compact('searchFields', 'searchOperators', 'savedSearches'));
    }
}
