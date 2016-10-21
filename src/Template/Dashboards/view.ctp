<?php
use Cake\Event\Event;
?>

<div class="row">
    <div class="col-xs-6">
        <h3><strong><?= h($dashboard->name) ?></strong></h3>
    </div>
    <div class="col-xs-6">
    <?php
    $event = new Event('Search.Dashboards.View.View.Menu.Top', $this, [
        'request' => $this->request,
        $dashboard
    ]);
    $this->eventManager()->dispatch($event);
    if (!empty($event->result)) : ?>
        <div class="h3 text-right">
            <?= $event->result; ?>
        </div>
    <?php endif; ?>
    </div>
</div>

<div class="row">
    <?php
    if( !empty($widgets) ) {
        foreach( $widgets as $widget) {
            echo $this->cell("Search.Widget::{$widget->widgetDisplayMethod}" , [[$widget], ['user' => $user]]);
        }

    }
    ?>
</div>

<?= $this->element('Search.common_js_libs'); ?>
