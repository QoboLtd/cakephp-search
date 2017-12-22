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
 * Widgets Controller
 *
 * @property \Search\Model\Table\WidgetsTable $Widgets
 */
class WidgetsController extends AppController
{
    /**
     * Index Method
     *
     * @return \Cake\Network\Response|null
     */
    public function index()
    {
        $table = TableRegistry::get('Search.Widgets');

        $widgets = $table->getWidgets();

        $this->set('widgets', $widgets);
        $this->set('_serialize', 'widgets');
    }
}
