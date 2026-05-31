<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/kz_multitracker.php';

dbconn(false);
kz_mt_update_due_trackers(isset($argv[1]) ? (int)$argv[1] : 25);

?>
