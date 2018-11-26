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

use Cake\Core\Configure;
use Cake\Datasource\RepositoryInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;
use Cake\Http\Exception\BadRequestException;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
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
     * @return \Cake\Http\Response|void|null
     */
    public function search(string $id = '')
    {
        $model = $this->modelClass;

        /** @var \Search\Model\Table\SavedSearchesTable */
        $searchTable = TableRegistry::get($this->tableName);
        $table = $this->loadModel();
        $search = new Search($table, $this->Auth->user());

        if (!$searchTable->isSearchable($model)) {
            throw new BadRequestException('You cannot search in ' . implode(' - ', pluginSplit($model)) . '.');
        }

        // redirect on POST requests (PRG pattern)
        if ($this->request->is('post')) {
            $searchData = $search->prepareData($this->request);

            if ('' !== $id) {
                $search->update($searchData, $id);
            }

            if ('' === $id) {
                $id = $search->create($searchData);
            }

            list($plugin, $controller) = pluginSplit($model);

            return $this->redirect(['plugin' => $plugin, 'controller' => $controller, 'action' => __FUNCTION__, $id]);
        }

        $entity = $search->get($id);

        $searchData = json_decode($entity->get('content'), true);

        // return json response and skip any further processing.
        if ($this->request->is('ajax') && $this->request->accepts('application/json')) {
            $this->viewBuilder()->setClassName('Json');
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

        $savedSearches = $searchTable->find('all')
            ->where([
                'SavedSearches.name IS NOT' => null,
                'SavedSearches.system' => false,
                'SavedSearches.user_id' => $this->Auth->user('id'),
                'SavedSearches.model' => $model
            ])
            ->toArray();

        $this->set('savedSearches', $savedSearches);
        $this->set('model', $model);
        $this->set('searchData', $searchData);
        $this->set('savedSearch', $entity);
        $this->set('preSaveId', $search->create($searchData));
        // INFO: this is valid when a saved search was modified and the form was re-submitted
        $this->set('searchOptions', SearchOptions::get());
        $this->set('associationLabels', Utility::instance()->getAssociationLabels($table));

        $this->render($this->searchElement);
    }

    /**
     * Get AJAX response view variables
     *
     * @param mixed[] $searchData Search data
     * @param \Cake\Datasource\RepositoryInterface $table Table instance
     * @param \Search\Utility\Search $search Search instance
     * @return mixed[] Variables and values for AJAX response
     */
    protected function getAjaxViewVars(array $searchData, RepositoryInterface $table, Search $search): array
    {
        /** @var \Cake\ORM\Table */
        $table = $table;

        $searchData['sort_by_field'] = Hash::get($this->request->getQueryParams(), 'sort', '');
        $searchData['sort_by_order'] = Hash::get(
            $this->request->getQueryParams(),
            'direction',
            SearchOptions::DEFAULT_SORT_BY_ORDER
        );

        /** @var \Cake\ORM\Query */
        $query = $search->execute($searchData);
        $resultSet = $this->paginate($query);

        $event = new Event(
            (string)SearchEventName::MODEL_SEARCH_AFTER_FIND(),
            $this,
            ['entities' => $resultSet, 'table' => $table]
        );
        $this->getEventManager()->dispatch($event);
        $resultSet = $event->getResult() instanceof ResultSetInterface ? $event->getResult() : $resultSet;

        $primaryKeys = [];
        foreach ((array)$table->getPrimaryKey() as $primaryKey) {
            $primaryKeys[] = $table->aliasField($primaryKey);
        }

        $displayColumns = empty($searchData['group_by']) ?
            $searchData['display_columns'] :
            [$searchData['group_by'], pluginSplit($searchData['group_by'])[0] . '.' . Search::GROUP_BY_FIELD];
        $displayColumns = array_merge($primaryKeys, $displayColumns);

        return [
            'success' => true,
            'data' => Utility::instance()->formatter($resultSet, $displayColumns, $table, $this->Auth->user()),
            'pagination' => ['count' => $resultSet->count()],
            '_serialize' => ['success', 'data', 'pagination']
        ];
    }

    /**
     * Save action
     *
     * @param string $id Search id.
     * @return \Cake\Http\Response|void|null
     */
    public function saveSearch(string $id)
    {
        $this->request->allowMethod(['patch', 'post', 'put']);

        $table = TableRegistry::get($this->tableName);

        $search = $table->get($id);
        $search = $table->patchEntity($search, (array)$this->request->getData());
        if ($table->save($search)) {
            $this->Flash->success((string)__('The search has been saved.'));
        } else {
            $this->Flash->error((string)__('The search could not be saved. Please, try again.'));
        }

        return $this->redirect(['action' => 'search', $id]);
    }

    /**
     * Edit action
     *
     * @param string $preId Presaved Search id.
     * @param string $id Search id.
     * @return \Cake\Http\Response|void|null
     */
    public function editSearch(string $preId, string $id)
    {
        $this->request->allowMethod(['patch', 'post', 'put']);

        $table = TableRegistry::get($this->tableName);

        $search = $table->patchEntity($table->get($id), [
            'name' => $this->request->getData('name'),
            'content' => $table->get($preId)->get('content')
        ]);

        if ($table->save($search)) {
            $this->Flash->success((string)__('The search has been edited.'));
        } else {
            $this->Flash->error((string)__('The search could not be edited. Please, try again.'));
        }

        return $this->redirect(['action' => 'search', $id]);
    }
    /**
     * Copy action
     *
     * @param string $id Search id.
     * @return \Cake\Http\Response|void|null
     */
    public function copySearch(string $id)
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
            $this->Flash->success((string)__('The search has been copied.'));
        } else {
            $this->Flash->error((string)__('The search could not be copied. Please, try again.'));
        }

        return $this->redirect(['action' => 'search', $entity->id]);
    }

    /**
     * Delete method
     *
     * @param string $id Saved search id.
     * @return \Cake\Http\Response|void|null Redirects to referer.
     */
    public function deleteSearch(string $id)
    {
        $this->request->allowMethod(['post', 'delete']);

        $table = TableRegistry::get($this->tableName);
        $entity = $table->get($id);
        if ($table->delete($entity)) {
            $this->Flash->success((string)__('The saved search has been deleted.'));
        } else {
            $this->Flash->error((string)__('The saved search could not be deleted. Please, try again.'));
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
    public function exportSearch(string $id, string $filename)
    {
        $filename = '' === trim($filename) ? $this->name : $filename;
        $export = new Export($id, $filename, $this->Auth->user());

        if ($this->request->is('ajax') && $this->request->accepts('application/json')) {
            $page = (int)Hash::get($this->request->getQueryParams(), 'page', 1);
            $limit = (int)Hash::get($this->request->getQueryParams(), 'limit', Configure::read('Search.export.limit'));

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
