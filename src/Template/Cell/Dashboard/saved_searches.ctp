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
<div class="row">
<?php foreach (array_keys($dashboardLayout) as $col) : ?>
    <div class="col-xs-6">
        <p class="h3 text-center"><?= $columns[$col] ?></p>
        <ul class="savetrue droppable-area" data-column=<?= $col ?>>
        <?php if (!empty($dashboardSavedSearches[$col])) : ?>
        <?php foreach ($dashboardSavedSearches[$col] as $rows) : ?>
            <?php foreach ($rows as $row) : ?>
             <li class="droppable" data-id="<?= $row->id ?>" data-type="saved_search">
                <div class="header">
                <?php
                    list($plugin, $model) = pluginSplit($row->model);
                    echo $model;
                ?>
                </div>
                <div class="content"><?= $row->name ?></div>
            </li>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <?php endif ?>
        </ul>
    </div>
<?php endforeach; ?>
</div>
<div class="row">
    <div class="col-xs-12">
        <p class="h3 text-center saved-searches-title"><?= __('Saved Searches') ?></p>
        <ul class="list-inline droppable-area saved-searches-area">
            <?php foreach ($allSavedSearches as $savedSearch) : ?>
            <li class="droppable col-xs-1" data-id="<?= $savedSearch['id'] ?>" data-type="saved_search">
                <div class="header">
                <?php
                    list($plugin, $model) = pluginSplit($savedSearch['model']);
                    echo $model;
                ?>
                </div>
                <div class="content"><?= $savedSearch['name'] ?></div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
