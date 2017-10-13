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

$url = ['plugin' => 'Search', 'controller' => 'Dashboards', 'action' => 'edit', $entity->get('id')];
echo $this->Html->link('<i class="fa fa-pencil"></i> ' . __('Edit'), $url, [
    'escape' => false, 'title' => __('Edit'), 'class' => 'btn btn-default'
]);

$url = ['plugin' => 'Search', 'controller' => 'Dashboards', 'action' => 'delete', $entity->get('id')];
echo $this->Form->postLink('<i class="fa fa-trash"></i> ' . __('Delete'), $url, [
    'confirm' => __('Are you sure you want to delete {0}?', $entity->get('name')),
    'title' => __('Delete'),
    'escape' => false,
    'class' => 'btn btn-default'
]);
