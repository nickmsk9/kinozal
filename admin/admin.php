<?php

loggedinorreturn();

if (get_user_class() < UC_ADMINISTRATOR) {
    stderr($tracker_lang['error'], 'Что вы тут забыли?');
}

require_once 'admin/core.php';

$op = (string)($_GET['op'] ?? $_POST['op'] ?? 'Main');
$counter = 0;

function BuildMenu($url, $title, $image = '')
{
    global $counter;

    $urlSafe   = htmlspecialchars((string)$url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $titleSafe = htmlspecialchars((string)$title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $image     = trim((string)$image);
    $iconKey   = strtolower(preg_replace('/[^a-z0-9_]+/i', '', preg_replace('/\.(gif|png|jpg|jpeg|svg)$/i', '', $image)));

    if ($iconKey === '') {
        $iconKey = strtolower((string)(parse_url((string)$url, PHP_URL_QUERY) ?: 'system'));
        $iconKey = preg_replace('/^op=|admin$/i', '', $iconKey);
        $iconKey = strtolower(preg_replace('/[^a-z0-9_]+/i', '', $iconKey));
    }

    $icons = array(
        'adduser' => array('#4a7f52', '<circle cx="13" cy="11" r="4"/><path d="M6 25c1-5 3.5-8 7-8 2.2 0 4.1 1.2 5.4 3.3M23 16v10M18 21h10"/>'),
        'bans' => array('#b45d4d', '<circle cx="16" cy="16" r="9"/><path d="M10 10l12 12"/>'),
        'block' => array('#5a71b0', '<rect x="7" y="8" width="18" height="6" rx="1.5"/><rect x="7" y="18" width="8" height="6" rx="1.5"/><rect x="18" y="18" width="7" height="6" rx="1.5"/>'),
        'blocksadmin' => array('#5a71b0', '<rect x="7" y="8" width="18" height="6" rx="1.5"/><rect x="7" y="18" width="8" height="6" rx="1.5"/><rect x="18" y="18" width="7" height="6" rx="1.5"/>'),
        'db' => array('#4a7f52', '<ellipse cx="16" cy="9" rx="8" ry="3"/><path d="M8 9v10c0 1.7 3.6 3 8 3s8-1.3 8-3V9"/><path d="M8 14c0 1.7 3.6 3 8 3s8-1.3 8-3"/>'),
        'statusdb' => array('#4a7f52', '<ellipse cx="16" cy="9" rx="8" ry="3"/><path d="M8 9v10c0 1.7 3.6 3 8 3s8-1.3 8-3V9"/><path d="M8 14c0 1.7 3.6 3 8 3s8-1.3 8-3"/>'),
        'password' => array('#7a5aa8', '<rect x="8" y="14" width="16" height="10" rx="2"/><path d="M11 14v-3a5 5 0 0 1 10 0v3"/><circle cx="16" cy="19" r="1.5"/>'),
        'show' => array('#b57936', '<path d="M6 17s4-7 10-7 10 7 10 7-4 7-10 7S6 17 6 17z"/><circle cx="16" cy="17" r="3"/>'),
        'stylesheet' => array('#5870ad', '<path d="M10 6h9l5 5v15H10z"/><path d="M19 6v6h5"/><path d="M13 15h8M13 19h8M13 23h5"/>'),
        'system' => array('#777777', '<circle cx="16" cy="16" r="4"/><path d="M16 5v4M16 23v4M5 16h4M23 16h4M8.2 8.2l2.8 2.8M21 21l2.8 2.8M23.8 8.2 21 11M11 21l-2.8 2.8"/>'),
        'cupsadmin' => array('#c58a20', '<path d="M10 7h12v4a6 6 0 0 1-12 0z"/><path d="M10 9H7c0 4 2 6 5 6M22 9h3c0 4-2 6-5 6M16 17v5M12 25h8"/>'),
        'cups' => array('#c58a20', '<path d="M10 7h12v4a6 6 0 0 1-12 0z"/><path d="M10 9H7c0 4 2 6 5 6M22 9h3c0 4-2 6-5 6M16 17v5M12 25h8"/>'),
        'grouppageadmin' => array('#4f8a83', '<circle cx="11" cy="12" r="3"/><circle cx="21" cy="12" r="3"/><path d="M6 24c.7-4 3-6 5-6s4.3 2 5 6M16 24c.7-3.5 2.7-5.5 5-5.5 2 0 4.1 1.7 5 5.5"/>'),
        'group' => array('#4f8a83', '<circle cx="11" cy="12" r="3"/><circle cx="21" cy="12" r="3"/><path d="M6 24c.7-4 3-6 5-6s4.3 2 5 6M16 24c.7-3.5 2.7-5.5 5-5.5 2 0 4.1 1.7 5 5.5"/>'),
        'multitrackeradmin' => array('#6b7fb5', '<circle cx="8" cy="16" r="3"/><circle cx="24" cy="9" r="3"/><circle cx="24" cy="23" r="3"/><path d="M11 15l10-5M11 17l10 5"/>'),
        'multitracker' => array('#6b7fb5', '<circle cx="8" cy="16" r="3"/><circle cx="24" cy="9" r="3"/><circle cx="24" cy="23" r="3"/><path d="M11 15l10-5M11 17l10 5"/>'),
        'payadmin' => array('#3b8b63', '<circle cx="16" cy="16" r="9"/><path d="M16 10v12M12 13c0-2 8-2 8 0s-8 1-8 4 8 2 8 0"/>'),
        'pay' => array('#3b8b63', '<circle cx="16" cy="16" r="9"/><path d="M16 10v12M12 13c0-2 8-2 8 0s-8 1-8 4 8 2 8 0"/>'),
        'personsadmin' => array('#9a5c7a', '<circle cx="16" cy="11" r="4"/><path d="M8 25c1-5 4-8 8-8s7 3 8 8"/>'),
        'persons' => array('#9a5c7a', '<circle cx="16" cy="11" r="4"/><path d="M8 25c1-5 4-8 8-8s7 3 8 8"/>'),
        'radioadmin' => array('#b45d4d', '<rect x="7" y="12" width="18" height="11" rx="2"/><path d="M10 12l10-6"/><circle cx="13" cy="17.5" r="2"/><path d="M18 16h5M18 20h4"/>'),
        'radio' => array('#b45d4d', '<rect x="7" y="12" width="18" height="11" rx="2"/><path d="M10 12l10-6"/><circle cx="13" cy="17.5" r="2"/><path d="M18 16h5M18 20h4"/>'),
        'reputationadmin' => array('#ba6f2a', '<path d="M16 6l3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1z"/>'),
        'reputation' => array('#ba6f2a', '<path d="M16 6l3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1z"/>'),
        'userstatusesadmin' => array('#5b7f9a', '<path d="M9 8h14M9 16h14M9 24h14"/><circle cx="6" cy="8" r="1.5"/><circle cx="6" cy="16" r="1.5"/><circle cx="6" cy="24" r="1.5"/>'),
        'statuses' => array('#5b7f9a', '<path d="M9 8h14M9 16h14M9 24h14"/><circle cx="6" cy="8" r="1.5"/><circle cx="6" cy="16" r="1.5"/><circle cx="6" cy="24" r="1.5"/>'),
        'uarchadmin' => array('#8b7f3f', '<rect x="8" y="8" width="16" height="18" rx="2"/><path d="M12 12h8M12 16h8M12 20h5"/>'),
        'uarch' => array('#8b7f3f', '<rect x="8" y="8" width="16" height="18" rx="2"/><path d="M12 12h8M12 16h8M12 20h5"/>'),
        'sitesettingsadmin' => array('#517a9f', '<circle cx="16" cy="16" r="4"/><path d="M16 5v3M16 24v3M5 16h3M24 16h3M8.5 8.5l2 2M21.5 21.5l2 2M23.5 8.5l-2 2M10.5 21.5l-2 2"/>'),
        'site' => array('#517a9f', '<circle cx="16" cy="16" r="4"/><path d="M16 5v3M16 24v3M5 16h3M24 16h3M8.5 8.5l2 2M21.5 21.5l2 2M23.5 8.5l-2 2M10.5 21.5l-2 2"/>'),
    );

    $icon = $icons[$iconKey] ?? $icons['system'];
    $imageHtml = '<span class="admin-menu-icon" style="display:inline-block;width:32px;height:32px;margin-bottom:4px;">'
        . '<svg width="32" height="32" viewBox="0 0 32 32" role="img" aria-hidden="true" style="display:block;">'
        . '<rect x="1" y="1" width="30" height="30" rx="6" fill="#fbfbfb" stroke="#f1d29c" stroke-width="2"/>'
        . '<g fill="none" stroke="' . $icon[0] . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $icon[1] . '</g>'
        . '</svg></span><br />';

    echo '
        <td class="center top w15p">
            <a class="sbab" href="' . $urlSafe . '" title="' . $titleSafe . '">
                ' . $imageHtml . '
                <b>' . $titleSafe . '</b>
            </a>
        </td>
    ';

    $counter++;

    if ($counter >= 6) {
        echo '</tr><tr>';
        $counter = 0;
    }
}

function admin_dashboard_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function admin_dashboard_value($sql, $default = 0)
{
    $res = sql_query($sql) or sqlerr(__FILE__, __LINE__);
    $row = mysqli_fetch_row($res);
    return $row ? $row[0] : $default;
}

function admin_dashboard_rows($sql)
{
    $res = sql_query($sql) or sqlerr(__FILE__, __LINE__);
    $rows = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $rows[] = $row;
    }
    return $rows;
}

function admin_dashboard_table_exists($table)
{
    static $cache = array();

    $table = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$table);
    if ($table === '') {
        return false;
    }

    if (!array_key_exists($table, $cache)) {
        $res = sql_query("SHOW TABLES LIKE " . sqlesc($table)) or sqlerr(__FILE__, __LINE__);
        $cache[$table] = mysqli_num_rows($res) > 0;
    }

    return $cache[$table];
}

