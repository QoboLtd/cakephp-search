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
    echo $this->Html->scriptBlock('
        var chartsData = chartsData || [];
        chartsData = chartsData.concat(' . json_encode($chartData) . ');
    ');
}
