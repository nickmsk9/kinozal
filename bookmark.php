<?php

require_once __DIR__ . '/include/bittorrent.php';

dbconn();
loggedinorreturn();
tracker_require_form_token('GET');

if (!function_exists('bookmarks_h')) {
    function bookmarks_h($value): string
    {
        if (function_exists('htmlspecialchars_uni')) {
            return htmlspecialchars_uni((string)$value);
        }

        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('bookmarks_message')) {
    function bookmarks_message(string $msg, bool $error = true): void
    {
        global $tracker_lang;

        $title = $error
            ? ($tracker_lang['error'] ?? 'Ошибка')
            : ($tracker_lang['success'] ?? 'Успешно');

        $caption = $error
            ? ($tracker_lang['error'] ?? 'Ошибка')
            : ($tracker_lang['success'] ?? 'Успешно');

        stdhead($title);
        stdmsg($caption, $msg, $error ? 'error' : 'success');
        stdfoot();

        exit;
    }
}

if (!function_exists('bookmarks_redirect')) {
    function bookmarks_redirect(int $torrentId): void
    {
        header('Location: details.php?id=' . $torrentId);
        exit;
    }
}

$id = isset($_GET['torrent']) ? (int)$_GET['torrent'] : 0;

if (!is_valid_id($id)) {
    bookmarks_message($tracker_lang['torrent_not_selected'] ?? 'Торрент не выбран.');
}

$userId = (int)$CURUSER['id'];

$res = sql_query("
    SELECT `id`, `name`
    FROM `torrents`
    WHERE `id` = " . $id . "
    LIMIT 1
") or sqlerr(__FILE__, __LINE__);

$torrent = mysqli_fetch_assoc($res);

if (!$torrent) {
    bookmarks_message($tracker_lang['torrent_not_found'] ?? 'Торрент не найден.');
}

$torrentName = (string)$torrent['name'];

$exists = sql_query("
    SELECT `id`
    FROM `bookmarks`
    WHERE `userid` = " . $userId . "
      AND `torrentid` = " . $id . "
    LIMIT 1
") or sqlerr(__FILE__, __LINE__);

if (mysqli_num_rows($exists) > 0) {
    bookmarks_message(
        ($tracker_lang['torrent'] ?? 'Торрент') .
        ' "' . bookmarks_h($torrentName) . '" ' .
        ($tracker_lang['already_bookmarked'] ?? 'уже находится в закладках.')
    );
}

sql_query("
    INSERT INTO `bookmarks`
        (`userid`, `torrentid`)
    VALUES
        (" . $userId . ", " . $id . ")
") or sqlerr(__FILE__, __LINE__);

bookmarks_message(
    ($tracker_lang['torrent'] ?? 'Торрент') .
    ' "' . bookmarks_h($torrentName) . '" ' .
    ($tracker_lang['bookmarked'] ?? 'добавлен в закладки.') .
    '<br><br><a href="details.php?id=' . $id . '">Вернуться к раздаче</a>',
    false
);

?>
