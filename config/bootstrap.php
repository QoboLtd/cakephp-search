<?php
use Cake\Core\Configure;
use Cake\Event\EventManager;
use Search\Event\Model\WidgetsListener;

// dashboards columns
Configure::write('Search.dashboard.columns', ['Left Side', 'Right Side']);

EventManager::instance()->on(new WidgetsListener());
