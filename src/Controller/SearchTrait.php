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
namespace Search\Controller;

use Cake\Event\Event;
use Cake\Network\Exception\BadRequestException;
use Cake\ORM\ResultSet;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Search\Event\EventName as SearchEventName;
use Search\Utility;
use Search\Utility\Export;
use Search\Utility\Options as SearchOptions;
use Search\Utility\Search;
use Search\Utility\Validator as SearchValidator;

trait SearchTrait
{
    /**
     * Table name for Saved Searches model.
     *
     * @var string
     */
    protected $tableName = 'Search.SavedSearches';

    /**
     * Element to be used as Search template.
     *
     * @var string
     */
    protected $searchElement = 'Search.Search/search';

    /**
     * Search action
     *
     * @param  string $id Saved search id
     * @return \Cake\Network\Response|void
     */
    public function search($id = null)
    {
        $model = $this->modelClass;

        $searchTable = TableRegistry::get($this->tableName);
        $table = $this->{$this->name};
        $search = new Search($table, $this->Auth->user());

        if (!$searchTable->isSearchable($model)) {
            throw new BadRequestException('You cannot search in ' . implode(' - ', pluginSplit($model)) . '.');
        }

        // redirect on POST requests (PRG pattern)
        if ($this->request->is('post')) {
            $searchData = $search->prepareData($this->request);

            if ($id) {
                $search->update($searchData, $id);
            }

            if (!$id) {
                $id = $search->create($searchData);
            }

            list($plugin, $controller) = pluginSplit($model);

            return $this->redirect(['plugin' => $plugin, 'controller' => $controller, 'action' => __FUNCTION__, $id]);
        }

        $entity = $search->get($id);

        $searchData = json_decode($entity->content, true);

        // return json response and skip any further processing.
        if ($this->request->is('ajax') && $this->request->accepts('application/json')) {
            $this->viewBuilder()->className('Json');
            $response = $this->getAjaxViewVars($searchData['latest'], $table, $search);
            $this->set($response);

            return;
        }

        $searchData = SearchValidator::validateData($table, $searchData['latest'], $this->Auth->user());

        // reset should only be applied to current search id (url parameter)
        // and NOT on newly pre-saved searches and that's we do the ajax
        // request check above, to prevent resetting the pre-saved search.
        $search->reset($entity);

        $this->set('searchableFields', Utility::instance()->getSearchableFields($table, $this->Auth->user()));
        $this->set('savedSearches', $searchTable->getSavedSearches([$this->Auth->user('id')], [$model]));
        $this->set('model', $model);
        $this->set('searchData', $searchData);
        $this->set('savedSearch', $entity);
        $this->set('preSaveId', $search->create($searchData));
        // INFO: this is valid when a saved search was modified and the form was re-submitted
        $this->set('isEditable', $searchTable->isEditable($entity));
        $this->set('searchOptions', SearchOptions::get());
        $this->set('associationLabels', Utility::instance()->getAssociationLabels($table));

        $this->render($this->searchElement);
    }

    /**
     * Get AJAX response view variables
     *
     * @param array $searchData Search data
     * @param \Cake\ORM\Table $table Table instance
     * @param \Search\Utility\Search $search Search instance
     * @return array Variables and values for AJAX response
     */
    protected function getAjaxViewVars(array $searchData, Table $table, Search $search)
    {
        $displayColumns = [];

        if (empty($searchData['group_by'])) {
            $displayColumns = $searchData['display_columns'];
        }

        if (!empty($searchData['group_by'])) {
            list($prefix, ) = pluginSplit($searchData['group_by']);
            $displayColumns = array_merge($displayColumns, (array)$searchData['group_by']);
            $displayColumns[] = $prefix . '.' . Search::GROUP_BY_FIELD;
        }

        $searchData['sort_by_field'] = $this->request->query('sort');

        $searchData['sort_by_order'] = $this->request->query('direction') ?: SearchOptions::DEFAULT_SORT_BY_ORDER;

        $query = $search->execute($searchData);

        $resultSet = $this->paginate($query);
        $eventName = (string)SearchEventName::MODEL_SEARCH_AFTER_FIND();
        $event = new Event($eventName, $this, [
            'entities' => $resultSet,
            'table' => $table
        ]);
        $this->eventManager()->dispatch($event);

        // overwrite result-set with event result, if a registered listener is found.
        if (!empty($this->eventManager()->listeners($eventName))) {
            $resultSet = $event->result;
        }

        $data = [];
        if ($resultSet instanceof ResultSet) {
            array_unshift($displayColumns, $table->aliasField($table->getPrimaryKey()));
            $data = Utility::instance()->formatter($resultSet, $displayColumns, $table, $this->Auth->user());
        }

        $pagination = [
            'count' => $query->count()
        ];

        $result = [
            'success' => true,
            'data' => $data,
            'pagination' => $pagination,
            '_serialize' => ['success', 'data', 'pagination']
        ];

        return $result;
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

        $table = TableRegistry::get($this->tableName);

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

        $table = TableRegistry::get($this->tableName);

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

        $table = TableRegistry::get($this->tableName);

        // get saved search
        $entity = $table->get($id);
        $data = $entity->toArray();

        $entity = $table->newEntity();

        // patch new entity with saved search data
        $entity = $table->patchEntity($entity, $data);
        if ($table->save($entity)) {
            $this->Flash->success(__('The search has been copied.'));
        } else {
            $this->Flash->error(__('The search could not be copied. Please, try again.'));
        }

        return $this->redirect(['action' => 'search', $entity->id]);
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

        $table = TableRegistry::get($this->tableName);
        $entity = $table->get($id);
        if ($table->delete($entity)) {
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
     * @param string $filename Export filename
     * @return \Cake\Http\Response|void
     */
    public function exportSearch($id, $filename = null)
    {
        $filename = is_null($filename) ? $this->name : $filename;
        $export = new Export($id, $filename, $this->Auth->user());

        if ($this->request->is('ajax') && $this->request->accepts('application/json')) {
            $page = (int)$this->request->query('page');
            $limit = (int)$this->request->query('limit');

            $export->execute($page, $limit);

            $result = [
                'success' => true,
                'data' => ['path' => $export->getUrl()],
                '_serialize' => ['success', 'data']
            ];

            $this->set($result);

            return;
        }

        $this->set('count', $export->count());
        $this->set('filename', $filename);
        $this->render('Search.Search/export');
    }
}
