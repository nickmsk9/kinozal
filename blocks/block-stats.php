<?php

if (!defined('BLOCK_FILE')) {
    header('Location: ../index.php');
    exit;
}

global $content;

$blocktitle = 'Статистика трекера';
$content = '';

$stats_res = sql_query("
    SELECT
        (SELECT COUNT(*) FROM users WHERE status = 'confirmed') AS users_total,
        (SELECT COUNT(*) FROM users WHERE status = 'confirmed' AND gender = '2') AS girls_total,
        (SELECT COUNT(*) FROM users WHERE status = 'confirmed' AND class = " . UC_UPLOADER . ") AS uploaders_total,
        (SELECT COUNT(*) FROM torrents WHERE visible = 'yes' AND banned != 'yes') AS torrents_total,
        (SELECT COALESCE(SUM(seeders), 0) FROM torrents WHERE visible = 'yes' AND banned != 'yes') AS seeders_total,
        (SELECT COALESCE(SUM(leechers), 0) FROM torrents WHERE visible = 'yes' AND banned != 'yes') AS leechers_total
");

$stats_row = $stats_res ? mysqli_fetch_assoc($stats_res) : array();

$stats = array(
    'Зрителей в зале' => number_format((int)($stats_row['users_total'] ?? 0)),
    'Девочек' => number_format((int)($stats_row['girls_total'] ?? 0)),
    'Кинооператоров' => number_format((int)($stats_row['uploaders_total'] ?? 0)),
    'Раздач' => number_format((int)($stats_row['torrents_total'] ?? 0)),
    'Сидов' => number_format((int)($stats_row['seeders_total'] ?? 0)),
    'Пиров' => number_format((int)($stats_row['leechers_total'] ?? 0)),
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
