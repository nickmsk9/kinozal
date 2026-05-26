<?php

if (!defined('BLOCK_FILE')) {
    header('Location: ../index.php');
    exit;
}

global $content;

$blocktitle = 'Статистика трекера';
$content = '';

$users_res = sql_query("
    SELECT
        COUNT(*) AS users_total,
        SUM(CASE WHEN gender = '2' THEN 1 ELSE 0 END) AS girls_total,
        SUM(CASE WHEN class = " . UC_UPLOADER . " THEN 1 ELSE 0 END) AS uploaders_total
    FROM users
    WHERE status = 'confirmed'
");

$torrents_res = sql_query("
    SELECT
        COUNT(*) AS torrents_total,
        COALESCE(SUM(seeders), 0) AS seeders_total,
        COALESCE(SUM(leechers), 0) AS leechers_total
    FROM torrents
    WHERE visible = 'yes' AND banned != 'yes'
");

$users_stats = $users_res ? mysqli_fetch_assoc($users_res) : array();
$torrents_stats = $torrents_res ? mysqli_fetch_assoc($torrents_res) : array();

$stats = array(
    'Зрителей в зале' => number_format((int)($users_stats['users_total'] ?? 0)),
    'Девочек' => number_format((int)($users_stats['girls_total'] ?? 0)),
    'Кинооператоров' => number_format((int)($users_stats['uploaders_total'] ?? 0)),
    'Раздач' => number_format((int)($torrents_stats['torrents_total'] ?? 0)),
    'Сидов' => number_format((int)($torrents_stats['seeders_total'] ?? 0)),
    'Пиров' => number_format((int)($torrents_stats['leechers_total'] ?? 0)),
);

$content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0">';

foreach ($stats as $label => $value) {
    $content .= '
    <tr>
        <td class="embedded"><b>' . $label . '</b></td>
        <td class="embedded" align="right">' . $value . '</td>
    </tr>';
}

$content .= '</table>';

?>
