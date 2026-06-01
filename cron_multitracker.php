<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/multitracker.php';

dbconn(false);
multitracker_update_due_trackers(isset($argv[1]) ? (int)$argv[1] : 25);

?>
