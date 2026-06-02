<?php

if (!defined('BLOCK_FILE')) {
    header('Location: ../index.php');
    exit;
}

global $content;

$blocktitle = 'Топ раздач';
$content = '';

$rows = isset($GLOBALS['index_top_torrents']) && is_array($GLOBALS['index_top_torrents'])
    ? $GLOBALS['index_top_torrents']
    : null;

if ($rows === null) {
    require_once(dirname(__DIR__) . '/include/test_torrents.php');
    test_torrents_ensure_schema();

    $rows = function_exists('tracker_cache_remember')
        ? tracker_cache_remember('block:top-torrents:rows', 60, function () {
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

            $cached_rows = array();
            while ($row = mysqli_fetch_assoc($res)) {
                $cached_rows[] = $row;
            }

            return $cached_rows;
        })
        : null;

    if ($rows === null) {
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

        $rows = array();
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = $row;
        }
    }
}

if (!$rows) {
    $content .= '<li class="small">раздач пока что нет</li>';
    return;
}

foreach ($rows as $row) {
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
