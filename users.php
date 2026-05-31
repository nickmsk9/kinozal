<?php

/*
// +--------------------------------------------------------------------------+
// | Project:    TBDevYSE - TBDev Yuna Scatari Edition                        |
// +--------------------------------------------------------------------------+
// | This file is part of TBDevYSE. TBDevYSE is based on TBDev,               |
// | originally by RedBeard of TorrentBits, extensively modified by           |
// | Gartenzwerg.                                                             |
// |                                                                          |
// | TBDevYSE is free software; you can redistribute it and/or modify         |
// | it under the terms of the GNU General Public License as published by     |
// | the Free Software Foundation; either version 2 of the License, or        |
// | (at your option) any later version.                                      |
// |                                                                          |
// | TBDevYSE is distributed in the hope that it will be useful,              |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
// | GNU General Public License for more details.                             |
// |                                                                          |
// | You should have received a copy of the GNU General Public License        |
// | along with TBDevYSE; if not, write to the Free Software Foundation,      |
// | Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA            |
// +--------------------------------------------------------------------------+
// |                                               Do not remove above lines! |
// +--------------------------------------------------------------------------+
*/

require_once __DIR__ . '/include/bittorrent.php';

dbconn();
loggedinorreturn();

function users_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function users_get($name, $default = '')
{
    return isset($_GET[$name]) ? trim((string)$_GET[$name]) : $default;
}

function users_like($value)
{
    return sqlesc('%' . str_replace(array('%', '_'), array('\\%', '\\_'), $value) . '%');
}

function users_selected($current, $value)
{
    return ((string)$current === (string)$value) ? ' selected' : '';
}

function users_size($bytes)
{
    $bytes = max(0, (float)$bytes);
    $units = array('КБ', 'МБ', 'ГБ', 'ТБ');
    $value = $bytes / 1024;

    foreach ($units as $idx => $unit) {
        if ($value < 1024 || $idx === count($units) - 1) {
            $text = number_format($value, 2, '.', ' ');
            $text = rtrim(rtrim($text, '0'), '.');
            return ($text === '' ? '0' : $text) . ' ' . $unit;
        }
        $value /= 1024;
    }

    return '0 КБ';
}

function users_date_text($date)
{
    if (empty($date) || $date === '0000-00-00 00:00:00') {
        return '';
    }

    $ts = strtotime($date);
    if (!$ts) {
        return '';
    }

    $day = date('Y-m-d', $ts);
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', time() - 86400);
    $time = date('H:i', $ts);

    if ($day === $today) {
        return 'сегодня в ' . $time;
    }

    if ($day === $yesterday) {
        return 'вчера в ' . $time;
    }

    $months = array(
        1 => 'января',
        2 => 'февраля',
        3 => 'марта',
        4 => 'апреля',
        5 => 'мая',
        6 => 'июня',
        7 => 'июля',
        8 => 'августа',
        9 => 'сентября',
        10 => 'октября',
        11 => 'ноября',
        12 => 'декабря',
    );

    return (int)date('j', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts) . ' в ' . $time;
}

function users_build_url($params)
{
    $query = $_GET;
    foreach ($params as $key => $value) {
        if ($value === null) {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }
    }

    return 'users.php' . ($query ? '?' . http_build_query($query) : '');
}

