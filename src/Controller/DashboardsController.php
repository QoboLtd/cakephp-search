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
namespace Qobo\Search\Controller;

use Cake\Core\Configure;
use Cake\Http\Exception\ForbiddenException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;

/**
 * Dashboards Controller
 *
 * @property \Search\Model\Table\DashboardsTable $Dashboards
 */
class DashboardsController extends AppController
{

    /**
     * Index method
     *
     * @return \Cake\Http\Response|void|null
     */
    public function index()
    {
        $query = $this->Dashboards->getUserDashboards($this->Auth->user());
        $entity = $query->first();

        if (null !== $entity) {
            return $this->redirect(['action' => 'view', $entity->get('id')]);
        }
    }

    /**
     * View method
     *
     * @param string $id Dashboard id.
     * @return \Cake\Http\Response|void|null
     * @throws \Cake\Http\Exception\ForbiddenException
     */
    public function view(string $id)
    {
        $dashboard = $this->Dashboards->get($id, [
            'contain' => [
                'Roles',
                'Widgets',
            ],
        ]);

        $query = $this->Dashboards->getUserDashboards($this->Auth->user());
        /**
         * @var \Search\Model\Table\WidgetsTable $widgetsTable
         */
        $widgetsTable = TableRegistry::getTableLocator()->get('Search.Widgets');

        $userDashboards = $query->find('list')->toArray();
        if (!array_key_exists($dashboard->id, $userDashboards)) {
            throw new ForbiddenException();
        }

        $widgets = [];

        foreach ($dashboard->get('widgets') as $k => $item) {
            $opts = $widgetsTable->getWidgetOptions($item);

            $x = (int)Hash::get($opts, 'x', 0);
            $y = (int)Hash::get($opts, 'y', 0);

            if (isset($widgets[$y][$x])) {
                $widgets[$y][] = $item;
            } else {
                $widgets[$y][$x] = $item;
            }
        }

        ksort($widgets);

        foreach ($widgets as $k => $items) {
            if (count($items) < 2) {
                continue;
            }

            usort($widgets[$k], function ($a, $b) {
                $opts_a = (array)json_decode($a->widget_options, true);
                $opts_b = (array)json_decode($b->widget_options, true);

                $x_a = (int)Hash::get($opts_a, 'x', 0);
                $x_b = (int)Hash::get($opts_b, 'x', 0);

                if ($x_a == $x_b) {
                    return 0;
                }

                return ($x_a < $x_b) ? -1 : 1;
            });
        }

        $this->set('dashboardWidgets', $widgets);
        $this->set('columns', Configure::readOrFail('Search.dashboard.columns'));
        $this->set('user', $this->Auth->user());
        $this->set('dashboard', $dashboard);
        $this->set('_serialize', ['dashboard']);
    }

    /**
     * Add method
     *
     * @TODO: refactor the code. Eyez bleeedzz
     *
     * @return \Cake\Http\Response|void|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $dashboard = $this->Dashboards->newEntity();

        /**
         * @var \Search\Model\Table\WidgetsTable $widgetsTable
         */
        $widgetsTable = TableRegistry::getTableLocator()->get('Search.Widgets');
        $widgets = $widgetsTable->getWidgets();

        if ($this->request->is('post')) {
            $data = (array)$this->request->getData();

            $widgets = [];

            $dashboard = $this->Dashboards->patchEntity($dashboard, [
                'name' => $data['name'],
                'role_id' => $data['role_id'],
            ]);

            $resultedDashboard = $this->Dashboards->save($dashboard);

            if ($resultedDashboard) {
                $this->Flash->success((string)__d('Qobo/Search', 'The dashboard has been saved.'));

                $dashboardId = $resultedDashboard->id;

                $data['widgets'] = !empty($data['options']) ? json_decode($data['options'], true) : [];

                if (!empty($data['widgets'])) {
                    $widgetsTable->saveDashboardWidgets($dashboardId, $data['widgets']);
                }

                return $this->redirect(['action' => 'view', $dashboard->id]);
            } else {
                $this->Flash->error((string)__d('Qobo/Search', 'The dashboard could not be saved. Please, try again.'));
            }
        }

