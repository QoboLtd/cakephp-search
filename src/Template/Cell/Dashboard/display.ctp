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
<?php for ($i = 0; $i < $gridColumns; $i++) : ?>
    <div class="col-md-6">
    <?php if (!empty($savedSearches[$i])) : ?>
        <?php for ($x = 0; $x < $gridRows; $x++) : ?>
            <?php if (!empty($savedSearches[$i][$x])) : ?>
                <?= $this->element('Search.search_results', ['savedSearch' => $savedSearches[$i][$x]]); ?>
            <?php endif; ?>
        <?php endfor; ?>
    <?php endif; ?>
    </div>
<?php endfor; ?>