function users_paginator($count, $perpage, $page)
{
    $pages = (int)ceil($count / $perpage);
    if ($pages <= 1) {
        return '';
    }

    $items = array();
    $last = $pages - 1;
    $visible = array(0, 1, 2, 3, 4, $page - 1, $page, $page + 1, $last);
    $visible = array_unique(array_filter($visible, function ($value) use ($last) {
        return $value >= 0 && $value <= $last;
    }));
    sort($visible);

    $previous = -1;
    foreach ($visible as $idx) {
        if ($previous >= 0 && $idx > $previous + 1) {
            $items[] = '<li class="dots">...</li>';
        }

        $href = users_h(users_build_url(array('page' => $idx)));
        if ($idx === $page) {
            $items[] = '<li class="current"><a href="' . $href . '">' . ($idx + 1) . '</a></li>';
        } else {
            $items[] = '<li><a href="' . $href . '">' . ($idx + 1) . '</a></li>';
        }

        $previous = $idx;
    }

    if ($page < $last) {
        $items[] = '<li><a rel="next" href="' . users_h(users_build_url(array('page' => $page + 1))) . '">Вперед</a></li>';
    }

    return '<div class="paginator"><ul>' . implode("\n", $items) . '</ul></div>';
}

function users_online_html()
{
    $dt = time() - 300;
    $res = sql_query("
        SELECT s.uid, u.username, u.class, u.gender, u.parked
        FROM sessions AS s
        INNER JOIN users AS u ON u.id = s.uid
        WHERE s.time >= $dt
          AND s.uid > 0
          AND (s.url LIKE '/users.php%' OR s.url LIKE 'users.php%')
        GROUP BY s.uid, u.username, u.class, u.gender, u.parked
        ORDER BY u.class DESC, u.username ASC
    ") or sqlerr(__FILE__, __LINE__);

    $items = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $username = users_h($row['username']);
        $html = '<a href="/userdetails.php?id=' . (int)$row['uid'] . '" class="u' . (int)$row['class'] . '">' . $username . '</a>';

        if ((string)$row['gender'] === '2') {
            $html .= '<i class="i1 s_dv"></i>';
        }

        if ((string)$row['parked'] === 'yes') {
            $html .= '<i class="i1 s_park"></i>';
        }

        $items[] = $html;
    }

    if (!$items) {
        return 'никого нет на этой странице';
    }

    return implode(', ', $items);
}

$search_name = users_get('s1');
if ($search_name === '') {
    $search_name = users_get('search');
}
$search_city = users_get('s2');
$search_movie = users_get('s3');
$search_person = users_get('s4');
$country = users_get('co');
$gender = users_get('gn');
$photo = users_get('f', '1');
if ($photo === '') {
    $photo = '1';
}
$class = users_get('c');
$status = users_get('g');
$sort = users_get('s', '0');
$order = users_get('o', '0');

$where = array("u.status = 'confirmed'");

if ($search_name !== '') {
    $where[] = 'u.username LIKE ' . users_like($search_name);
}
if ($search_city !== '') {
    $where[] = 'u.city LIKE ' . users_like($search_city);
}
if ($search_movie !== '') {
    $where[] = 'u.favorite_movie LIKE ' . users_like($search_movie);
}
if ($search_person !== '') {
    $where[] = 'u.favorite_persons LIKE ' . users_like($search_person);
}
if ($country !== '' && ctype_digit($country)) {
    $where[] = 'u.country = ' . (int)$country;
}
if ($gender === '1' || $gender === '2') {
    $where[] = 'u.gender = ' . sqlesc($gender);
}
if ($photo === '2') {
    $where[] = "u.avatar <> ''";
}
if ($class !== '' && ctype_digit($class)) {
    $class_id = (int)$class - 1;
    if (is_valid_user_class($class_id)) {
        $where[] = 'u.class = ' . $class_id;
    }
}

$status_map = array(
    '1' => 'patron',
    '2' => 'warned',
    '4' => 'loyal_seed',
    '5' => 'rhetoric',
    '6' => 'keeper',
    '7' => 'low_ratio',
    '8' => 'dj',
    '9' => 'king',
    '10' => 'king',
    '11' => 'person_designer',
    '13' => 'journalist',
    '15' => 'translator',
    '16' => 'art_studio',
    '17' => 'honorary',
    '19' => 'developer',
    '20' => 'declamator',
);

