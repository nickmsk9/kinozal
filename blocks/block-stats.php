<?php

if (!defined('BLOCK_FILE')) {
    header('Location: ../index.php');
    exit;
}

global $content;

$blocktitle = 'Статистика трекера';
$content = '';

$stats_row = isset($GLOBALS['index_stats']) && is_array($GLOBALS['index_stats'])
    ? $GLOBALS['index_stats']
    : array();

if (!$stats_row) {
    require_once(dirname(__DIR__) . '/include/test_torrents.php');
    test_torrents_ensure_schema();

    $stats_row = function_exists('tracker_cache_remember')
        ? tracker_cache_remember('block:stats:row', 60, function () {
            $stats_res = sql_query("
                SELECT
                    (SELECT COUNT(*) FROM users WHERE status = 'confirmed') AS users_total,
                    (SELECT COUNT(*) FROM users WHERE status = 'confirmed' AND gender = '2') AS girls_total,
                    (SELECT COUNT(*) FROM users WHERE status = 'confirmed' AND class = " . (int)UC_UPLOADER . ") AS uploaders_total,
                    (SELECT COUNT(*) FROM torrents WHERE visible = 'yes' AND banned != 'yes' AND (is_test <> 'yes' OR test_approved_at IS NOT NULL)) AS torrents_total,
                    (SELECT COALESCE(SUM(seeders), 0) FROM torrents WHERE visible = 'yes' AND banned != 'yes' AND (is_test <> 'yes' OR test_approved_at IS NOT NULL)) AS seeders_total,
                    (SELECT COALESCE(SUM(leechers), 0) FROM torrents WHERE visible = 'yes' AND banned != 'yes' AND (is_test <> 'yes' OR test_approved_at IS NOT NULL)) AS leechers_total
            ");

            return $stats_res ? mysqli_fetch_assoc($stats_res) : array();
        })
        : array();

    if (!$stats_row) {
        $stats_res = sql_query("
            SELECT
                (SELECT COUNT(*) FROM users WHERE status = 'confirmed') AS users_total,
                (SELECT COUNT(*) FROM users WHERE status = 'confirmed' AND gender = '2') AS girls_total,
                (SELECT COUNT(*) FROM users WHERE status = 'confirmed' AND class = " . (int)UC_UPLOADER . ") AS uploaders_total,
                (SELECT COUNT(*) FROM torrents WHERE visible = 'yes' AND banned != 'yes' AND (is_test <> 'yes' OR test_approved_at IS NOT NULL)) AS torrents_total,
                (SELECT COALESCE(SUM(seeders), 0) FROM torrents WHERE visible = 'yes' AND banned != 'yes' AND (is_test <> 'yes' OR test_approved_at IS NOT NULL)) AS seeders_total,
                (SELECT COALESCE(SUM(leechers), 0) FROM torrents WHERE visible = 'yes' AND banned != 'yes' AND (is_test <> 'yes' OR test_approved_at IS NOT NULL)) AS leechers_total
        ");

        $stats_row = $stats_res ? mysqli_fetch_assoc($stats_res) : array();
    }
}

$stats = array(
    'Зрителей в зале' => (int)($stats_row['users_total'] ?? 0),
    'Девочек' => (int)($stats_row['girls_total'] ?? 0),
    'Кинооператоров' => (int)($stats_row['uploaders_total'] ?? 0),
    'Раздач' => (int)($stats_row['torrents_total'] ?? 0),
    'Сидов' => (int)($stats_row['seeders_total'] ?? 0),
    'Пиров' => (int)($stats_row['leechers_total'] ?? 0),
);

$content .= '
<ul style="
    margin:0;
    padding:0 3px 1px 3px;
    list-style:none;
">';

foreach ($stats as $label => $value) {
    $content .= '
        <li class="small" style="
            clear:both;
            margin:0;
            padding:0;
            height:14px;
            line-height:14px;
            overflow:hidden;
        ">
            <b>' . htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</b>
            <span class="floatright">' . number_format($value) . '</span>
        </li>';
}

$content .= '</ul>';

?>
