<?php

# IMPORTANT: Do not edit below unless you know what you are doing!
if (!defined('IN_TRACKER')) {
    die('Прямой вызов запрещён.');
}

if (!function_exists('cleanup_int_list')) {
    function cleanup_int_list(array $ids): string
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_filter($ids, static function ($id) {
            return $id > 0;
        });

        return implode(',', $ids);
    }
}

if (!function_exists('cleanup_delete_by_ids')) {
    function cleanup_delete_by_ids(string $table, string $field, array $ids): void
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if (empty($ids)) {
            return;
        }

        foreach (array_chunk($ids, 500) as $chunk) {
            $in = cleanup_int_list($chunk);

            if ($in !== '') {
                sql_query("DELETE FROM `$table` WHERE `$field` IN ($in)") or sqlerr(__FILE__, __LINE__);
            }
        }
    }
}

if (!function_exists('cleanup_delete_users')) {
    function cleanup_delete_users(array $user_ids): void
    {
        $user_ids = array_values(array_unique(array_map('intval', $user_ids)));

        if (empty($user_ids)) {
            return;
        }

        foreach (array_chunk($user_ids, 500) as $chunk) {
            $in = cleanup_int_list($chunk);

            if ($in === '') {
                continue;
            }

            sql_query("DELETE FROM messages WHERE receiver IN ($in) OR sender IN ($in)") or sqlerr(__FILE__, __LINE__);
            sql_query("DELETE FROM friends WHERE userid IN ($in) OR friendid IN ($in)") or sqlerr(__FILE__, __LINE__);
            sql_query("DELETE FROM blocks WHERE userid IN ($in) OR blockid IN ($in)") or sqlerr(__FILE__, __LINE__);
            sql_query("DELETE FROM bookmarks WHERE userid IN ($in)") or sqlerr(__FILE__, __LINE__);
            sql_query("DELETE FROM peers WHERE userid IN ($in)") or sqlerr(__FILE__, __LINE__);
            sql_query("DELETE FROM readtorrents WHERE userid IN ($in)") or sqlerr(__FILE__, __LINE__);
            sql_query("DELETE FROM simpaty WHERE fromuserid IN ($in) OR touserid IN ($in)") or sqlerr(__FILE__, __LINE__);
            sql_query("DELETE FROM checkcomm WHERE userid IN ($in)") or sqlerr(__FILE__, __LINE__);
            sql_query("DELETE FROM users WHERE id IN ($in)") or sqlerr(__FILE__, __LINE__);
        }
    }
}

if (!function_exists('cleanup_fetch_ids')) {
    function cleanup_fetch_ids(string $query, string $field = 'id'): array
    {
        $ids = [];
        $res = sql_query($query) or sqlerr(__FILE__, __LINE__);

        while ($row = mysqli_fetch_assoc($res)) {
            if (isset($row[$field])) {
                $ids[] = (int)$row[$field];
            }
        }

        return $ids;
    }
}

if (!function_exists('cleanup_unlink_file')) {
    function cleanup_unlink_file(string $file): void
    {
        if ($file !== '' && is_file($file)) {
            @unlink($file);
        }
    }
}

if (!function_exists('cleanup_acquire_lock')) {
    function cleanup_acquire_lock(): bool
    {
        global $cleanup_lock_held;
        static $shutdown_registered = false;

        $res = sql_query("SELECT GET_LOCK('kinozal_cleanup', 0) AS locked") or sqlerr(__FILE__, __LINE__);
        $row = mysqli_fetch_assoc($res);
        $locked = !empty($row['locked']);

        if ($locked) {
            $cleanup_lock_held = true;

            if (!$shutdown_registered) {
                register_shutdown_function('cleanup_release_lock');
                $shutdown_registered = true;
            }
        }

        return $locked;
    }

    function cleanup_release_lock(): void
    {
        global $cleanup_lock_held;

        if (empty($cleanup_lock_held)) {
            return;
        }

        $cleanup_lock_held = false;
        @sql_query("SELECT RELEASE_LOCK('kinozal_cleanup')");
    }
}

