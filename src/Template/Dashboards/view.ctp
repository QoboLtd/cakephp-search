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

use Qobo\Search\Widgets\WidgetFactory;

$scripts = [];
$chartData = [];
?>
<section class="content-header">
    <div class="row">
        <div class="col-xs-12 col-md-6">
            <h4><?= h($dashboard->name) ?></h4>
        </div>
        <div class="col-xs-12 col-md-6">
            <div class="pull-right">
                <div class="btn-group btn-group-sm" role="group">
                    <?= $this->element('Qobo/Search.Menu/dashboards-view-top', [
                        'entity' => $dashboard
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="content">
    <?php foreach ($dashboardWidgets as $row => $column) : ?>
        <div class="row">
        <?php foreach ($column as $k => $dw) : ?>
            <?php
            $options = json_decode($dw->widget_options, true);
            $width = (!empty($options['w'])) ? $options['w'] : '6';
            ?>
            <div class="col-md-<?=$width;?>">
            <?php
            try {
                $widgetHandler = WidgetFactory::create($dw->widget_type, ['entity' => $dw]);

                $widgetHandler->getResults(['entity' => $dw, 'user' => $user, 'rootView' => $this]);

                if ($widgetHandler->getRenderElement() == 'Search.Widgets/graph') {
                    $chartData[] = $widgetHandler->getData();
                }

                $dataOptions = $widgetHandler->getOptions();

                if (!empty($dataOptions['scripts'])) {
                    $scripts[] = $dataOptions['scripts'];
                }

                $widgetContent = $this->element(
                    $widgetHandler->getRenderElement(),
                    ['widget' => $widgetHandler],
                    ['plugin' => false]
                );

                if (empty($widgetContent)) {
                    throw new \Exception(__d('Qobo/Search', 'Widget is unavailable'));
                }
                echo $widgetContent;
            } catch (\Exception $e) {
                $this->log("Cannot process widget: " . $e->getMessage(), 'error');
                echo $this->element('Qobo/Search.missing_element', [
                    'exception' => $e,
                    'messages' => !empty($widgetHandler) ? $widgetHandler->getErrors() : ['Unknown error']
                ]);
            }
            ?>
            </div>
        <?php endforeach;?>
        </div>
    <?php endforeach; ?>
</section>
<?php echo $this->element('Qobo/Search.widget_libraries', ['scripts' => $scripts, 'chartData' => $chartData]); ?>
