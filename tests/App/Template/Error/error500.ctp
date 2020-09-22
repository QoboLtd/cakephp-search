<?php
use Cake\Routing\Router;
?>
<b><?= 'Error' ?> <?= h($code) ?>: <?= h($message) ?></b>
<b>URL: </b><?= h(Router::url($url, true)) ?>
