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
                'Widgets' => [
                    'sort' => [
                        'Widgets.row' => 'ASC',
                        'Widgets.column' => 'ASC'
                    ]
                ]
            ]
        ]);

        $query = $this->Dashboards->getUserDashboards($this->Auth->user());

        $userDashboards = $query->find('list')->toArray();
        if (!array_key_exists($dashboard->id, $userDashboards)) {
            throw new ForbiddenException();
        }

        $this->set('dashboardWidgets', $dashboard->widgets);
        $this->set('columns', Configure::readOrFail('Search.dashboard.columns'));
        $this->set('user', $this->Auth->user());
        $this->set('dashboard', $dashboard);
        $this->set('_serialize', ['dashboard']);
    }

    /**
     * Add method
     *
     * @return \Cake\Network\Response|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $dashboard = $this->Dashboards->newEntity();

        $widgetsTable = TableRegistry::get('Search.Widgets');
        $widgets = $widgetsTable->getWidgets();

        if ($this->request->is('post')) {
            $data = $this->request->data;

            $widgets = [];

            $dashboard = $this->Dashboards->patchEntity($dashboard, [
                'name' => $data['name'],
                'role_id' => $data['role_id']
            ]);

            $resultedDashboard = $this->Dashboards->save($dashboard);

            if ($resultedDashboard) {
                $this->Flash->success(__('The dashboard has been saved.'));

                $dashboardId = $resultedDashboard->id;

                if (!empty($data['widgets'])) {
                    $count = count($data['widgets']['widget_id']);
                    for ($i = 0; $i < $count; $i++) {
                        array_push($widgets, [
                            'dashboard_id' => $dashboardId,
                            'widget_id' => $data['widgets']['widget_id'][$i],
                            'widget_type' => $data['widgets']['widget_type'][$i],
                            'widget_options' => null,
                            'column' => $data['widgets']['column'][$i],
                            'row' => $data['widgets']['row'][$i],
                        ]);
                    }

                    $widgetTable = TableRegistry::get('Search.Widgets');
                    foreach ($widgets as $w) {
                        $widget = $widgetTable->newEntity();
                        $widget = $widgetTable->patchEntity($widget, $w);
                        $widgetTable->save($widget);
                    }
                }

                return $this->redirect(['action' => 'view', $dashboard->id]);
            } else {
                $this->Flash->error(__('The dashboard could not be saved. Please, try again.'));
            }
        }

        $roles = $this->Dashboards->Roles->find('list', ['limit' => 200]);

        $this->set(compact('dashboard', 'roles', 'widgets'));
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
        $dashboard = $this->Dashboards->get($id, [
            'contain' => [
                'Widgets' => [
                    'sort' => [
                        'Widgets.row' => 'ASC',
                        'Widgets.column' => 'ASC'
                    ]
                ]
            ]
        ]);

        $dashboardWidgets = $dashboard->widgets;
        unset($dashboard->widgets);

        $widgetsTable = TableRegistry::get('Search.Widgets');

        $widgets = $widgetsTable->getWidgets();
        $savedWidgetData = [];
        foreach ($dashboardWidgets as $dashboardWidget) {
            foreach ($widgets as $k => $widget) {
                if ($dashboardWidget->widget_id !== $widget['data']['id']) {
                    continue;
                }
                $widget['data']['column'] = $dashboardWidget->column;
                $widget['data']['row'] = $dashboardWidget->row;
                array_push($savedWidgetData, $widget);
                unset($widgets[$k]);
            }
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->data;
            $widgets = [];

            if (!empty($data['widgets'])) {
                $count = count($data['widgets']['widget_id']);
                for ($i = 0; $i < $count; $i++) {
                    array_push($widgets, [
                        'dashboard_id' => $dashboard->id,
                        'widget_id' => $data['widgets']['widget_id'][$i],
                        'widget_type' => $data['widgets']['widget_type'][$i],
                        'widget_options' => null,
                        'column' => $data['widgets']['column'][$i],
                        'row' => $data['widgets']['row'][$i],
                    ]);
                }
            }

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

                if (!empty($widgets)) {
                    foreach ($widgets as $w) {
                        $widget = $widgetTable->newEntity();
                        $widget = $widgetTable->patchEntity($widget, $w);
                        $resultedWidgets = $widgetTable->save($widget);
                    }
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
