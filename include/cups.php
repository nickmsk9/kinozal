<?php

if (!defined('IN_TRACKER')) {
    die('Hacking attempt!');
}

function kz_cups_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function kz_cups_catalog()
{
    return array(
        1 => array('id' => 1, 'cup_key' => 'best_release', 'title' => 'Кубок за лучшую раздачу', 'profile_title' => 'За самую лучшую раздачу', 'icon' => 'cb1', 'sort' => 1),
        2 => array('id' => 2, 'cup_key' => 'popular_release', 'title' => 'Кубок за популярную раздачу', 'profile_title' => 'За популярную раздачу', 'icon' => 'cb2', 'sort' => 2),
        3 => array('id' => 3, 'cup_key' => 'active_seeder', 'title' => 'Кубок самому активному раздающему', 'profile_title' => 'Самому активному раздающему', 'icon' => 'cb3', 'sort' => 3),
        4 => array('id' => 4, 'cup_key' => 'discussed_release', 'title' => 'Кубок за самую обсуждаемую раздачу', 'profile_title' => 'За самую обсуждаемую раздачу', 'icon' => 'cb4', 'sort' => 4),
        5 => array('id' => 5, 'cup_key' => 'best_commentator', 'title' => 'Кубок лучшему комментатору', 'profile_title' => 'Лучшему комментатору', 'icon' => 'cb5', 'sort' => 5),
        6 => array('id' => 6, 'cup_key' => 'active_patron', 'title' => 'Кубок активному Меценату', 'profile_title' => 'Активному Меценату', 'icon' => 'cb6', 'sort' => 6),
        7 => array('id' => 7, 'cup_key' => 'best_patron', 'title' => 'Кубок лучшему Меценату', 'profile_title' => 'Лучшему Меценату', 'icon' => 'cb7', 'sort' => 7),
        8 => array('id' => 8, 'cup_key' => 'best_dj', 'title' => 'Кубок лучшему ДиДжею', 'profile_title' => 'Лучшему ДиДжею', 'icon' => 'cb8', 'sort' => 8),
    );
}

function kz_cups_ensure_schema()
{
    return;
}

