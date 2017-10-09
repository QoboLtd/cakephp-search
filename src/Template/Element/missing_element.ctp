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
<div class="dashboard-widget-display_config">
    <div class="box box-danger">
        <div class="box-header with-border">
            <h3 class="box-title"><?= $exception->getMessage()?></h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse">
                    <i class="fa fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="box-body">
            <ul>
                <?php foreach ($messages as $msg) : ?>
                    <li><?= $msg ?></li>
                <?php endforeach;?>
            </ul>
        </div>
    </div>
</div>
