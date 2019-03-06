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

use Cake\Datasource\EntityInterface;
use Search\Controller\AppController;
use Webmozart\Assert\Assert;

/**
 * SavedSearches Controller
 *
 * @property \Search\Model\Table\SavedSearchesTable $SavedSearches
 */
class SavedSearchesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $this->request->allowMethod('get');

        $query = $this->SavedSearches->find('all')
            ->where(['name IS NOT' => null])
            ->where($this->request->getQueryParams());

        $entities = $this->paginate($query);

        $this->set('success', true);
        $this->set('data', $entities);
        $this->set('_serialize', ['success', 'data']);
    }

    /**
     * View method
     *
     * @param string $id Saved Search id.
     * @return \Cake\Http\Response|void
     */
    public function view(string $id)
    {
        $this->request->allowMethod('get');

        $entity = $this->SavedSearches->find()
            ->enableHydration(true)
            ->where(['id' => $id])
            ->first();

        $this->set('success', null !== $entity);
        null !== $entity ?
            $this->set('data', $entity) :
            $this->set('error', sprintf('Failed to fetch saved search for record with ID "%s".', $id));
        $this->set('_serialize', ['success', 'data', 'error']);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|void
     */
    public function add()
    {
        $this->request->allowMethod('post');

        $entity = $this->SavedSearches->newEntity();
        $entity = $this->SavedSearches->patchEntity($entity, (array)$this->request->getData());
        $success = (bool)$this->SavedSearches->save($entity);

        $this->set('success', $success);
        $success ?
            $this->set('data', ['id' => $entity->get('id')]) :
            $this->set('error', $entity->getErrors());
        $this->set('_serialize', ['success', 'data', 'error']);
    }

    /**
     * Edit method
     *
     * @param string $id Saved Search id.
     * @return \Cake\Http\Response|void
     */
    public function edit(string $id)
    {
        $this->request->allowMethod('put');

        $entity = $this->SavedSearches->find()
            ->enableHydration(true)
            ->where(['id' => $id])
            ->first();

        $success = false;
        if (null !== $entity) {
            Assert::isInstanceOf($entity, EntityInterface::class);
            $entity = $this->SavedSearches->patchEntity($entity, (array)$this->request->getData());
            $success = (bool)$this->SavedSearches->save($entity);
        }

        $this->set('success', $success);
        $success ?
            $this->set('data', []) :
            $this->set('error', 'The saved search could not be saved. Please, try again.');
        $this->set('_serialize', ['success', 'data', 'error']);
    }

    /**
     * Delete method
     *
     * @param string $id Saved Search id.
     * @return \Cake\Http\Response|void
     */
    public function delete(string $id)
    {
        $this->request->allowMethod('delete');

        $entity = $this->SavedSearches->find()
            ->enableHydration(true)
            ->where(['id' => $id])
            ->first();

        $success = false;
        if (null !== $entity) {
            Assert::isInstanceOf($entity, EntityInterface::class);
            $success = $this->SavedSearches->delete($entity);
        }

        $this->set('success', $success);
        $success ?
            $this->set('data', []) :
            $this->set('error', 'The saved search could not be deleted. Please, try again.');
        $this->set('_serialize', ['success', 'data', 'error']);
    }
}
