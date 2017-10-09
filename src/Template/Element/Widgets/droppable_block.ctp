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

use Cake\Utility\Inflector;

list($plugin, $model) = pluginSplit($widget['data']['model']);

switch ($widget['type']) {
    case 'report':
        $divColor = 'bg-green';
        break;

    case 'app':
        $divColor = 'bg-blue';
        break;

    case 'saved_search':
        $divColor = 'bg-aqua';
        break;

    default:
        $divColor = 'bg-red';
        break;
}
?>
<li class="droppable col-lg-3 col-xs-6" data-id="<?= $widget['data']['id'] ?>" data-type="<?= $widget['type'] ?>">
    <div class="small-box <?= $divColor?>">
        <div class="inner">
            <h4><?= $model?></h4>
            <p><?= $widget['data']['name']?></p>
        </div>
        <div class="icon">
            <?php if (in_array($widget['type'], ['saved_search'])) : ?>
                <i class="ion ion-android-list"></i>
            <?php elseif (in_array($widget['type'], ['report'])) : ?>
                <i class="ion ion-stats-bars"></i>
            <?php else : ?>
                <i class="ion ion-cube"></i>
            <?php endif; ?>
        </div>
        <div class="small-box-footer">
            <?= $model ?>
        </div>
    </div>
</li>
