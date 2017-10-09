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
namespace Search\Event\Model;

use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Search\Event\EventName;

class WidgetsListener implements EventListenerInterface
{
    /**
     * {@inheritDoc}
     */
    public function implementedEvents()
    {
        return [
            (string)EventName::MODEL_DASHBOARDS_GET_WIDGETS() => [
                'callable' => 'getWidgets',
                'priority' => 9999999999 // this listener should be called last
            ]
        ];
    }

    /**
     * Add widgets for Search plugin's Dashboards.
     *
     * @param \Cake\Event\Event $event Event instance
     * @return void
     */
    public function getWidgets(Event $event)
    {
        $result = !empty($event->result) ? $event->result : [];

        $savedSearches = $this->_getSavedSearches();
        if (!empty($savedSearches)) {
            array_push($result, ['type' => 'saved_search', 'data' => $savedSearches]);
        }

        $reports = $this->_getReports();
        if (!empty($reports)) {
            $graphs = [];
            $grids = [];
            foreach ($reports as $id => $info) {
                if ($info['widget_type'] == 'grid') {
                    $grids[$id] = $info;
                } else {
                    $graphs[$id] = $info;
                }
            }

            if (!empty($grids)) {
                array_push($result, ['type' => 'grid', 'data' => $grids]);
            }

            if (!empty($graphs)) {
                array_push($result, ['type' => 'report', 'data' => $graphs]);
            }
        }

        $appWidgets = $this->_getAppWidgets();
        if (!empty($appWidgets)) {
            array_push($result, ['type' => 'app', 'data' => $appWidgets]);
        }

        $event->result = $result;
    }

    /**
     * Fetch all saved searches from the database.
     *
     * @return array
     */
    protected function _getSavedSearches()
    {
        $table = TableRegistry::get('Search.SavedSearches');

        $query = $table->find('all')
            ->where(['SavedSearches.name IS NOT' => null])
            ->hydrate(false)
            ->indexBy('id');

        if ($query->isEmpty()) {
            return [];
        }

        $result = $query->toArray();
        foreach ($result as &$entity) {
            $table = TableRegistry::get($entity['model']);
            if (!method_exists($table, 'moduleAlias')) {
                continue;
            }
            $entity['model'] = $table->moduleAlias();
        }

        return $result;
    }

    /**
     * Fetch all reports through Event broadcast.
     *
     * @return array
     */
    protected function _getReports()
    {
        $event = new Event((string)EventName::MODEL_DASHBOARDS_GET_REPORTS(), $this);
        EventManager::instance()->dispatch($event);

        if (empty($event->result)) {
            return [];
        }

        $result = [];
        foreach ($event->result as $model => $reports) {
            foreach ($reports as $reportSlug => $reportInfo) {
                $result[$reportInfo['id']] = $reportInfo;
            }
        }

        return $result;
    }

    /**
     * Returns list of widgets defined in the application scope.
     *
     * @return array
     */
    protected function _getAppWidgets()
    {
        $table = TableRegistry::get('Search.AppWidgets');

        $query = $table->find('all');

        if ($query->isEmpty()) {
            return [];
        }

        $result = [];
        foreach ($query->toArray() as $entity) {
            $result[] = [
                'id' => $entity->id,
                'model' => $entity->content['model'],
                'name' => $entity->name,
                'path' => $entity->content['path']
            ];
        }

        return $result;
    }
}
