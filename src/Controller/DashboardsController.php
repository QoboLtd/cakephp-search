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
use Cake\Event\Event;
use Cake\Network\Exception\ForbiddenException;
use Cake\ORM\TableRegistry;
use Search\Controller\AppController;
use Search\Model\Entity\Widget;

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
     * @return \Cake\Network\Response|null
     */
    public function index()
    {
        $query = $this->Dashboards->getUserDashboards($this->Auth->user());

        if (!$query->isEmpty()) {
            return $this->redirect(['action' => 'view', $query->first()->id]);
        }
    }

    /**
     * View method
     *
     * @param string|null $id Dashboard id.
     * @return void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     * @throws \Cake\Network\Exception\ForbiddenException
     */
    public function view($id = null)
    {
        $dashboard = $this->Dashboards->get($id, [
            'contain' => [
                'Roles',
                'Widgets'
            ]
        ]);

        $query = $this->Dashboards->getUserDashboards($this->Auth->user());
        $widgetsTable = TableRegistry::get('Search.Widgets');

        $userDashboards = $query->find('list')->toArray();
        if (!array_key_exists($dashboard->id, $userDashboards)) {
            throw new ForbiddenException();
        }

        $widgets = [];

        foreach ($dashboard->widgets as $k => $item) {
            $opts = $widgetsTable->getWidgetPosition($item); //, ['sequence' => $k]);

            $x = (int)$opts['x'];
            $y = (int)$opts['y'];

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
                $opts_a = json_decode($a->widget_options, true);
                $opts_b = json_decode($b->widget_options, true);

                $x_a = (int)$opts_a['x'];
                $x_b = (int)$opts_b['x'];

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
     * @return \Cake\Network\Response|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $dashboard = $this->Dashboards->newEntity();

        $widgetsTable = TableRegistry::get('Search.Widgets');
        $widgets = $widgetsTable->getWidgets();

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            $widgets = [];

            $dashboard = $this->Dashboards->patchEntity($dashboard, [
                'name' => $data['name'],
                'role_id' => $data['role_id'],
            ]);

            $resultedDashboard = $this->Dashboards->save($dashboard);

            if ($resultedDashboard) {
                $this->Flash->success(__('The dashboard has been saved.'));

                $dashboardId = $resultedDashboard->id;

                $data['widgets'] = !empty($data['options']) ? json_decode($data['options'], true) : [];

                if (!empty($data['widgets'])) {
                    $saved = $widgetsTable->saveDashboardWidgets($dashboardId, $data['widgets']);
                }

                return $this->redirect(['action' => 'view', $dashboard->id]);
            } else {
                $this->Flash->error(__('The dashboard could not be saved. Please, try again.'));
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
     * @param string|null $id Dashboard id.
     * @return \Cake\Network\Response|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $savedWidgetData = [];
        $dashboard = $this->Dashboards->get($id, [
            'contain' => ['Widgets']
        ]);

        $dashboardWidgets = $dashboard->widgets;
        unset($dashboard->widgets);

        $widgetsTable = TableRegistry::get('Search.Widgets');
        $widgets = $widgetsTable->getWidgets();

        $sequence = 0;

        foreach ($dashboardWidgets as $dw) {
            foreach ($widgets as $k => $widget) {
                if ($dw->widget_id !== $widget['data']['id']) {
                    continue;
                }

                $widgetOptions = $widgetsTable->getWidgetPosition($dw, ['sequence' => $sequence]);

                $item = array_merge(
                    [
                        'id' => $widget['data']['id']
                    ],
                    [
                        'data' => $widget['data']
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
            $data = $this->request->getData();

            unset($dashboard->widgets);

            $dashboard = $this->Dashboards->patchEntity($dashboard, [
                'name' => $data['name'],
                'role_id' => $data['role_id']
            ]);

            if ($this->Dashboards->save($dashboard)) {
                $this->Flash->success(__('The dashboard has been saved.'));

                $widgetTable = TableRegistry::get('Search.Widgets');
                $widgetTable->trashAll([
                    'dashboard_id' => $dashboard->id
                ]);

                $data['widgets'] = !empty($data['options']) ? json_decode($data['options'], true) : [];
                if (!empty($data['widgets'])) {
                    $saved = $widgetsTable->saveDashboardWidgets($id, $data['widgets']);
                }

                return $this->redirect(['action' => 'view', $id]);
            } else {
                $this->Flash->error(__('The dashboard could not be saved. Please, try again.'));
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
     * @param string|null $id Dashboard id.
     * @return \Cake\Network\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $dashboard = $this->Dashboards->get($id);

        if ($this->Dashboards->delete($dashboard)) {
            $widgetTable = TableRegistry::get('Search.Widgets');
            $widgetTable->trashAll([
                'dashboard_id' => $id
            ]);

            $this->Flash->success(__('The dashboard has been deleted.'));
        } else {
            $this->Flash->error(__('The dashboard could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
