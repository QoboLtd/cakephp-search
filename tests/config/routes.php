<?php
namespace Search\Test\App\Config;

use Cake\Routing\Router;

Router::connect('/users/login', ['controller' => 'Users', 'action' => 'login']);