function admin_dashboard_class_link($user_id, $username, $class = 0)
{
    $user_id = (int)$user_id;
    $username = (string)$username;
    if ($user_id <= 0 || $username === '') {
        return '<i>system</i>';
    }

    return '<a href="/userdetails.php?id=' . $user_id . '" class="u' . (int)$class . '">' . admin_dashboard_h($username) . '</a>';
}

function admin_dashboard_date($value)
{
    if (empty($value) || $value === '0000-00-00 00:00:00') {
        return '';
    }

    $ts = strtotime((string)$value);
    return $ts ? date('d.m.Y H:i', $ts) : admin_dashboard_h($value);
}

function admin_dashboard_count($label, $value, $link = '')
{
    $value = (int)$value;
    $text = '<b>' . number_format($value, 0, '.', ' ') . '</b><br><span class="small">' . admin_dashboard_h($label) . '</span>';

    if ($link !== '') {
        $text = '<a class="sbab" href="' . admin_dashboard_h($link) . '">' . $text . '</a>';
    }

    return '<td class="center">' . $text . '</td>';
}

function admin_dashboard_bool($value)
{
    return $value ? '<span class="green b">да</span>' : '<span class="red b">нет</span>';
}

function admin_dashboard_status_row($label, $value, $good = true)
{
    $class = $good ? 'green' : 'red';
    echo '<tr><td class="rowhead w250">' . admin_dashboard_h($label) . '</td><td><span class="' . $class . ' b">' . admin_dashboard_h($value) . '</span></td></tr>';
}

