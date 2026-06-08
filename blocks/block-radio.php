<?php

if (!defined('BLOCK_FILE')) {
    header('Location: ../index.php');
    exit;
}

$blocktitle = 'Радио Кинозал';
$content = '<div class="center">'
    . '<a href="/radio.php" title="Радио Кинозал.ТВ">'
    . '<img src="/pic/radio_ban.jpg" height="57" class="w190 block" alt="Радио Кинозал.ТВ">'
    . '</a>'
    . '</div>';
