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

$url = ['plugin' => 'Qobo/Search', 'controller' => 'Dashboards', 'action' => 'edit', $entity->get('id')];
echo $this->Html->link('<i class="fa fa-pencil"></i> ' . __d('Qobo/Search', 'Edit'), $url, [
    'escape' => false, 'title' => __d('Qobo/Search', 'Edit'), 'class' => 'btn btn-default'
]);

$url = ['plugin' => 'Qobo/Search', 'controller' => 'Dashboards', 'action' => 'delete', $entity->get('id')];
echo $this->Form->postLink('<i class="fa fa-trash"></i> ' . __d('Qobo/Search', 'Delete'), $url, [
    'confirm' => __d('Qobo/Search', 'Are you sure you want to delete {0}?', $entity->get('name')),
    'title' => __d('Qobo/Search', 'Delete'),
    'escape' => false,
    'class' => 'btn btn-default'
]);