function docleanup()
{
    global $torrent_dir, $signup_timeout, $max_dead_torrent_time, $use_ttl, $autoclean_interval;
    global $points_per_cleanup, $ttl_days, $tracker_lang;

    if (!cleanup_acquire_lock()) {
        return false;
    }

    @set_time_limit(0);
    @ignore_user_abort(true);

    $torrent_dir = rtrim((string)$torrent_dir, '/\\');

    /*
     * 1. Синхронизация таблицы torrents и файлов .torrent.
     * Удаляем:
     * - файлы .torrent без записи в базе;
     * - записи в torrents без файла на диске;
     * - хвосты в peers/files для несуществующих торрентов.
     */
    if ($torrent_dir !== '' && is_dir($torrent_dir)) {
        $db_torrents = [];

        $res = sql_query("SELECT id FROM torrents") or sqlerr(__FILE__, __LINE__);

        while ($row = mysqli_fetch_assoc($res)) {
            $db_torrents[(int)$row['id']] = true;
        }

        $fs_torrents = [];
        $dp = @opendir($torrent_dir);

        if ($dp) {
            while (($file = readdir($dp)) !== false) {
                if (!preg_match('/^([1-9][0-9]*)\.torrent$/', $file, $m)) {
                    continue;
                }

                $id = (int)$m[1];
                $fs_torrents[$id] = true;

                if (!isset($db_torrents[$id])) {
                    cleanup_unlink_file($torrent_dir . DIRECTORY_SEPARATOR . $file);
                }
            }

            closedir($dp);
        }

        if (!empty($db_torrents)) {
            $missing_files = [];

            foreach ($db_torrents as $id => $_) {
                if (!isset($fs_torrents[$id])) {
                    $missing_files[] = $id;
                }
            }

            cleanup_delete_by_ids('torrents', 'id', $missing_files);
        }
    }

    /*
     * 2. Удаление хвостов в связанных таблицах.
     * Через LEFT JOIN быстрее и чище, чем SELECT GROUP BY + PHP-цикл.
     */
    sql_query("
        DELETE p
        FROM peers AS p
        LEFT JOIN torrents AS t ON t.id = p.torrent
        WHERE t.id IS NULL
    ") or sqlerr(__FILE__, __LINE__);

    sql_query("
        DELETE f
        FROM files AS f
        LEFT JOIN torrents AS t ON t.id = f.torrent
        WHERE t.id IS NULL
    ") or sqlerr(__FILE__, __LINE__);

    sql_query("
        DELETE cp
        FROM comments_parsed AS cp
        LEFT JOIN comments AS c ON c.id = cp.cid
        WHERE c.id IS NULL
    ") or sqlerr(__FILE__, __LINE__);

    sql_query("
        DELETE td
        FROM torrent_details AS td
        LEFT JOIN torrents AS t ON t.id = td.tid
        WHERE t.id IS NULL
    ") or sqlerr(__FILE__, __LINE__);

    sql_query("
        DELETE td
        FROM torrents_descr AS td
        LEFT JOIN torrents AS t ON t.id = td.tid
        WHERE t.id IS NULL
    ") or sqlerr(__FILE__, __LINE__);

    sql_query("
        DELETE tt
        FROM torrent_trackers AS tt
        LEFT JOIN torrents AS t ON t.id = tt.torrentid
        WHERE t.id IS NULL
    ") or sqlerr(__FILE__, __LINE__);

    sql_query("
        DELETE th
        FROM thanks AS th
        LEFT JOIN torrents AS t ON t.id = th.torrentid
        WHERE t.id IS NULL
    ") or sqlerr(__FILE__, __LINE__);

    sql_query("
        DELETE gt
        FROM groupex_torrents AS gt
        LEFT JOIN torrents AS t ON t.id = gt.torrent_id
        WHERE t.id IS NULL
    ") or sqlerr(__FILE__, __LINE__);

    sql_query("
        DELETE ir
        FROM indexreleases AS ir
        LEFT JOIN torrents AS t ON t.id = ir.torrentid
        WHERE t.id IS NULL
    ") or sqlerr(__FILE__, __LINE__);

    sql_query("
        DELETE utd
        FROM user_torrent_downloads AS utd
        LEFT JOIN torrents AS t ON t.id = utd.torrent
        WHERE t.id IS NULL
    ") or sqlerr(__FILE__, __LINE__);

    sql_query("
        DELETE rt
        FROM readtorrents AS rt
        LEFT JOIN torrents AS t ON t.id = rt.torrentid
        WHERE t.id IS NULL
    ") or sqlerr(__FILE__, __LINE__);

    /*
     * 3. Удаление мёртвых пиров и обновление snatched.
     */
    $deadtime = (int)deadtime();

    sql_query("DELETE FROM peers WHERE last_action < FROM_UNIXTIME($deadtime)") or sqlerr(__FILE__, __LINE__);

    sql_query("
        UPDATE snatched
        SET seeder = 'no'
        WHERE seeder = 'yes'
          AND last_action < FROM_UNIXTIME($deadtime)
    ") or sqlerr(__FILE__, __LINE__);

    /*
     * 4. Пересчёт seeders/leechers/comments одним запросом.
     * Раньше было: SELECT агрегатов + SELECT всех торрентов + UPDATE каждого изменённого торрента.
     */
    sql_query("
        UPDATE torrents AS t
        LEFT JOIN (
            SELECT
                torrent,
                SUM(seeder = 'yes') AS seeders,
                SUM(seeder != 'yes') AS leechers
            FROM peers
            GROUP BY torrent
        ) AS p ON p.torrent = t.id
        LEFT JOIN (
            SELECT torrent, COUNT(*) AS comments
            FROM comments
            GROUP BY torrent
        ) AS c ON c.torrent = t.id
        SET
            t.seeders = COALESCE(p.seeders, 0),
            t.leechers = COALESCE(p.leechers, 0),
            t.comments = COALESCE(c.comments, 0)
        WHERE
            t.seeders != COALESCE(p.seeders, 0)
            OR t.leechers != COALESCE(p.leechers, 0)
            OR t.comments != COALESCE(c.comments, 0)
    ") or sqlerr(__FILE__, __LINE__);

    /*
     * 5. Удаление неактивных пользователей.
     */
    $maxclass = (int)UC_POWER_USER;

    $inactive_dt = sqlesc(get_date_time(gmtime() - 31 * 86400));
    $inactive_users = cleanup_fetch_ids("
        SELECT id
        FROM users
        WHERE parked = 'no'
          AND status = 'confirmed'
          AND class <= $maxclass
          AND last_access IS NOT NULL
          AND last_access > '1000-01-01 00:00:00'
          AND last_access < $inactive_dt
    ");

    cleanup_delete_users($inactive_users);

    /*
     * 6. Удаление parked-пользователей.
     */
    $parked_dt = sqlesc(get_date_time(gmtime() - 175 * 86400));
    $parked_users = cleanup_fetch_ids("
        SELECT id
        FROM users
        WHERE parked = 'yes'
          AND status = 'confirmed'
          AND class <= $maxclass
          AND last_access IS NOT NULL
          AND last_access > '1000-01-01 00:00:00'
          AND last_access < $parked_dt
    ");

    cleanup_delete_users($parked_users);

    /*
     * 7. Удаление неподтверждённых пользователей по таймауту регистрации.
     */
    $signup_deadtime = (int)TIMENOW - (int)$signup_timeout;

    $pending_users = cleanup_fetch_ids("
        SELECT id
        FROM users
        WHERE status = 'pending'
          AND added IS NOT NULL
          AND added > '1000-01-01 00:00:00'
          AND added < FROM_UNIXTIME($signup_deadtime)
          AND last_login IS NOT NULL
          AND last_login > '1000-01-01 00:00:00'
          AND last_login < FROM_UNIXTIME($signup_deadtime)
          AND last_access IS NOT NULL
          AND last_access > '1000-01-01 00:00:00'
          AND last_access < FROM_UNIXTIME($signup_deadtime)
    ");

    cleanup_delete_users($pending_users);

    /*
     * 8. Seed bonus.
     * DISTINCT защищает от лишних повторов userid в peers.
     */
    $points_per_cleanup = (float)$points_per_cleanup;

    if ($points_per_cleanup > 0) {
        sql_query("
            UPDATE users AS u
            INNER JOIN (
                SELECT DISTINCT userid
                FROM peers
                WHERE seeder = 'yes'
                  AND userid > 0
            ) AS p ON p.userid = u.id
            SET u.bonus = u.bonus + $points_per_cleanup
        ") or sqlerr(__FILE__, __LINE__);
    }

    /*
     * 9. Снятие истёкших предупреждений.
     */
    $now = sqlesc(get_date_time());
    $today = date('Y-m-d');

    $warn_modcomment = sqlesc($today . " - Предупреждение снято системой по таймауту.\n");
    $warn_msg = sqlesc("Ваше предупреждение снято по таймауту. Постарайтесь больше не получать предупреждений и соблюдать правила.\n");

    sql_query("
        INSERT INTO messages (sender, receiver, added, msg, poster)
        SELECT 0, id, $now, $warn_msg, 0
        FROM users
        WHERE warned = 'yes'
          AND warneduntil IS NOT NULL
          AND warneduntil > '1000-01-01 00:00:00'
          AND warneduntil < NOW()
    ") or sqlerr(__FILE__, __LINE__);

    sql_query("
        UPDATE users
        SET
            warned = 'no',
            warneduntil = NULL,
            modcomment = CONCAT($warn_modcomment, modcomment)
        WHERE warned = 'yes'
          AND warneduntil IS NOT NULL
          AND warneduntil > '1000-01-01 00:00:00'
          AND warneduntil < NOW()
    ") or sqlerr(__FILE__, __LINE__);

    /*
     * 10. Снятие истёкших банов.
     */
    $ban_modcomment = sqlesc($today . " - Включен системой по истечению бана.\n");

    sql_query("
        UPDATE users AS u
        INNER JOIN users_ban AS b ON b.userid = u.id
        SET
            u.enabled = 'yes',
            u.modcomment = CONCAT($ban_modcomment, u.modcomment)
        WHERE b.disuntil IS NOT NULL
          AND b.disuntil > '1000-01-01 00:00:00'
          AND b.disuntil < NOW()
    ") or sqlerr(__FILE__, __LINE__);

    sql_query("
        DELETE FROM users_ban
        WHERE disuntil IS NOT NULL
          AND disuntil > '1000-01-01 00:00:00'
          AND disuntil < NOW()
    ") or sqlerr(__FILE__, __LINE__);

    /*
     * 11. Автоповышение до Power User.
     */
    $limit = 25 * 1024 * 1024 * 1024;
    $minratio = 1.05;
    $power_maxdt = sqlesc(get_date_time(gmtime() - 86400 * 28));
    $subject = sqlesc('Вы были повышены');
    $msg = sqlesc('Наши поздравления, вы были авто-повышены до ранга [b]Опытный Зритель[/b].');
    $class_power = isset($tracker_lang['class_power_user']) ? $tracker_lang['class_power_user'] : get_user_class_name(UC_POWER_USER);
    $modcomment = sqlesc($today . ' - Повышен до уровня "' . $class_power . "\" системой.\n");

    $ratio_where = "(downloaded = 0 OR uploaded / downloaded >= $minratio)";

    sql_query("
        INSERT INTO messages (sender, receiver, added, msg, poster, subject)
        SELECT 0, id, $now, $msg, 0, $subject
        FROM users
        WHERE class = " . (int)UC_USER . "
          AND status = 'confirmed'
          AND enabled = 'yes'
          AND uploaded >= $limit
          AND $ratio_where
          AND added < $power_maxdt
    ") or sqlerr(__FILE__, __LINE__);

    sql_query("
        UPDATE users
        SET
            class = " . (int)UC_POWER_USER . ",
            modcomment = CONCAT($modcomment, modcomment)
        WHERE class = " . (int)UC_USER . "
          AND status = 'confirmed'
          AND enabled = 'yes'
          AND uploaded >= $limit
          AND $ratio_where
          AND added < $power_maxdt
    ") or sqlerr(__FILE__, __LINE__);

    /*
     * 12. Автоповышение старых пользователей до Honor User.
     */
    $honor_maxdt = sqlesc(get_date_time(gmtime() - 86400 * 365 * 3));
    $msg = sqlesc('Наши поздравления, вы были авто-повышены до ранга [b]Заслуженный Зритель[/b].');
    $subject = sqlesc('Вы были повышены');
    $modcomment = sqlesc($today . ' - Повышен до уровня "' . get_user_class_name(UC_HONOR_USER) . "\" системой.\n");

    sql_query("
        INSERT INTO messages (sender, receiver, added, msg, poster, subject)
        SELECT 0, id, $now, $msg, 0, $subject
        FROM users
        WHERE class IN (" . (int)UC_USER . ", " . (int)UC_POWER_USER . ")
          AND status = 'confirmed'
          AND enabled = 'yes'
          AND added < $honor_maxdt
    ") or sqlerr(__FILE__, __LINE__);

    sql_query("
        UPDATE users
        SET
            class = " . (int)UC_HONOR_USER . ",
            modcomment = CONCAT($modcomment, modcomment)
        WHERE class IN (" . (int)UC_USER . ", " . (int)UC_POWER_USER . ")
          AND status = 'confirmed'
          AND enabled = 'yes'
          AND added < $honor_maxdt
    ") or sqlerr(__FILE__, __LINE__);

    /*
     * 13. Автопонижение Power User.
     */
    $minratio = 0.95;
    $msg = sqlesc("Вы были авто-понижены с ранга [b]Опытный Зритель[/b] до ранга [b]Зритель[/b] потому что ваш рейтинг упал ниже [b]{$minratio}[/b].");
    $subject = sqlesc('Вы были понижены');
    $class_user = isset($tracker_lang['class_user']) ? $tracker_lang['class_user'] : get_user_class_name(UC_USER);
    $modcomment = sqlesc($today . ' - Понижен до уровня "' . $class_user . "\" системой.\n");

    sql_query("
        INSERT INTO messages (sender, receiver, added, msg, poster, subject)
        SELECT 0, id, $now, $msg, 0, $subject
        FROM users
        WHERE class = " . (int)UC_POWER_USER . "
          AND downloaded > 0
          AND uploaded / downloaded < $minratio
    ") or sqlerr(__FILE__, __LINE__);

    sql_query("
        UPDATE users
        SET
            class = " . (int)UC_USER . ",
            modcomment = CONCAT($modcomment, modcomment)
        WHERE class = " . (int)UC_POWER_USER . "
          AND downloaded > 0
          AND uploaded / downloaded < $minratio
    ") or sqlerr(__FILE__, __LINE__);

    /*
     * 14. Удаление старых торрентов по TTL.
     */
    if (!empty($use_ttl)) {
        $ttl_days = (int)$ttl_days;

        if ($ttl_days > 0) {
            $ttl_dt = sqlesc(get_date_time(gmtime() - ($ttl_days * 86400)));

            $res = sql_query("
                SELECT id, name, image1, image2, image3, image4, image5
                FROM torrents
                WHERE added < $ttl_dt
            ") or sqlerr(__FILE__, __LINE__);

            $ttl_ids = [];

            while ($arr = mysqli_fetch_assoc($res)) {
                $id = (int)$arr['id'];
                $ttl_ids[] = $id;

                cleanup_unlink_file($torrent_dir . DIRECTORY_SEPARATOR . $id . '.torrent');

                for ($x = 1; $x <= 5; $x++) {
                    $image = isset($arr['image' . $x]) ? trim((string)$arr['image' . $x]) : '';

                    if ($image !== '') {
                        cleanup_unlink_file(ROOT_PATH . 'torrents/images/' . basename($image));
                    }
                }

                write_log(
                    'Торрент ' . $id . ' (' . $arr['name'] . ') был удален системой (старше чем ' . $ttl_days . ' дней)',
                    '',
                    'torrent'
                );
            }

            cleanup_delete_by_ids('snatched', 'torrent', $ttl_ids);
            cleanup_delete_by_ids('peers', 'torrent', $ttl_ids);
            foreach (array_chunk($ttl_ids, 500) as $chunk) {
                $in = cleanup_int_list($chunk);

                if ($in !== '') {
                    sql_query("DELETE cp FROM comments_parsed AS cp INNER JOIN comments AS c ON c.id = cp.cid WHERE c.torrent IN ($in)") or sqlerr(__FILE__, __LINE__);
                }
            }
            cleanup_delete_by_ids('comments', 'torrent', $ttl_ids);
            cleanup_delete_by_ids('files', 'torrent', $ttl_ids);
            cleanup_delete_by_ids('ratings', 'torrent', $ttl_ids);
            cleanup_delete_by_ids('bookmarks', 'torrentid', $ttl_ids);
            cleanup_delete_by_ids('readtorrents', 'torrentid', $ttl_ids);
            cleanup_delete_by_ids('thanks', 'torrentid', $ttl_ids);
            cleanup_delete_by_ids('torrent_trackers', 'torrentid', $ttl_ids);
            cleanup_delete_by_ids('torrents_descr', 'tid', $ttl_ids);
            cleanup_delete_by_ids('torrent_details', 'tid', $ttl_ids);
            cleanup_delete_by_ids('groupex_torrents', 'torrent_id', $ttl_ids);
            cleanup_delete_by_ids('indexreleases', 'torrentid', $ttl_ids);
            cleanup_delete_by_ids('user_torrent_downloads', 'torrent', $ttl_ids);

            foreach (array_chunk($ttl_ids, 500) as $chunk) {
                $in = cleanup_int_list($chunk);

                if ($in !== '') {
                    sql_query("DELETE FROM checkcomm WHERE torrent = 1 AND checkid IN ($in)") or sqlerr(__FILE__, __LINE__);
                    sql_query("DELETE FROM torrents WHERE id IN ($in)") or sqlerr(__FILE__, __LINE__);
                }
            }
        }
    }

    sql_query("
        UPDATE users
        SET editsecret = '',
            editsecret_expires = NULL
        WHERE editsecret_expires IS NOT NULL
          AND editsecret_expires < NOW()
    ") or sqlerr(__FILE__, __LINE__);

    if (function_exists('tracker_user_passkeys_available') && tracker_user_passkeys_available()) {
        sql_query("
            DELETE FROM user_passkeys
            WHERE revoked_at IS NOT NULL
              AND revoked_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ") or sqlerr(__FILE__, __LINE__);
    }

    $session_deadtime = time() - 3600;
    sql_query("DELETE FROM sessions WHERE time < $session_deadtime") or sqlerr(__FILE__, __LINE__);

    /*
     * 16. Автообновление cups, если модуль есть.
     */
    if (function_exists('cups_update_auto')) {
        cups_update_auto(false);
    }

    cleanup_release_lock();
    return true;
}

?>