        $roles = $this->Dashboards->Roles->find('list', ['limit' => 200]);
        $savedWidgetData = [];

        $this->set(compact('dashboard', 'roles', 'widgets', 'savedWidgetData'));
        $this->set('columns', Configure::readOrFail('Search.dashboard.columns'));
        $this->set('_serialize', ['dashboard']);
    }

    /**
     * Edit method
     *
     * @param string $id Dashboard id.
     * @return \Cake\Http\Response|void|null Redirects on successful edit, renders view otherwise.
     */
    public function edit(string $id)
    {
        $savedWidgetData = [];
        $dashboard = $this->Dashboards->get($id, [
            'contain' => ['Widgets'],
        ]);

        $dashboardWidgets = $dashboard->get('widgets');
        $dashboard->unsetProperty('widgets');

        /**
         * @var \Search\Model\Table\WidgetsTable $widgetsTable
         */
        $widgetsTable = TableRegistry::getTableLocator()->get('Search.Widgets');
        $widgets = $widgetsTable->getWidgets();

        $sequence = 0;

        foreach ($dashboardWidgets as $dw) {
            foreach ($widgets as $widget) {
                if ($dw->widget_id !== $widget['data']['id']) {
                    continue;
                }

                $widgetOptions = $widgetsTable->getWidgetOptions($dw, ['sequence' => $sequence]);

                $item = array_merge(
                    [
                        'id' => $widget['data']['id'],
                    ],
                    [
                        'data' => $widget['data'],
                    ],
                    $widgetOptions
                );
                unset($item['data']['content'], $item['data']['created'], $item['data']['modified']);
                if (!empty($item['data']['query'])) {
                    unset($item['data']['query']);
                }
                array_push($savedWidgetData, $item);

                $sequence++;
            }
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = (array)$this->request->getData();

            $dashboard->unsetProperty('widgets');

            $dashboard = $this->Dashboards->patchEntity($dashboard, [
                'name' => $data['name'],
                'role_id' => $data['role_id'],
            ]);

            if ($this->Dashboards->save($dashboard)) {
                $this->Flash->success((string)__d('Qobo/Search', 'The dashboard has been saved.'));
                /** @var \Search\Model\Table\WidgetsTable */
                $widgetTable = TableRegistry::getTableLocator()->get('Search.Widgets');
                $widgetTable->trashAll([
                    'dashboard_id' => $dashboard->id,
                ]);

                $data['widgets'] = !empty($data['options']) ? json_decode($data['options'], true) : [];
                if (!empty($data['widgets'])) {
                    $widgetsTable->saveDashboardWidgets($id, $data['widgets']);
                }

                return $this->redirect(['action' => 'view', $id]);
            } else {
                $this->Flash->error((string)__d('Qobo/Search', 'The dashboard could not be saved. Please, try again.'));
            }
        }

        $roles = $this->Dashboards->Roles->find('list', ['limit' => 200]);

        $this->set(compact('dashboard', 'roles', 'widgets', 'savedWidgetData'));
        $this->set('columns', Configure::readOrFail('Search.dashboard.columns'));
        $this->set('_serialize', ['dashboard']);
    }

    /**
     * Delete method
     *
     * @param string $id Dashboard id.
     * @return \Cake\Http\Response|void|null Redirects to index.
     */
    public function delete(string $id)
    {
        $this->request->allowMethod(['post', 'delete']);
        $dashboard = $this->Dashboards->get($id);

        if ($this->Dashboards->delete($dashboard)) {
            /** @var \Search\Model\Table\WidgetsTable */
            $widgetTable = TableRegistry::getTableLocator()->get('Search.Widgets');
            $widgetTable->trashAll([
                'dashboard_id' => $id,
            ]);

            $this->Flash->success((string)__d('Qobo/Search', 'The dashboard has been deleted.'));
        } else {
            $this->Flash->error((string)__d('Qobo/Search', 'The dashboard could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