function admin_dashboard_disk_text($path)
{
    $free = @disk_free_space($path);
    $total = @disk_total_space($path);

    if ($free === false || $total === false || $total <= 0) {
        return 'недоступно';
    }

    $used = $total - $free;
    $percent = $total > 0 ? round(($used / $total) * 100, 1) : 0;
    return mksize((int)$free) . ' свободно из ' . mksize((int)$total) . ' (' . $percent . '% занято)';
}

function admin_dashboard_cache_status()
{
    if (!function_exists('tracker_cache_enabled') || !tracker_cache_enabled()) {
        return 'выключен';
    }

    if (!function_exists('tracker_cache_redis')) {
        return 'локальный fallback';
    }

    return tracker_cache_redis() ? 'Redis подключен' : 'Redis недоступен, fallback';
}

function admin_dashboard_overview()
{
    return admin_dashboard_rows("
        SELECT
            (SELECT COUNT(*) FROM users) AS users_total,
            (SELECT COUNT(*) FROM users WHERE status = 'pending') AS users_pending,
            (SELECT COUNT(*) FROM users WHERE enabled = 'no') AS users_disabled,
            (SELECT COUNT(*) FROM torrents) AS torrents_total,
            (SELECT COUNT(*) FROM torrents WHERE visible = 'yes' AND banned != 'yes' AND is_test = 'no') AS torrents_public,
            (SELECT COUNT(*) FROM torrents WHERE is_test = 'yes') AS torrents_test,
            (SELECT COUNT(*) FROM torrents WHERE moderated = 'no' AND is_test = 'no' AND visible = 'yes' AND banned != 'yes') AS torrents_unmoderated,
            (SELECT COUNT(*) FROM peers) AS peers_total,
            (SELECT COALESCE(SUM(seeder = 'yes'), 0) FROM peers) AS seeders_total,
            (SELECT COALESCE(SUM(seeder = 'no'), 0) FROM peers) AS leechers_total,
            (SELECT COUNT(*) FROM comments WHERE added >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) AS comments_day,
            (SELECT COUNT(*) FROM messages WHERE unread = 'yes' AND location = 1) AS unread_pm,
            (SELECT COUNT(*) FROM sessions WHERE time >= UNIX_TIMESTAMP() - 900) AS online_15m
    ")[0] ?? array();
}

function admin_dashboard_render_summary($admin_file)
{
    $data = admin_dashboard_overview();

    echo '<div class="mn_wrap">';
    echo '<div class="tp1_title"><b>Оперативная сводка</b></div>';
    echo '<div class="tp1_body">';
    echo '<table class="tables2 w100p">';
    echo '<tr>';
    echo admin_dashboard_count('пользователей', $data['users_total'] ?? 0, '/users.php');
    echo admin_dashboard_count('публичных раздач', $data['torrents_public'] ?? 0, '/browse.php');
    echo admin_dashboard_count('активных пиров', $data['peers_total'] ?? 0);
    echo admin_dashboard_count('онлайн за 15 минут', $data['online_15m'] ?? 0);
    echo '</tr><tr>';
    echo admin_dashboard_count('ожидают email/активации', $data['users_pending'] ?? 0, '/users.php');
    echo admin_dashboard_count('тестовых раздач', $data['torrents_test'] ?? 0, $admin_file . '.php?op=TestTorrentsAdmin');
    echo admin_dashboard_count('сидов', $data['seeders_total'] ?? 0);
    echo admin_dashboard_count('личных непрочитанных', $data['unread_pm'] ?? 0, '/inbox.php');
    echo '</tr><tr>';
    echo admin_dashboard_count('отключенных пользователей', $data['users_disabled'] ?? 0, '/users.php');
    echo admin_dashboard_count('непроверенных обычных', $data['torrents_unmoderated'] ?? 0, '/browse.php');
    echo admin_dashboard_count('личей', $data['leechers_total'] ?? 0);
    echo admin_dashboard_count('комментариев за сутки', $data['comments_day'] ?? 0);
    echo '</tr>';
    echo '</table>';
    echo '</div></div>';
}

function admin_dashboard_render_queues($admin_file)
{
    require_once ROOT_PATH . 'include/test_torrents.php';

    $has_review_schema = test_torrents_review_schema_ready();
    $test_counts = array('pending' => 0, 'checking' => 0, 'changes' => 0, 'rejected' => 0);

    if ($has_review_schema) {
        $rows = admin_dashboard_rows("
            SELECT test_status, COUNT(*) AS total
            FROM torrents
            WHERE is_test = 'yes'
            GROUP BY test_status
        ");
        foreach ($rows as $row) {
            $key = (string)$row['test_status'];
            if (array_key_exists($key, $test_counts)) {
                $test_counts[$key] = (int)$row['total'];
            }
        }
    } else {
        $test_counts['pending'] = (int)admin_dashboard_value("SELECT COUNT(*) FROM torrents WHERE is_test = 'yes'");
    }

    $pending_users = (int)admin_dashboard_value("SELECT COUNT(*) FROM users WHERE status = 'pending'");
    $disabled_users = (int)admin_dashboard_value("SELECT COUNT(*) FROM users WHERE enabled = 'no'");
    $unmoderated = (int)admin_dashboard_value("SELECT COUNT(*) FROM torrents WHERE moderated = 'no' AND is_test = 'no' AND visible = 'yes' AND banned != 'yes'");
    $bad_trackers = admin_dashboard_table_exists('torrent_trackers')
        ? (int)admin_dashboard_value("SELECT COUNT(*) FROM torrent_trackers WHERE enabled = 'yes' AND last_error != ''")
        : 0;
    $hidden_wishes = admin_dashboard_table_exists('pay_wishes')
        ? (int)admin_dashboard_value("SELECT COUNT(*) FROM pay_wishes WHERE active != 'yes'")
        : 0;
    $support_chat = admin_dashboard_table_exists('pay_chat')
        ? (int)admin_dashboard_value("SELECT COUNT(*) FROM pay_chat WHERE visible = 'yes' AND tab = 2")
        : 0;

    echo '<div class="mn_wrap">';
    echo '<div class="tp1_title"><b>Очереди внимания</b></div>';
    echo '<div class="tp1_body">';
    echo '<table class="tables2 w100p">';
    echo '<tr><td class="colhead">Очередь</td><td class="colhead center">Количество</td><td class="colhead">Переход</td></tr>';
    echo '<tr><td>Тестовые: ожидают проверки</td><td class="center b">' . (int)$test_counts['pending'] . '</td><td><a class="sba" href="' . admin_dashboard_h($admin_file) . '.php?op=TestTorrentsAdmin&amp;status=pending">открыть</a></td></tr>';
    echo '<tr><td>Тестовые: в работе</td><td class="center b">' . (int)$test_counts['checking'] . '</td><td><a class="sba" href="' . admin_dashboard_h($admin_file) . '.php?op=TestTorrentsAdmin&amp;status=checking">открыть</a></td></tr>';
    echo '<tr><td>Тестовые: на доработке</td><td class="center b">' . (int)$test_counts['changes'] . '</td><td><a class="sba" href="' . admin_dashboard_h($admin_file) . '.php?op=TestTorrentsAdmin&amp;status=changes">открыть</a></td></tr>';
    echo '<tr><td>Обычные раздачи без модерации</td><td class="center b">' . $unmoderated . '</td><td><a class="sba" href="/browse.php">раздачи</a></td></tr>';
    echo '<tr><td>Пользователи pending</td><td class="center b">' . $pending_users . '</td><td><a class="sba" href="/users.php">пользователи</a></td></tr>';
    echo '<tr><td>Отключенные пользователи</td><td class="center b">' . $disabled_users . '</td><td><a class="sba" href="/users.php">пользователи</a></td></tr>';
    echo '<tr><td>Трекеры мультитрекера с ошибкой</td><td class="center b">' . $bad_trackers . '</td><td><a class="sba" href="' . admin_dashboard_h($admin_file) . '.php?op=MultitrackerAdmin">мультитрекер</a></td></tr>';
    echo '<tr><td>Скрытые пожелания меценатов</td><td class="center b">' . $hidden_wishes . '</td><td><a class="sba" href="' . admin_dashboard_h($admin_file) . '.php?op=PayAdmin">меценаты</a></td></tr>';
    echo '<tr><td>Сообщения техподдержки меценатов</td><td class="center b">' . $support_chat . '</td><td><a class="sba" href="' . admin_dashboard_h($admin_file) . '.php?op=PayAdmin">меценаты</a></td></tr>';
    echo '</table>';
    echo '</div></div>';
}

function admin_dashboard_render_health()
{
    global $SITE_ONLINE, $deny_signup, $use_captcha, $use_gzip, $use_ipbans, $use_sessions;

    $db_version = admin_dashboard_value('SELECT VERSION()', 'unknown');
    $error_log = ROOT_PATH . 'include/php_errors.log';
    $error_size = is_file($error_log) ? mksize((int)filesize($error_log)) : 'нет файла';
    $error_mtime = is_file($error_log) ? date('d.m.Y H:i', filemtime($error_log)) : '';

    echo '<div class="mn_wrap">';
    echo '<div class="tp1_title"><b>Здоровье системы</b></div>';
    echo '<div class="tp1_body">';
    echo '<table class="tables2 w100p">';
    echo '<tr><td class="colhead" colspan="2">Окружение</td></tr>';
    admin_dashboard_status_row('PHP', PHP_VERSION, version_compare(PHP_VERSION, '8.0.0', '>='));
    admin_dashboard_status_row('MySQL', $db_version, true);
    admin_dashboard_status_row('Кэш', admin_dashboard_cache_status(), true);
    admin_dashboard_status_row('Диск проекта', admin_dashboard_disk_text(ROOT_PATH), true);
    admin_dashboard_status_row('Лог PHP', trim($error_size . ' ' . $error_mtime), true);
    echo '<tr><td class="colhead" colspan="2">Переключатели</td></tr>';
    echo '<tr><td class="rowhead w250">Сайт онлайн</td><td>' . admin_dashboard_bool(!empty($SITE_ONLINE)) . '</td></tr>';
    echo '<tr><td class="rowhead">Регистрация закрыта</td><td>' . admin_dashboard_bool(!empty($deny_signup)) . '</td></tr>';
    echo '<tr><td class="rowhead">Капча</td><td>' . admin_dashboard_bool(!empty($use_captcha)) . '</td></tr>';
    echo '<tr><td class="rowhead">Gzip</td><td>' . admin_dashboard_bool(!empty($use_gzip)) . '</td></tr>';
    echo '<tr><td class="rowhead">IP-баны</td><td>' . admin_dashboard_bool(!empty($use_ipbans)) . '</td></tr>';
    echo '<tr><td class="rowhead">Сессии</td><td>' . admin_dashboard_bool(!empty($use_sessions)) . '</td></tr>';
    echo '<tr><td class="rowhead">Автомиграции</td><td>' . admin_dashboard_bool(defined('KZ_AUTO_MIGRATIONS') && KZ_AUTO_MIGRATIONS === true) . '</td></tr>';
    echo '</table>';
    echo '</div></div>';
}

function admin_dashboard_render_recent_torrents()
{
    $rows = admin_dashboard_rows("
        SELECT t.id, t.name, t.added, t.size, t.visible, t.banned, t.is_test, t.owner,
               (t.seeders + t.remote_seeders) AS seeders,
               (t.leechers + t.remote_leechers) AS leechers,
               u.username, u.class
        FROM torrents AS t
        LEFT JOIN users AS u ON u.id = t.owner
        ORDER BY t.id DESC
        LIMIT 8
    ");

    echo '<div class="mn_wrap">';
    echo '<div class="tp1_title"><b>Последние раздачи</b></div>';
    echo '<div class="tp1_body">';
    echo '<table class="tables2 w100p">';
    echo '<tr><td class="colhead">Раздача</td><td class="colhead">Автор</td><td class="colhead center">Размер</td><td class="colhead center">Сиды/пиры</td><td class="colhead center">Статус</td></tr>';
    foreach ($rows as $row) {
        $status = array();
        if ((string)$row['is_test'] === 'yes') {
            $status[] = 'тест';
        }
        if ((string)$row['visible'] !== 'yes') {
            $status[] = 'скрыта';
        }
        if ((string)$row['banned'] === 'yes') {
            $status[] = 'бан';
        }
        if (!$status) {
            $status[] = 'публична';
        }

        echo '<tr>';
        echo '<td><a class="sbab" href="/details.php?id=' . (int)$row['id'] . '">' . admin_dashboard_h($row['name']) . '</a><br><span class="small">' . admin_dashboard_date($row['added']) . '</span></td>';
        echo '<td>' . admin_dashboard_class_link((int)$row['owner'], $row['username'] ?? '', (int)($row['class'] ?? 0)) . '</td>';
        echo '<td class="center">' . mksize((int)$row['size']) . '</td>';
        echo '<td class="center"><span class="green b">' . (int)$row['seeders'] . '</span> / <span class="red b">' . (int)$row['leechers'] . '</span></td>';
        echo '<td class="center">' . admin_dashboard_h(implode(', ', $status)) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</div></div>';
}

function admin_dashboard_render_recent_users()
{
    $rows = admin_dashboard_rows("
        SELECT id, username, class, status, enabled, added, last_access, uploaded, downloaded
        FROM users
        ORDER BY id DESC
        LIMIT 8
    ");

    echo '<div class="mn_wrap">';
    echo '<div class="tp1_title"><b>Последние пользователи</b></div>';
    echo '<div class="tp1_body">';
    echo '<table class="tables2 w100p">';
    echo '<tr><td class="colhead">Пользователь</td><td class="colhead center">Класс</td><td class="colhead center">Статус</td><td class="colhead center">Рейтинг</td><td class="colhead">Активность</td></tr>';
    foreach ($rows as $row) {
        $downloaded = (float)$row['downloaded'];
        $ratio = $downloaded > 0 ? ((float)$row['uploaded'] / $downloaded) : 0;
        $ratio_text = $downloaded > 0 ? number_format($ratio, 2, '.', '') : '---';
        $status = (string)$row['status'] . ((string)$row['enabled'] === 'yes' ? '' : ', disabled');

        echo '<tr>';
        echo '<td>' . admin_dashboard_class_link((int)$row['id'], $row['username'], (int)$row['class']) . '<br><span class="small">' . admin_dashboard_date($row['added']) . '</span></td>';
        echo '<td class="center">' . admin_dashboard_h(get_user_class_name((int)$row['class'])) . '</td>';
        echo '<td class="center">' . admin_dashboard_h($status) . '</td>';
        echo '<td class="center">' . $ratio_text . '</td>';
        echo '<td>' . admin_dashboard_date($row['last_access']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</div></div>';
}

function admin_dashboard_render_logs()
{
    $rows = admin_dashboard_rows("
        SELECT added, txt, type
        FROM sitelog
        ORDER BY id DESC
        LIMIT 10
    ");

    echo '<div class="mn_wrap">';
    echo '<div class="tp1_title"><b>Последний системный лог</b></div>';
    echo '<div class="tp1_body">';
    echo '<table class="tables2 w100p">';
    echo '<tr><td class="colhead w150">Дата</td><td class="colhead w100">Тип</td><td class="colhead">Событие</td></tr>';
    foreach ($rows as $row) {
        echo '<tr><td>' . admin_dashboard_date($row['added']) . '</td><td>' . admin_dashboard_h($row['type']) . '</td><td>' . admin_dashboard_h($row['txt']) . '</td></tr>';
    }
    echo '</table>';
    echo '</div></div>';
}

function admin_dashboard_render_sections_menu()
{
    global $admin_file, $counter;

    $counter = 0;
    echo '
        <div class="mn_wrap">
            <table class="tables2 w100p">
                <tr>
                    <td class="colhead center" colspan="6">Разделы администрирования</td>
                </tr>
                <tr>
    ';

    $linksDir = 'admin/links';

    if (is_dir($linksDir)) {
        $files = scandir($linksDir);

        if ($files !== false) {
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                if (preg_match('/\.php$/i', $file)) {
                    require_once $linksDir . '/' . $file;
                }
            }
        }
    }

    echo '
                </tr>
                <tr>
                    <td class="colhead center" colspan="6">&nbsp;</td>
                </tr>
            </table>
        </div>
    ';
}

function admin_dashboard_render_main($admin_file)
{
    admin_dashboard_render_summary($admin_file);
    admin_dashboard_render_queues($admin_file);
    admin_dashboard_render_health();
    admin_dashboard_render_recent_torrents();
    admin_dashboard_render_recent_users();
    admin_dashboard_render_logs();
    admin_dashboard_render_sections_menu();
}

switch ($op) {
    case 'Main':
        admin_dashboard_render_main($admin_file);
        break;

    default:
        $modulesDir = 'admin/modules';

        if (is_dir($modulesDir)) {
            $files = scandir($modulesDir);

            if ($files !== false) {
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }

                    if (preg_match('/\.php$/i', $file)) {
                        require_once $modulesDir . '/' . $file;
                    }
                }
            }
        }
        break;
}

?>