if (isset($status_map[$status])) {
    $status_key = sqlesc($status_map[$status]);
    $where[] = "EXISTS (SELECT 1 FROM user_status_assignments AS usa WHERE usa.userid = u.id AND usa.status_key = $status_key)";
    if ($status === '10') {
        $where[] = "u.gender = '2'";
    }
}

$where_sql = implode(' AND ', $where);

$sort_fields = array(
    '0' => 'u.added',
    '1' => 'torrent_count',
    '2' => 'u.simpaty',
    '3' => 'comment_count',
    '4' => 'u.uploaded',
    '5' => 'u.downloaded',
    '6' => 'u.bonus',
    '7' => 'u.downloaded',
);
$sort_sql = isset($sort_fields[$sort]) ? $sort_fields[$sort] : $sort_fields['0'];
$order_sql = ($order === '1') ? 'ASC' : 'DESC';
$perpage = 30;
$page = isset($_GET['page']) ? max(0, (int)$_GET['page']) : 0;

$count_res = sql_query("SELECT COUNT(*) FROM users AS u WHERE $where_sql") or sqlerr(__FILE__, __LINE__);
$count_row = mysqli_fetch_row($count_res);
$count = (int)($count_row[0] ?? 0);
$pages = max(1, (int)ceil($count / $perpage));
if ($page >= $pages) {
    $page = $pages - 1;
}
$offset = $page * $perpage;

