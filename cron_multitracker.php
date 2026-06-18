<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/multitracker.php';

dbconn(false);
multitracker_update_due_trackers(isset($argv[1]) ? (int)$argv[1] : 50, isset($argv[2]) ? (int)$argv[2] : 4);

?>
