<?php
namespace Search\Widgets;

use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Search\Widgets\BaseWidget;

class GridWidget extends ReportWidget
{
    public $renderElement = 'Search.Widgets/grid';
    public $options = [];
}
