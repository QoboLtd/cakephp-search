<?php
use Cake\Core\Configure;

$url = $this->Url->build([
    'plugin' => $this->request->param('plugin'),
    'controller' => $this->request->param('controller'),
    'action' => $this->request->param('action'),
    $this->request->param('pass.0'),
    $filename
]);

echo $this->Html->script('Search.exporter', ['block' => 'scriptBottom']);
echo $this->Html->scriptBlock(
    '$( document ).ready(function() {
        $("#search-export-link").exporter({
            url: "' . $url . '",
            count: ' . $count . ',
            limit: "' . Configure::read('Search.export.limit') . '",
            token: "' . Configure::read('Search.api.token') . '"
        });
    });
    $(document).on("progress.search.export", function (e) {
        var percent = Math.floor(e.percent);
        $("#search-export-report .progress-bar").css("width", percent + "%");
        $("#search-export-report .progress-percent").text(percent + "%");
    })
    $(document).on("completed.search.export", function (e) {
        $("#search-export-report").removeClass("bg-blue").addClass("bg-green-active");
        $("#search-export-report .info-box-icon .fa").removeClass("fa-spinner fa-pulse").addClass("fa-check");
        $("#search-export-report .progress-status").text("' . __('completed') . '");
        $("#search-export-link a").attr("href", e.link);
        $("#search-export-link").removeClass("hidden");
    })',
    ['block' => 'scriptBottom']
);
?>
<section class="content-header">
    <h4>Export</h4>
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