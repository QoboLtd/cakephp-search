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

use Cake\ORM\TableRegistry;

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
     * @return \Cake\Http\Response|void|null
     */
    public function index()
    {
        /**
         * @var \Search\Model\Table\WidgetsTable $table
         */
        $table = TableRegistry::getTableLocator()->get('Search.Widgets');

        $widgets = $table->getWidgets();

        $this->set('widgets', $widgets);
        $this->set('_serialize', 'widgets');
    }
}
