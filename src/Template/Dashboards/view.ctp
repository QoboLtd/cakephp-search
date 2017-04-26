<?php
use Cake\Event\Event;
use Search\Widgets\WidgetFactory;

$scripts = [];
$chartData = [];

$event = new Event('Search.Dashboards.View.View.Menu.Top', $this, [
    'request' => $this->request,
    $dashboard
]);
$this->eventManager()->dispatch($event);
?>
<section class="content-header">
    <div class="row">
        <div class="col-xs-12 col-md-6">
            <h4><?= h($dashboard->name) ?></h4>
        </div>
        <div class="col-xs-12 col-md-6">
            <div class="pull-right">
                <div class="btn-group btn-group-sm" role="group">
                    <?= $event->result; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="content">
    <div class="row">
        <?php $columnsCount = count($columns); for ($col = 0; $col < $columnsCount; $col++) : ?>
            <div class="col-md-<?= 12 / $columnsCount ?>">
            <?php if (!empty($dashboardWidgets)) : ?>
                <?php
                foreach ($dashboardWidgets as $dw) {
                    if ($dw->column !== $col) {
                        continue;
                    }

                    try {
                        $widgetHandler = WidgetFactory::create($dw->widget_type, ['entity' => $dw]);

                        $widgetHandler->getResults(['entity' => $dw, 'user' => $user, 'rootView' => $this]);

                        if ($widgetHandler->getRenderElement() == 'graph') {
                            $chartData[] = $widgetHandler->getData();
                        }

                        $dataOptions = $widgetHandler->getOptions();
                        if (!empty($dataOptions['scripts'])) {
                            $scripts[] = $dataOptions['scripts'];
                        }

                        echo $this->element(
                            $widgetHandler->getRenderElement(),
                            ['widget' => $widgetHandler],
                            ['plugin' => false]
                        );
                    } catch (\Exception $e) {
                        echo $this->element('Search.missing_element', [
                            'exception' => $e,
                            'messages' => $widgetHandler->getErrors()
                        ]);
                    }
                }
                ?>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>
</section>

<?php echo $this->element('Search.widget_libraries', ['scripts' => $scripts, 'chartData' => $chartData]); ?>
