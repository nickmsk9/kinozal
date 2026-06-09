<?php

require_once __DIR__ . '/include/bittorrent.php';

dbconn(false);
loggedinorreturn();

header('Location: /pay.php', true, 302);
exit;