function kz_cups_install_schema()
{
    sql_query("
        CREATE TABLE IF NOT EXISTS cups (
            id TINYINT UNSIGNED NOT NULL,
            cup_key VARCHAR(40) NOT NULL,
            title VARCHAR(100) NOT NULL,
            profile_title VARCHAR(100) NOT NULL,
            icon VARCHAR(16) NOT NULL DEFAULT '🏆',
            sort INT UNSIGNED NOT NULL DEFAULT 0,
            active TINYINT UNSIGNED NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY cup_key (cup_key),
            KEY sort (sort),
            KEY active (active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ") or sqlerr(__FILE__, __LINE__);

    sql_query("
        CREATE TABLE IF NOT EXISTS user_cups (
            cup_id TINYINT UNSIGNED NOT NULL,
            userid INT UNSIGNED NOT NULL,
            source ENUM('auto','manual') NOT NULL DEFAULT 'auto',
            metric BIGINT UNSIGNED NOT NULL DEFAULT 0,
            assigned_by INT UNSIGNED NOT NULL DEFAULT 0,
            assigned_at DATETIME NULL DEFAULT NULL,
            note VARCHAR(255) NOT NULL DEFAULT '',
            PRIMARY KEY (cup_id),
            KEY userid (userid),
            KEY source (source),
            KEY assigned_at (assigned_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ") or sqlerr(__FILE__, __LINE__);

    kz_cups_seed_catalog();
}

function kz_cups_seed_catalog()
{
    $values = array();

    foreach (kz_cups_catalog() as $cup) {
        $values[] = '('
            . (int)$cup['id'] . ', '
            . sqlesc($cup['cup_key'], true) . ', '
            . sqlesc($cup['title'], true) . ', '
            . sqlesc($cup['profile_title'], true) . ', '
            . sqlesc($cup['icon'], true) . ', '
            . (int)$cup['sort'] . ', 1)';
    }

    sql_query("
        INSERT INTO cups (id, cup_key, title, profile_title, icon, sort, active)
        VALUES " . implode(', ', $values) . "
        ON DUPLICATE KEY UPDATE
            cup_key = VALUES(cup_key),
            title = VALUES(title),
            profile_title = VALUES(profile_title),
            icon = VALUES(icon),
            sort = VALUES(sort),
            active = VALUES(active)
    ") or sqlerr(__FILE__, __LINE__);
}

function kz_cups_fetch_one($query)
{
    $res = sql_query($query) or sqlerr(__FILE__, __LINE__);
    $row = mysqli_fetch_assoc($res);

    if (!$row || empty($row['userid'])) {
        return null;
    }

    return array(
        'userid' => (int)$row['userid'],
        'metric' => isset($row['metric']) ? max(0, (int)$row['metric']) : 0,
    );
}

function kz_cups_candidate($cup_key)
{
    $since = sqlesc(get_date_time(TIMENOW - 7 * 86400), true);
    $active_user_where = "u.status = 'confirmed' AND u.enabled = 'yes'";

    switch ($cup_key) {
        case 'best_release':
            return kz_cups_fetch_one("
                SELECT t.owner AS userid,
                       CAST(((CASE WHEN t.numratings > 0 THEN t.ratingsum / t.numratings ELSE 0 END) * 1000000)
                           + (t.numratings * 1000) + t.times_completed AS UNSIGNED) AS metric
                FROM torrents AS t
                INNER JOIN users AS u ON u.id = t.owner
                WHERE t.owner > 0
                  AND t.visible = 'yes'
                  AND t.banned != 'yes'
                  AND t.added >= $since
                  AND $active_user_where
                ORDER BY (CASE WHEN t.numratings > 0 THEN t.ratingsum / t.numratings ELSE 0 END) DESC,
                         t.numratings DESC,
                         t.times_completed DESC,
                         t.views DESC,
                         t.id DESC
                LIMIT 1
            ");

        case 'popular_release':
            return kz_cups_fetch_one("
                SELECT t.owner AS userid,
                       CAST(t.times_completed + t.hits + t.views + t.seeders + t.leechers AS UNSIGNED) AS metric
                FROM torrents AS t
                INNER JOIN users AS u ON u.id = t.owner
                WHERE t.owner > 0
                  AND t.visible = 'yes'
                  AND t.banned != 'yes'
                  AND t.added >= $since
                  AND $active_user_where
                ORDER BY t.times_completed DESC,
                         t.hits DESC,
                         t.views DESC,
                         (t.seeders + t.leechers) DESC,
                         t.id DESC
                LIMIT 1
            ");

        case 'active_seeder':
            $candidate = kz_cups_fetch_one("
                SELECT s.userid,
                       CAST(SUM(s.uploaded) AS UNSIGNED) AS metric
                FROM snatched AS s
                INNER JOIN users AS u ON u.id = s.userid
                WHERE s.userid > 0
                  AND s.seeder = 'yes'
                  AND s.last_action >= $since
                  AND $active_user_where
                GROUP BY s.userid
                HAVING metric > 0
                ORDER BY metric DESC, s.userid ASC
                LIMIT 1
            ");

            if ($candidate !== null) {
                return $candidate;
            }

            return kz_cups_fetch_one("
                SELECT p.userid,
                       CAST(SUM(p.uploaded) AS UNSIGNED) AS metric
                FROM peers AS p
                INNER JOIN users AS u ON u.id = p.userid
                WHERE p.userid > 0
                  AND p.seeder = 'yes'
                  AND p.last_action >= $since
                  AND $active_user_where
                GROUP BY p.userid
                HAVING metric > 0
                ORDER BY metric DESC, p.userid ASC
                LIMIT 1
            ");

        case 'discussed_release':
            return kz_cups_fetch_one("
                SELECT t.owner AS userid,
                       COUNT(c.id) AS metric
                FROM comments AS c
                INNER JOIN torrents AS t ON t.id = c.torrent
                INNER JOIN users AS u ON u.id = t.owner
                WHERE c.added >= $since
                  AND t.owner > 0
                  AND t.visible = 'yes'
                  AND t.banned != 'yes'
                  AND $active_user_where
                GROUP BY t.id, t.owner
                ORDER BY metric DESC, t.id DESC
                LIMIT 1
            ");

        case 'best_commentator':
            return kz_cups_fetch_one("
                SELECT c.user AS userid,
                       COUNT(c.id) AS metric
                FROM comments AS c
                INNER JOIN users AS u ON u.id = c.user
                WHERE c.user > 0
                  AND c.added >= $since
                  AND $active_user_where
                GROUP BY c.user
                ORDER BY metric DESC, c.user ASC
                LIMIT 1
            ");

        case 'active_patron':
            $candidate = kz_cups_fetch_one("
                SELECT u.id AS userid,
                       UNIX_TIMESTAMP(u.last_access) AS metric
                FROM users AS u
                WHERE u.donor = 'yes'
                  AND u.last_access >= $since
                  AND $active_user_where
                ORDER BY u.last_access DESC, u.uploaded DESC, u.id ASC
                LIMIT 1
            ");

            if ($candidate !== null) {
                return $candidate;
            }

            return kz_cups_fetch_one("
                SELECT u.id AS userid,
                       UNIX_TIMESTAMP(u.last_access) AS metric
                FROM users AS u
                WHERE u.donor = 'yes'
                  AND $active_user_where
                ORDER BY u.last_access DESC, u.uploaded DESC, u.id ASC
                LIMIT 1
            ");

        case 'best_patron':
            return kz_cups_fetch_one("
                SELECT u.id AS userid,
                       u.uploaded AS metric
                FROM users AS u
                WHERE u.donor = 'yes'
                  AND $active_user_where
                ORDER BY u.uploaded DESC, u.bonus DESC, u.last_access DESC, u.id ASC
                LIMIT 1
            ");

        case 'best_dj':
            return kz_cups_fetch_one("
                SELECT t.owner AS userid,
                       COUNT(t.id) AS metric
                FROM torrents AS t
                INNER JOIN users AS u ON u.id = t.owner
                INNER JOIN categories AS c ON c.id = t.category
                WHERE t.owner > 0
                  AND t.visible = 'yes'
                  AND t.banned != 'yes'
                  AND t.added >= $since
                  AND c.name LIKE 'Музыка%'
                  AND $active_user_where
                GROUP BY t.owner
                ORDER BY metric DESC, SUM(t.times_completed) DESC, SUM(t.views) DESC, t.owner ASC
                LIMIT 1
            ");
    }

    return null;
}

function kz_cups_assign($cup_id, $userid, $source = 'manual', $metric = 0, $assigned_by = 0, $note = '')
{
    kz_cups_ensure_schema();

    $cup_id = (int)$cup_id;
    $userid = (int)$userid;
    $source = ($source === 'auto') ? 'auto' : 'manual';
    $metric = max(0, (int)$metric);
    $assigned_by = max(0, (int)$assigned_by);

    if ($cup_id < 1 || $cup_id > 8 || !is_valid_id($userid)) {
        return false;
    }

    $user_res = sql_query("SELECT id FROM users WHERE id = $userid LIMIT 1") or sqlerr(__FILE__, __LINE__);

    if (!mysqli_fetch_assoc($user_res)) {
        return false;
    }

    sql_query("
        REPLACE INTO user_cups (cup_id, userid, source, metric, assigned_by, assigned_at, note)
        VALUES ($cup_id, $userid, " . sqlesc($source, true) . ", $metric, $assigned_by, NOW(), " . sqlesc($note, true) . ")
    ") or sqlerr(__FILE__, __LINE__);

    return true;
}

function kz_cups_release($cup_id, $source = '')
{
    kz_cups_ensure_schema();

    $cup_id = (int)$cup_id;

    if ($cup_id < 1 || $cup_id > 8) {
        return;
    }

    $where = "cup_id = $cup_id";

    if ($source === 'auto' || $source === 'manual') {
        $where .= " AND source = " . sqlesc($source, true);
    }

    sql_query("DELETE FROM user_cups WHERE $where") or sqlerr(__FILE__, __LINE__);
}

function kz_cups_update_auto($force = false)
{
    kz_cups_ensure_schema();

    $now = TIMENOW;
    $interval = 2 * 3600;
    $res = sql_query("SELECT value_u FROM avps WHERE arg = 'cups_last_update' LIMIT 1") or sqlerr(__FILE__, __LINE__);
    $row = mysqli_fetch_assoc($res);
    $last_update = $row ? (int)$row['value_u'] : 0;

    if (!$force && $last_update > 0 && ($last_update + $interval) > $now) {
        return;
    }

    if ($row) {
        sql_query("UPDATE avps SET value_u = $now WHERE arg = 'cups_last_update'") or sqlerr(__FILE__, __LINE__);
    } else {
        sql_query("INSERT INTO avps (arg, value_u, value_s) VALUES ('cups_last_update', $now, '')") or sqlerr(__FILE__, __LINE__);
    }

    $manual_res = sql_query("SELECT cup_id FROM user_cups WHERE source = 'manual'") or sqlerr(__FILE__, __LINE__);
    $manual = array();

    while ($manual_row = mysqli_fetch_assoc($manual_res)) {
        $manual[(int)$manual_row['cup_id']] = true;
    }

    foreach (kz_cups_catalog() as $cup) {
        $cup_id = (int)$cup['id'];

        if (isset($manual[$cup_id])) {
            continue;
        }

        $candidate = kz_cups_candidate($cup['cup_key']);

        if ($candidate === null) {
            kz_cups_release($cup_id, 'auto');
            continue;
        }

        kz_cups_assign($cup_id, (int)$candidate['userid'], 'auto', (int)$candidate['metric'], 0, 'Автовыдача по статистике за последние семь дней');
    }
}

function kz_cups_current()
{
    kz_cups_ensure_schema();

    $rows = array();
    $res = sql_query("
        SELECT c.id AS cup_id,
               c.cup_key,
               c.title,
               c.profile_title,
               c.icon,
               c.sort,
               uc.userid,
               uc.source,
               uc.metric,
               uc.assigned_at,
               u.username,
               u.class,
               u.donor,
               u.warned,
               u.enabled,
               u.parked,
               co.flagpic
        FROM cups AS c
        LEFT JOIN user_cups AS uc ON uc.cup_id = c.id
        LEFT JOIN users AS u ON u.id = uc.userid
        LEFT JOIN countries AS co ON co.id = u.country
        WHERE c.active = 1
        ORDER BY c.sort ASC, c.id ASC
    ") or sqlerr(__FILE__, __LINE__);

    while ($row = mysqli_fetch_assoc($res)) {
        $rows[] = $row;
    }

    return $rows;
}

function kz_cups_for_user($userid)
{
    kz_cups_ensure_schema();

    $userid = (int)$userid;
    $rows = array();

    if (!is_valid_id($userid)) {
        return $rows;
    }

    $res = sql_query("
        SELECT c.id AS cup_id,
               c.title,
               c.profile_title,
               c.icon,
               c.sort,
               uc.source,
               uc.metric,
               uc.assigned_at
        FROM user_cups AS uc
        INNER JOIN cups AS c ON c.id = uc.cup_id
        WHERE uc.userid = $userid
          AND c.active = 1
        ORDER BY c.sort ASC, c.id ASC
    ") or sqlerr(__FILE__, __LINE__);

    while ($row = mysqli_fetch_assoc($res)) {
        $rows[] = $row;
    }

    return $rows;
}

function kz_cups_user_profile_html($userid)
{
    $cups = kz_cups_for_user($userid);

    if (empty($cups)) {
        return '';
    }

    $parts = array();

    foreach ($cups as $cup) {
        $parts[] = '<i class="i1 ' . kz_cups_h($cup['icon']) . '"></i> <span class="u9">' . kz_cups_h($cup['profile_title']) . '</span>';
    }

    return implode('<br />', $parts);
}

function kz_cups_user_manual_ids($userid)
{
    kz_cups_ensure_schema();

    $userid = (int)$userid;
    $ids = array();

    if (!is_valid_id($userid)) {
        return $ids;
    }

    $res = sql_query("SELECT cup_id FROM user_cups WHERE userid = $userid AND source = 'manual'") or sqlerr(__FILE__, __LINE__);

    while ($row = mysqli_fetch_assoc($res)) {
        $ids[] = (int)$row['cup_id'];
    }

    return $ids;
}

function kz_cups_save_profile_manual($userid, $selected_ids, $admin_id)
{
    kz_cups_ensure_schema();

    $userid = (int)$userid;
    $admin_id = (int)$admin_id;

    if (!is_valid_id($userid)) {
        return;
    }

    $selected = array();

    foreach ((array)$selected_ids as $id) {
        $id = (int)$id;

        if ($id >= 1 && $id <= 8) {
            $selected[$id] = true;
        }
    }

    $current = kz_cups_user_manual_ids($userid);

    foreach ($current as $cup_id) {
        if (!isset($selected[$cup_id])) {
            sql_query("DELETE FROM user_cups WHERE cup_id = $cup_id AND userid = $userid AND source = 'manual'") or sqlerr(__FILE__, __LINE__);
        }
    }

    foreach (array_keys($selected) as $cup_id) {
        kz_cups_assign($cup_id, $userid, 'manual', 0, $admin_id, 'Назначено администратором');
    }
}

function kz_cups_find_user_by_username($username)
{
    kz_cups_ensure_schema();

    $username = trim((string)$username);

    if ($username === '') {
        return null;
    }

    $res = sql_query("
        SELECT id, username, class
        FROM users
        WHERE username = " . sqlesc($username, true) . "
        LIMIT 1
    ") or sqlerr(__FILE__, __LINE__);
    $row = mysqli_fetch_assoc($res);

    return $row ?: null;
}

?>