$res = sql_query("
    SELECT
        u.*,
        c.name AS country_name,
        c.flagpic,
        (SELECT COUNT(*) FROM torrents AS t WHERE t.owner = u.id) AS torrent_count,
        (SELECT COUNT(*) FROM comments AS cm WHERE cm.user = u.id) AS comment_count
    FROM users AS u
    LEFT JOIN countries AS c ON c.id = u.country
    WHERE $where_sql
    ORDER BY $sort_sql $order_sql, u.id DESC
    LIMIT $offset, $perpage
") or sqlerr(__FILE__, __LINE__);

$countries = array();
$country_res = sql_query('SELECT id, name FROM countries ORDER BY name ASC') or sqlerr(__FILE__, __LINE__);
while ($row = mysqli_fetch_assoc($country_res)) {
    $countries[] = $row;
}

$hide_right_blocks = true;
stdhead('Список пользователей - Поиск пользователей');
?>
<div class="bx2">
    <div style="padding:0 5px 7px 0;">
        <h1>
            <span class="bulet"></span>
            <a href="/users.php" class="sbab">Список пользователей - Поиск пользователей</a>
        </h1>
    </div>
    <div class="mn1_menu">
        <form method="get" action="" name="u_srch" id="br_srch">
            <ul class="men">
                <li class="img">
                    <a href="/users.php">
                        <img src="/pic/p_users.jpg" height="75" class="block w200" alt="">
                    </a>
                </li>
                <li class="tp">Поиск пользователей</li>
                <li class="img">
                    <dl>
                        <dt>Имя</dt>
                        <dd><input class="w120" type="text" name="s1" value="<?= users_h($search_name) ?>"></dd>
                    </dl>
                    <dl>
                        <dt>Город</dt>
                        <dd><input class="w120" type="text" name="s2" value="<?= users_h($search_city) ?>"></dd>
                    </dl>
                    <dl>
                        <dt>Фильм</dt>
                        <dd><input class="w120" type="text" name="s3" value="<?= users_h($search_movie) ?>"></dd>
                    </dl>
                    <dl>
                        <dt>Персона</dt>
                        <dd><input class="w120" type="text" name="s4" value="<?= users_h($search_person) ?>"></dd>
                    </dl>
                    <dl>
                        <dt>Страна</dt>
                        <dd>
                            <select name="co" class="w120">
                                <option value="">Все</option>
                                <?php foreach ($countries as $ct) { ?>
                                    <option value="<?= (int)$ct['id'] ?>"<?= users_selected($country, $ct['id']) ?>><?= users_h($ct['name']) ?></option>
                                <?php } ?>
                            </select>
                        </dd>
                    </dl>
                    <dl>
                        <dt>Пол</dt>
                        <dd>
                            <select name="gn" class="w120">
                                <option value="">Все</option>
                                <option value="2"<?= users_selected($gender, '2') ?>>Женский</option>
                                <option value="1"<?= users_selected($gender, '1') ?>>Мужской</option>
                            </select>
                        </dd>
                    </dl>
                    <dl>
                        <dt>Фото</dt>
                        <dd>
                            <select name="f" class="w120">
                                <option value="1"<?= users_selected($photo, '1') ?>>Все</option>
                                <option value="2"<?= users_selected($photo, '2') ?>>Только с фото</option>
                            </select>
                        </dd>
                    </dl>
                    <dl>
                        <dt>Звание</dt>
                        <dd>
                            <select name="c" class="w120">
                                <option value="">Все</option>
                                <?php for ($i = UC_USER; $i <= UC_SYSOP; $i++) { ?>
                                    <option value="<?= $i + 1 ?>"<?= users_selected($class, $i + 1) ?>><?= users_h(get_user_class_name($i)) ?></option>
                                <?php } ?>
                            </select>
                        </dd>
                    </dl>
                    <dl>
                        <dt>Статус</dt>
                        <dd>
                            <select name="g" class="w120">
                                <option value="">Все</option>
                                <option value="5"<?= users_selected($status, '5') ?>>Риторик</option>
                                <option value="4"<?= users_selected($status, '4') ?>>Верный сид</option>
                                <option value="9"<?= users_selected($status, '9') ?>>Король трекера</option>
                                <option value="10"<?= users_selected($status, '10') ?>>Королева трекера</option>
                                <option value="1"<?= users_selected($status, '1') ?>>Меценат</option>
                                <option value="2"<?= users_selected($status, '2') ?>>Предупрежден</option>
                                <option value="7"<?= users_selected($status, '7') ?>>Предупрежден 1 Торрент</option>
                                <option value="8"<?= users_selected($status, '8') ?>>ДиДжей</option>
                                <option value="6"<?= users_selected($status, '6') ?>>Хранитель раздач</option>
                                <option value="11"<?= users_selected($status, '11') ?>>Оформитель персон</option>
                                <option value="13"<?= users_selected($status, '13') ?>>Журналист</option>
                                <option value="15"<?= users_selected($status, '15') ?>>Переводчик</option>
                                <option value="16"<?= users_selected($status, '16') ?>>Арт - Студия</option>
                                <option value="17"<?= users_selected($status, '17') ?>>Почетный</option>
                                <option value="19"<?= users_selected($status, '19') ?>>Разработчик</option>
                                <option value="20"<?= users_selected($status, '20') ?>>Декламатор</option>
                            </select>
                        </dd>
                    </dl>
                </li>
                <li class="img">
                    <input type="submit" value="Найти пользователя" class="w200">
                </li>
                <li class="tp">Сортировка результата</li>
                <li class="img">
                    <dl>
                        <dt>
                            <select name="s" class="w100">
                                <option value="0"<?= users_selected($sort, '0') ?>>Регистрация</option>
                                <option value="1"<?= users_selected($sort, '1') ?>>Раздачи</option>
                                <option value="2"<?= users_selected($sort, '2') ?>>Репутация</option>
                                <option value="3"<?= users_selected($sort, '3') ?>>Комментарии</option>
                                <option value="4"<?= users_selected($sort, '4') ?>>Залил</option>
                                <option value="5"<?= users_selected($sort, '5') ?>>Скачал</option>
                                <option value="6"<?= users_selected($sort, '6') ?>>Время сида</option>
                                <option value="7"<?= users_selected($sort, '7') ?>>Время пира</option>
                            </select>
                        </dt>
                        <dd>
                            <select name="o" class="w80">
                                <option value="0"<?= users_selected($order, '0') ?>>по убыв</option>
                                <option value="1"<?= users_selected($order, '1') ?>>по возр</option>
                            </select>
                        </dd>
                    </dl>
                </li>
                <li class="img">
                    <input type="submit" value="Найти пользователя" class="w200">
                </li>
                <li class="tp"><h2>Информация</h2></li>
                <li class="justify">
                    <p>Поиск пользователей - По ключевым словам и критериям. Вы можете найти пользователей по имени и указанным в профиле данным, таким как страна, персоны, города и фильмы.</p>
                </li>
            </ul>
        </form>
    </div>
    <div class="mn1_content">
        <div class="bx2_0">
            <?php if (mysqli_num_rows($res) > 0) { ?>
                <?php while ($user = mysqli_fetch_assoc($res)) { ?>
                    <?php
                    $uid = (int)$user['id'];
                    $avatar = !empty($user['avatar']) ? users_h($user['avatar']) : '/pic/default_avatar.gif';
                    $country_id = (int)($user['country'] ?? 0);
                    $icons = function_exists('get_user_icons') ? get_user_icons($user) : '';
                    $registered = users_date_text($user['added']);
                    $last_access = users_date_text($user['last_access']);
                    $torrent_count = (int)$user['torrent_count'];
                    $comment_count = (int)$user['comment_count'];
                    $reputation = (int)$user['simpaty'];
                    ?>
                    <div class="bx5x5">
                        <img class="imgg rot180" src="<?= $avatar ?>" alt="">
                        <div class="ptable_r">
                            <a href="/sendmessage.php?receiver=<?= $uid ?>" class="sba">Отправить сообщение</a>
                            <br>
                            <a href="/bookmarks.php?type=3&amp;add=<?= $uid ?>&amp;hash4u=<?= users_h($CURUSER['hash4u'] ?? ($CURUSER['logout_hash'] ?? '')) ?>" class="sba">Добавить в закладки</a>
                        </div>
                        <div class="ptable">
                            <ul>
                                <li>
                                    <?php if ($country_id > 0) { ?>
                                        <img src="/pic/emty.gif" class="i2 c<?= $country_id ?>" alt="<?= users_h($user['country_name'] ?? '') ?>">
                                    <?php } ?>
                                    <a href="/userdetails.php?id=<?= $uid ?>" class="u<?= (int)$user['class'] ?>"><?= users_h($user['username']) ?></a>
                                    <?= $icons ?>
                                </li>
                                <?php if ($registered !== '') { ?>
                                    <li>Зарегистрирован <?= users_h($registered) ?></li>
                                <?php } ?>
                                <?php if ($last_access !== '') { ?>
                                    <li>Был на трекере <?= users_h($last_access) ?></li>
                                <?php } ?>
                                <?php if ((float)$user['uploaded'] > 0 || (float)$user['downloaded'] > 0) { ?>
                                    <li>Залил <?= users_h(users_size($user['uploaded'])) ?>, скачал <?= users_h(users_size($user['downloaded'])) ?></li>
                                <?php } ?>
                                <?php if ($torrent_count > 0 || $comment_count > 0 || $reputation !== 0) { ?>
                                    <li>Раздач <?= $torrent_count ?>, комментариев <?= $comment_count ?>, репутация <?= $reputation ?></li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="bx5x5">Пользователи не найдены.</div>
            <?php } ?>
        </div>
        <?= users_paginator($count, $perpage, $page) ?>
    </div>
</div>
<div class="bx2_0">
    <ul class="men">
        <li class="tp2 center">
            Кто ОнЛайн здесь, на этой странице [
            <a class="sba" href="/pay.php">помочь проекту</a>
            ]
        </li>
        <li>
            <div class="pad5x5">
                <?= users_online_html() ?>
            </div>
        </li>
    </ul>
</div>
<?php
stdfoot();

?>
