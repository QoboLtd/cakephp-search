<?php
namespace Qobo\Search\Test\App\Config;

use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

Router::defaultRouteClass(DashedRoute::class);

Router::connect('/:controller/:action/*');
Router::plugin(
    'Search',
    ['path' => '/search'],
    function ($routes) {
        $routes->fallbacks('DashedRoute');
    }
);
