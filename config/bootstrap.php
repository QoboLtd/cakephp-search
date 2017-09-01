<?php
use Cake\Core\Configure;
use Cake\Event\EventManager;
use Search\Event\Model\WidgetsListener;

/**
 * Plugin configuration
 */
// get app level config
$config = Configure::read('Search');
$config = $config ? $config : [];

// load default plugin config
Configure::load('Search.search');

// overwrite default plugin config by app level config
Configure::write('Search', array_replace_recursive(
    Configure::read('Search'),
    $config
));

EventManager::instance()->on(new WidgetsListener());
