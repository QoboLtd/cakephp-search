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
        $this->_searchAction($model, true, true);
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
        $this->set('search_name', $search->name);
        $this->set('entities', json_decode($search->content));
        $this->set('fields', $this->SavedSearches->getListingFields($model));
    }

    /**
     * Search action
     *
     * @param  string $model model name
     * @param  bool   $advanced advanced search flag
     * @return void
     */
    protected function _searchAction($model, $advanced = false, $preSave = false)
    {
        if (is_null($model)) {
            throw new BadRequestException();
        }

        if ($this->request->is('post')) {
            $search = $this->SavedSearches->search($model, $this->Auth->user(), $this->request->data, $advanced, $preSave);

            /*
            if in advanced mode, pre-save search criteria and results
             */
            if ($advanced) {
                $this->set('saveSearchCriteriaId', $search['saveSearchCriteriaId']);
                $this->set('saveSearchResultsId', $search['saveSearchResultsId']);
            }
            $this->set('entities', $this->paginate($search['entities']));
            $this->set('fields', $this->SavedSearches->getListingFields($model));
        }

        $searchFields = [];
        /*
        get searchable fields
         */
        if ($this->Searchable->isSearchable($model)) {
            $searchFields = $this->SavedSearches->getSearchableFields($model);
            $searchFields = $this->SavedSearches->getSearchableFieldProperties($model, $searchFields);
            $searchFields = $this->SavedSearches->getSearchableFieldLabels($searchFields);
        }

        $searchOperators = [];
        /*
        get search operators based on searchable fields
         */
        if (!empty($searchFields)) {
            $searchOperators = $this->Searchable->getFieldTypeOperators();
        }

        $savedSearches = $this->Searchable->getSavedSearches([$this->Auth->user('id')], [$model]);

        $this->set(compact('searchFields', 'searchOperators', 'savedSearches'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Saved search id.
     * @return \Cake\Network\Response|null Redirects to referer.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $savedSearch = $this->SavedSearches->get($id);
        if ($this->SavedSearches->delete($savedSearch)) {
            $this->Flash->success(__('The saved search has been deleted.'));
        } else {
            $this->Flash->error(__('The saved search could not be deleted. Please, try again.'));
        }
        return $this->redirect($this->referer());
    }
}
