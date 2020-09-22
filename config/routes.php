<?php
use Cake\Routing\Router;

Router::plugin(
    'Qobo/Search',
    ['path' => '/search'],
    function ($routes) {
        $routes->fallbacks('DashedRoute');
    }
);
