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
namespace Qobo\Search\Controller;

/**
 * Widgets Controller
 *
 * @property \Qobo\Search\Model\Table\WidgetsTable $Widgets
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
        $widgets = $this->Widgets->getWidgets();

        $this->set('widgets', $widgets);
        $this->set('_serialize', 'widgets');
    }
}
