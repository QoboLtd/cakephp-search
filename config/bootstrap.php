<?php
use Cake\Core\Configure;
use Cake\Event\EventManager;
use Qobo\Search\Event\Model\WidgetsListener;

/**
 * Plugin configuration
 */

// get app level config
$config = Configure::read('Qobo/Search');
$config = $config ? $config : [];

// load default plugin config
Configure::load('Qobo/Search.search');

// load color palette for reports
Configure::load('Qobo/Search.color_palette');

// overwrite default plugin config by app level config
Configure::write('Search', array_replace_recursive(
    Configure::read('Search'),
    $config
));

EventManager::instance()->on(new WidgetsListener());
