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
    public function getWidgets(Event $event): void
    {
        $result = array_merge(
            (array)$event->result,
            $this->getSavedSearchWidgets(),
            $this->getReportWidgets(),
            $this->getAppWidgets()
        );

        $event->result = array_filter($result);
    }

    /**
     * Fetch all saved searches from the database.
     *
     * @return mixed[]
     */
    private function getSavedSearchWidgets(): array
    {
        $table = TableRegistry::get('Search.SavedSearches');

        $query = $table->find('all')
            ->where([
                'SavedSearches.name IS NOT' => null,
                'SavedSearches.name !=' => '',
                'SavedSearches.system' => false
            ])
            ->enableHydration(false)
            ->indexBy('id');

        if ($query->isEmpty()) {
            return [];
        }

        return [
            ['type' => 'saved_search', 'data' => $query->toArray()]
        ];
    }

    /**
     * Fetch all reports through Event broadcast.
     *
     * @return mixed[]
     */
    private function getReportWidgets(): array
    {
        $event = new Event((string)EventName::MODEL_DASHBOARDS_GET_REPORTS(), $this);
        EventManager::instance()->dispatch($event);

        if (empty($event->result)) {
            return [];
        }

        $result = [];
        foreach ($event->result as $reports) {
            foreach ($reports as $report) {
                if (! isset($result[$report['widget_type']])) {
                    $result[$report['widget_type']] = ['type' => $report['widget_type'], 'data' => []];
                }
                $result[$report['widget_type']]['data'][$report['id']] = $report;
            }
        }

        return array_values($result);
    }

    /**
     * Returns list of widgets defined in the application scope.
     *
     * @return mixed[]
     */
    private function getAppWidgets(): array
    {
        $table = TableRegistry::get('Search.AppWidgets');

        $query = $table->find('all')
            ->select(['id', 'name', 'content']);

        if ($query->isEmpty()) {
            return [];
        }

        $data = [];
        // normalize app widget data
        foreach ($query->all() as $entity) {
            $data[] = [
                'id' => $entity->get('id'),
                'model' => $entity->get('content')['model'],
                'name' => $entity->get('name'),
                'path' => $entity->get('content')['path']
            ];
        }

        return [
            ['type' => 'app', 'data' => $data]
        ];
    }
}
