<?php

if (!defined('BLOCK_FILE')) {
    header('Location: ../index.php');
    exit;
}

global $content;

$blocktitle = 'День рождения';
$content = '';

$users = array();
$rows = isset($GLOBALS['index_birthdays']) && is_array($GLOBALS['index_birthdays'])
    ? $GLOBALS['index_birthdays']
    : null;

if ($rows === null) {
    $today = date('m-d');

    $rows = function_exists('tracker_cache_remember')
        ? tracker_cache_remember('block:birthday:rows:' . date('Ymd'), 1800, function () use ($today) {
            $res = sql_query("
                SELECT id, username, class
                FROM users
                WHERE status = 'confirmed'
                  AND enabled = 'yes'
                  AND birthday IS NOT NULL
                  AND DATE_FORMAT(birthday, '%m-%d') = " . sqlesc($today) . "
                ORDER BY class DESC, username ASC
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
            SELECT id, username, class
            FROM users
            WHERE status = 'confirmed'
              AND enabled = 'yes'
              AND birthday IS NOT NULL
              AND DATE_FORMAT(birthday, '%m-%d') = " . sqlesc($today) . "
            ORDER BY class DESC, username ASC
        ") or sqlerr(__FILE__, __LINE__);

        $rows = array();
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = $row;
        }
    }
}

foreach ($rows as $row) {
    $users[] = '<a href="/userdetails.php?id=' . (int)$row['id'] . '" class="u' . (int)$row['class'] . '">'
        . htmlspecialchars_uni($row['username'])
        . '</a>';
}

if ($users) {
    $content .= '<li class="justify small">' . implode(', ', $users) . '</li>';
} else {
    $content .= '<li class="justify small">именинников сегодня нет</li>';
}

?>
