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

use Cake\Core\Configure;

$url = $this->Url->build([
    'plugin' => $this->request->param('plugin'),
    'controller' => $this->request->param('controller'),
    'action' => $this->request->param('action'),
    $this->request->param('pass.0'),
    $filename
]);

echo $this->Html->script(
    [
        'Search.exporter',
        'Search.export.init'
    ],
    [
        'block' => 'scriptBottom'
    ]
);
echo $this->Html->scriptBlock(
    'search_export.init({
        url: "' . $url . '",
        count: ' . $count . ',
        limit: "' . Configure::read('Search.export.limit') . '",
        token: "' . Configure::read('Search.api.token') . '",
        completed_message: "' . __('completed') . '"
    })',
    ['block' => 'scriptBottom']
);
?>
<section class="content-header">
    <h4>Export &raquo; <?= $this->Html->link($filename, [
        'plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'search', $this->request->param('pass.0')
    ]) ?></h4>
</section>
<section class="content">
    <div class="row">
        <div class="col-xs-12 col-md-8 col-lg-4">
            <div class="box box-solid no-padding">
                <div class="box-body">
                    <!-- Apply any bg-* class to to the info-box to color it -->
                    <div id="search-export-report" class="info-box bg-blue">
                        <span class="info-box-icon"><i class="fa fa-spinner fa-pulse"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">
                                <?= __('Export') ?>
                                <span class="progress-status"><?= __('in progress') ?></span>
                            </span>
                            <span class="info-box-number"><?= $count ?> <?= __('records') ?></span>
                            <!-- The progress section is optional -->
                            <div class="progress">
                                <div class="progress-bar" style="width: 0%"></div>
                            </div>
                            <span class="progress-description">
                                <span class="progress-percent">0%</span> <?= __('completed') ?>
                            </span>
                        </div><!-- /.info-box-content -->
                    </div><!-- /.info-box -->
                    <div id="search-export-link" class="hidden text-center">
                        <a href="#" title="Download Link">
                            <i class="fa fa-cloud-download fa-5x" aria-hidden="true"></i>
                        </a>
                        <h4 class="text-uppercase text-muted"><?= ('Download') ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
