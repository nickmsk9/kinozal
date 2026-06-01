<?php

if (!defined('BLOCK_FILE')) {
    header('Location: ../index.php');
    exit;
}

require_once(dirname(__DIR__) . '/include/kz_test_torrents.php');
kz_test_torrents_ensure_schema();

global $content;

$blocktitle = 'Топ раздач';
$content = '';

$res = sql_query("
    SELECT
        id,
        name,
        seeders + remote_seeders AS seeders
    FROM torrents
    WHERE visible = 'yes'
      AND banned != 'yes'
      AND (is_test <> 'yes' OR test_approved_at IS NOT NULL)
    ORDER BY seeders DESC, leechers + remote_leechers DESC, added DESC, id DESC
    LIMIT 10
") or sqlerr(__FILE__, __LINE__);

if (mysqli_num_rows($res) < 1) {
    $content .= '<li class="small">раздач пока что нет</li>';
    return;
}

while ($row = mysqli_fetch_assoc($res)) {
    $id = (int)$row['id'];
    $name = htmlspecialchars_uni($row['name']);
    $shortName = htmlspecialchars_uni(cut_text($row['name'], 24));
    $seeders = number_format((int)$row['seeders']);

    $content .= '<li class="small">'
        . '<a href="/details.php?id=' . $id . '&amp;hit=1" title="' . $name . '">' . $shortName . '</a>'
        . '<span class="floatright">' . $seeders . '</span>'
        . '</li>';
}

?>
