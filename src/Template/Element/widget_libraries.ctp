<?php
$duplicates = [];
if (!empty($scripts)) {
    foreach ($scripts as $script) {
        $checksum = md5(serialize($script['post']));

        if (in_array($checksum, $duplicates)) {
            continue;
        }

        $duplicates[] = $checksum;

        foreach ($script['post'] as $type => $item) {
            if (empty($item['type']) || empty($item['content'])) {
                continue;
            }

            if (!method_exists($this->Html, $item['type'])) {
                continue;
            }

            echo $this->Html->{$item['type']}($item['content'], [
                'block' => !empty($item['block']) ? $item['block'] : true
            ]);
        }
    }

    echo $this->Html->script('Search.reportGraphs', ['block' => 'scriptBottom']);
}

if (isset($chartData) && !empty($chartData)) {
    echo $this->Html->scriptBlock('var chartsData = ' . json_encode($chartData) . ';');
}
