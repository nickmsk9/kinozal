<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/include/bittorrent.php';

dbconn(false, true);

require_once ROOT_PATH . 'include/cleanup.php';

$start_queries = isset($queries) ? (int)$queries : 0;
$started_at = microtime(true);
$result = docleanup();
$elapsed = microtime(true) - $started_at;
$end_queries = isset($queries) ? (int)$queries : $start_queries;

if ($result === false) {
    fwrite(STDOUT, "cleanup skipped: lock is already held\n");
    exit(2);
}

fwrite(
    STDOUT,
    'cleanup done: ' . ($end_queries - $start_queries) . ' queries, ' . number_format($elapsed, 3, '.', '') . "s\n"
);

