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
?>
<div class="btn-group btn-group-xs" role="group">
<?php
list($plugin, $controller) = pluginSplit($model);

$url = ['plugin' => $plugin, 'controller' => $controller, 'action' => 'view', $entity->get('id')];
echo $this->Html->link('<i class="fa fa-eye"></i> ', $url, [
    'title' => __('View'), 'class' => 'btn btn-default', 'escape' => false
]);
?>
</div>