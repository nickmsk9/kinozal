<?php

if (!defined('ADMIN_FILE')) {
    die('Illegal File Access');
}

$prefix = 'orbital';
$blocksTable = $prefix . '_blocks';

function blocks_admin_h($value): string
{
    return htmlspecialchars_uni((string)$value);
}

function blocks_admin_redirect(): void
{
    global $admin_file;

    header('Location: ' . $admin_file . '.php?op=BlocksAdmin');
    exit;
}

function blocks_admin_positions(): array
{
    return array(
        'l' => 'Слева',
        'c' => 'По центру сверху',
        'd' => 'По центру снизу',
        'r' => 'Справа',
        'b' => 'Верхний баннер',
        'f' => 'Нижний баннер',
    );
}

function blocks_admin_views(): array
{
    return array(
        0 => 'Все посетители',
        1 => 'Только пользователи',
        2 => 'Только модераторы и администраторы',
        3 => 'Только гости',
    );
}

function blocks_admin_files(): array
{
    $files = glob(ROOT_PATH . 'blocks/block-*.php') ?: array();
    $result = array();

    foreach ($files as $path) {
        $file = basename($path);
        $result[$file] = ucwords(str_replace(array('block-', '-', '_', '.php'), array('', ' ', ' ', ''), $file));
    }

    ksort($result, SORT_NATURAL | SORT_FLAG_CASE);
    return $result;
}

function blocks_admin_file_title($file): string
{
    $titles = array(
        'block-birthday.php' => 'День рождения',
        'block-cups.php' => 'Переходящие кубки',
        'block-news.php' => 'Новости',
        'block-online.php' => 'Пользователи онлайн',
        'block-pay.php' => 'Меценаты',
        'block-radio.php' => 'Радио Кинозал',
        'block-releases.php' => 'Релизы',
        'block-stats.php' => 'Статистика трекера',
        'block-top-torrents.php' => 'Топ раздач',
        'block-uarch.php' => 'Улыбка',
    );

    return $titles[$file] ?? (blocks_admin_files()[$file] ?? $file);
}

function blocks_admin_normalize_weights(): void
{
    global $blocksTable;

    foreach (array_keys(blocks_admin_positions()) as $position) {
        $res = sql_query("
            SELECT bid
            FROM $blocksTable
            WHERE bposition = " . sqlesc($position) . "
            ORDER BY weight ASC, bid ASC
        ") or sqlerr(__FILE__, __LINE__);

        $weight = 1;
        while ($row = mysqli_fetch_assoc($res)) {
            sql_query("
                UPDATE $blocksTable
                SET weight = $weight
                WHERE bid = " . (int)$row['bid'] . "
            ") or sqlerr(__FILE__, __LINE__);
            $weight++;
        }
    }
}

function blocks_admin_next_weight($position): int
{
    global $blocksTable;

    $res = sql_query("
        SELECT COALESCE(MAX(weight), 0) + 1 AS next_weight
        FROM $blocksTable
        WHERE bposition = " . sqlesc($position) . "
    ") or sqlerr(__FILE__, __LINE__);
    $row = mysqli_fetch_assoc($res);

    return max(1, (int)($row['next_weight'] ?? 1));
}

function blocks_admin_add_file($file, $position = 'r', $which = 'all'): void
{
    global $blocksTable;

    $files = blocks_admin_files();
    if (!isset($files[$file])) {
        stderr('Ошибка', 'Файл блока не найден.');
    }

    $exists = sql_query("
        SELECT bid
        FROM $blocksTable
        WHERE blockfile = " . sqlesc($file) . "
        LIMIT 1
    ") or sqlerr(__FILE__, __LINE__);

    if (mysqli_fetch_assoc($exists)) {
        return;
    }

    $positions = blocks_admin_positions();
    if (!isset($positions[$position])) {
        $position = 'r';
    }

    $weight = blocks_admin_next_weight($position);
    sql_query("
        INSERT INTO $blocksTable
            (bkey, title, content, bposition, weight, active, time, blockfile, view, expire, action, which, allow_hide)
        VALUES
            (
                '',
                " . sqlesc(blocks_admin_file_title($file)) . ",
                '',
                " . sqlesc($position) . ",
                $weight,
                1,
                '0',
                " . sqlesc($file) . ",
                0,
                '0',
                'd',
                " . sqlesc($which) . ",
                'yes'
            )
    ") or sqlerr(__FILE__, __LINE__);
}

function blocks_admin_migrate_legacy(): void
{
    global $blocksTable;

    $res = sql_query("
        SELECT blockfile
        FROM $blocksTable
        WHERE blockfile IN ('block-uarch.php', 'block-radio.php')
    ") or sqlerr(__FILE__, __LINE__);
    $installed = array();

    while ($row = mysqli_fetch_assoc($res)) {
        $installed[(string)$row['blockfile']] = true;
    }

    if (!isset($installed['block-uarch.php']) && is_file(ROOT_PATH . 'blocks/block-uarch.php')) {
        blocks_admin_add_file('block-uarch.php', 'r', 'all');
    }

    if (!isset($installed['block-radio.php']) && is_file(ROOT_PATH . 'blocks/block-radio.php')) {
        blocks_admin_add_file('block-radio.php', 'r', 'all');
    }
}

function blocks_admin_expire_old(): void
{
    global $blocksTable;

    $now = time();
    $res = sql_query("
        SELECT bid, action
        FROM $blocksTable
        WHERE CAST(expire AS UNSIGNED) > 0
          AND CAST(expire AS UNSIGNED) <= $now
    ") or sqlerr(__FILE__, __LINE__);

    while ($row = mysqli_fetch_assoc($res)) {
        $bid = (int)$row['bid'];

        if ((string)$row['action'] === 'r') {
            sql_query("DELETE FROM $blocksTable WHERE bid = $bid") or sqlerr(__FILE__, __LINE__);
        } else {
            sql_query("UPDATE $blocksTable SET active = 0, expire = '0' WHERE bid = $bid") or sqlerr(__FILE__, __LINE__);
        }
    }
}

function blocks_admin_scope($mode, $modules): string
{
    if ($mode === 'all') {
        return 'all';
    }

    if ($mode === 'home') {
        return 'ihome';
    }

    $items = preg_split('/[\s,;]+/', strtolower((string)$modules), -1, PREG_SPLIT_NO_EMPTY);
    $items = array_unique(array_filter(array_map(function ($item) {
        return preg_replace('/[^a-z0-9_-]/', '', basename($item, '.php'));
    }, $items)));

    return $items ? implode(',', $items) . ',' : 'all';
}

function blocks_admin_scope_fields($which): array
{
    $which = trim((string)$which, " \t\n\r\0\x0B,");

    if ($which === 'all' || $which === '') {
        return array('all', '');
    }

    if ($which === 'home' || $which === 'ihome') {
        return array('home', '');
    }

    return array('custom', str_replace(',', ', ', $which));
}

function blocks_admin_navigation(): void
{
    global $admin_file;
    ?>
    <div class="pad0x0x5x0">
        <ul class="lis">
            <li class="mn"><a href="<?=$admin_file;?>.php?op=BlocksAdmin">Блоки и баннеры</a></li>
            <li><a href="<?=$admin_file;?>.php?op=BlocksAdmin&amp;edit=0#block-form">Добавить блок</a></li>
        </ul>
    </div>
    <?php
}

blocks_admin_expire_old();
blocks_admin_migrate_legacy();

$requestMethod = (string)($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($requestMethod === 'POST') {
    $action = trim((string)($_POST['block_action'] ?? ''));

    if ($action === 'install') {
        blocks_admin_add_file(basename((string)($_POST['blockfile'] ?? '')));
        blocks_admin_redirect();
    }

    if ($action === 'normalize') {
        blocks_admin_normalize_weights();
        blocks_admin_redirect();
    }

    $bid = (int)($_POST['bid'] ?? 0);

    if ($action === 'toggle' && is_valid_id($bid)) {
        sql_query("
            UPDATE $blocksTable
            SET active = IF(active = 1, 0, 1)
            WHERE bid = $bid
        ") or sqlerr(__FILE__, __LINE__);
        blocks_admin_redirect();
    }

    if ($action === 'delete' && is_valid_id($bid)) {
        sql_query("DELETE FROM $blocksTable WHERE bid = $bid AND bkey = '' LIMIT 1") or sqlerr(__FILE__, __LINE__);
        blocks_admin_normalize_weights();
        blocks_admin_redirect();
    }

    if (($action === 'up' || $action === 'down') && is_valid_id($bid)) {
        $res = sql_query("
            SELECT bid, bposition, weight
            FROM $blocksTable
            WHERE bid = $bid
            LIMIT 1
        ") or sqlerr(__FILE__, __LINE__);
        $current = mysqli_fetch_assoc($res);

        if ($current) {
            $operator = $action === 'up' ? '<' : '>';
            $direction = $action === 'up' ? 'DESC' : 'ASC';
            $otherRes = sql_query("
                SELECT bid, weight
                FROM $blocksTable
                WHERE bposition = " . sqlesc($current['bposition']) . "
                  AND weight $operator " . (int)$current['weight'] . "
                ORDER BY weight $direction, bid $direction
                LIMIT 1
            ") or sqlerr(__FILE__, __LINE__);
            $other = mysqli_fetch_assoc($otherRes);

            if ($other) {
                sql_query("
                    UPDATE $blocksTable
                    SET weight = CASE
                        WHEN bid = $bid THEN " . (int)$other['weight'] . "
                        WHEN bid = " . (int)$other['bid'] . " THEN " . (int)$current['weight'] . "
                        ELSE weight
                    END
                    WHERE bid IN ($bid, " . (int)$other['bid'] . ")
                ") or sqlerr(__FILE__, __LINE__);
            }
        }

        blocks_admin_redirect();
    }

    if ($action === 'save') {
        $title = trim((string)($_POST['title'] ?? ''));
        $content = (string)($_POST['content'] ?? '');
        $blockfile = basename(trim((string)($_POST['blockfile'] ?? '')));
        $position = trim((string)($_POST['bposition'] ?? 'r'));
        $active = !empty($_POST['active']) ? 1 : 0;
        $allowHide = !empty($_POST['allow_hide']) ? 'yes' : 'no';
        $view = (int)($_POST['view'] ?? 0);
        $expireDays = max(0, min(999, (int)($_POST['expire_days'] ?? 0)));
        $expire = $expireDays > 0 ? time() + ($expireDays * 86400) : 0;
        $expireAction = ($_POST['expire_action'] ?? 'd') === 'r' ? 'r' : 'd';
        $which = blocks_admin_scope(
            (string)($_POST['scope'] ?? 'all'),
            (string)($_POST['modules'] ?? '')
        );

        if (!isset(blocks_admin_positions()[$position])) {
            $position = 'r';
        }

        if (!isset(blocks_admin_views()[$view])) {
            $view = 0;
        }

        if ($blockfile !== '' && !isset(blocks_admin_files()[$blockfile])) {
            stderr('Ошибка', 'Выбранный файл блока не найден.');
        }

        if ($title === '') {
            $title = $blockfile !== '' ? blocks_admin_file_title($blockfile) : 'Новый блок';
        }

        if ($blockfile === '' && trim($content) === '') {
            stderr('Ошибка', 'HTML-блок не может быть пустым.');
        }

        if (is_valid_id($bid)) {
            $oldRes = sql_query("SELECT bposition, weight FROM $blocksTable WHERE bid = $bid LIMIT 1") or sqlerr(__FILE__, __LINE__);
            $old = mysqli_fetch_assoc($oldRes);

            if (!$old) {
                stderr('Ошибка', 'Блок не найден.');
            }

            $weight = (string)$old['bposition'] === $position
                ? (int)$old['weight']
                : blocks_admin_next_weight($position);

            sql_query("
                UPDATE $blocksTable
                SET title = " . sqlesc($title) . ",
                    content = " . sqlesc($content) . ",
                    bposition = " . sqlesc($position) . ",
                    weight = $weight,
                    active = $active,
                    blockfile = " . sqlesc($blockfile) . ",
                    view = $view,
                    expire = " . sqlesc((string)$expire) . ",
                    action = " . sqlesc($expireAction) . ",
                    which = " . sqlesc($which) . ",
                    allow_hide = " . sqlesc($allowHide) . "
                WHERE bid = $bid
            ") or sqlerr(__FILE__, __LINE__);
        } else {
            $weight = blocks_admin_next_weight($position);
            sql_query("
                INSERT INTO $blocksTable
                    (bkey, title, content, bposition, weight, active, time, blockfile, view, expire, action, which, allow_hide)
                VALUES
                    (
                        '',
                        " . sqlesc($title) . ",
                        " . sqlesc($content) . ",
                        " . sqlesc($position) . ",
                        $weight,
                        $active,
                        '0',
                        " . sqlesc($blockfile) . ",
                        $view,
                        " . sqlesc((string)$expire) . ",
                        " . sqlesc($expireAction) . ",
                        " . sqlesc($which) . ",
                        " . sqlesc($allowHide) . "
                    )
            ") or sqlerr(__FILE__, __LINE__);
        }

        blocks_admin_normalize_weights();
        blocks_admin_redirect();
    }
}

if ($op === 'BlocksAdmin') {
    blocks_admin_normalize_weights();
    blocks_admin_navigation();

    $res = sql_query("
        SELECT *
        FROM $blocksTable
        ORDER BY FIELD(bposition, 'b', 'l', 'c', 'r', 'd', 'f'), weight ASC, bid ASC
    ") or sqlerr(__FILE__, __LINE__);
    $blocks = array();
    $installedFiles = array();
    $availableFiles = blocks_admin_files();

    while ($row = mysqli_fetch_assoc($res)) {
        $blocks[] = $row;
        if ((string)$row['blockfile'] !== '') {
            $installedFiles[(string)$row['blockfile']] = (int)$row['bid'];
        }
    }

    $editId = (int)($_GET['edit'] ?? -1);
    $edit = array(
        'bid' => 0,
        'title' => '',
        'content' => '',
        'bposition' => 'r',
        'active' => 1,
        'blockfile' => '',
        'view' => 0,
        'expire' => 0,
        'action' => 'd',
        'which' => 'all',
        'allow_hide' => 'yes',
    );

    if ($editId > 0) {
        foreach ($blocks as $row) {
            if ((int)$row['bid'] === $editId) {
                $edit = $row;
                break;
            }
        }
    }

    list($scope, $modules) = blocks_admin_scope_fields($edit['which']);
    $expireDays = (int)$edit['expire'] > time()
        ? max(1, (int)ceil(((int)$edit['expire'] - time()) / 86400))
        : 0;
    ?>

    <div class="mn_wrap">
        <div class="tp1_title">
            <b>Блоки и баннеры</b>
            <span class="floatright">Всего: <?=count($blocks);?></span>
        </div>
        <div class="tp1_body">
            <table class="brd w100p">
                <tr>
                    <th>Название</th>
                    <th class="center">Позиция</th>
                    <th class="center">Тип</th>
                    <th class="center">Показывать</th>
                    <th class="center">Статус</th>
                    <th class="center">Порядок</th>
                    <th class="center">Управление</th>
                </tr>
                <?php foreach ($blocks as $block) {
                    $bid = (int)$block['bid'];
                    $isFile = (string)$block['blockfile'] !== '';
                    $fileMissing = $isFile && !isset($availableFiles[(string)$block['blockfile']]);
                    $scopeFields = blocks_admin_scope_fields($block['which']);
                    $scopeLabel = $scopeFields[0] === 'all'
                        ? 'Везде'
                        : ($scopeFields[0] === 'home' ? 'Главная' : $scopeFields[1]);
                ?>
                    <tr class="bov">
                        <td>
                            <b><?=blocks_admin_h($block['title']);?></b>
                            <?php if ($isFile) { ?>
                                <div class="small"><?=blocks_admin_h($block['blockfile']);?></div>
                                <?php if ($fileMissing) { ?>
                                    <div class="red small">Файл отсутствует</div>
                                <?php } ?>
                            <?php } ?>
                        </td>
                        <td class="center nw"><?=blocks_admin_h(blocks_admin_positions()[$block['bposition']] ?? $block['bposition']);?></td>
                        <td class="center"><?=$isFile ? 'Файл' : 'HTML';?></td>
                        <td class="center" title="<?=blocks_admin_h($scopeLabel);?>">
                            <?=blocks_admin_h(blocks_admin_views()[(int)$block['view']] ?? 'Все');?>
                            <div class="small"><?=blocks_admin_h($scopeLabel);?></div>
                        </td>
                        <td class="center">
                            <span class="<?=(int)$block['active'] === 1 ? 'green' : 'red';?>">
                                <?=(int)$block['active'] === 1 ? 'Включён' : 'Выключен';?>
                            </span>
                        </td>
                        <td class="center nw">
                            <form method="post" action="<?=$admin_file;?>.php?op=BlocksAdmin" style="display:inline;">
                                <input type="hidden" name="block_action" value="up">
                                <input type="hidden" name="bid" value="<?=$bid;?>">
                                <input type="submit" class="buttonS" value=" ↑ " title="Выше">
                            </form>
                            <form method="post" action="<?=$admin_file;?>.php?op=BlocksAdmin" style="display:inline;">
                                <input type="hidden" name="block_action" value="down">
                                <input type="hidden" name="bid" value="<?=$bid;?>">
                                <input type="submit" class="buttonS" value=" ↓ " title="Ниже">
                            </form>
                        </td>
                        <td class="center nw">
                            <a class="buttonS" href="<?=$admin_file;?>.php?op=BlocksAdmin&amp;edit=<?=$bid;?>#block-form">Изменить</a>
                            <form method="post" action="<?=$admin_file;?>.php?op=BlocksAdmin" style="display:inline;">
                                <input type="hidden" name="block_action" value="toggle">
                                <input type="hidden" name="bid" value="<?=$bid;?>">
                                <input type="submit" class="buttonS" value="<?=(int)$block['active'] === 1 ? 'Выкл.' : 'Вкл.';?>">
                            </form>
                            <?php if ((string)$block['bkey'] === '') { ?>
                                <form method="post" action="<?=$admin_file;?>.php?op=BlocksAdmin" style="display:inline;" onsubmit="return confirm('Удалить этот блок?');">
                                    <input type="hidden" name="block_action" value="delete">
                                    <input type="hidden" name="bid" value="<?=$bid;?>">
                                    <input type="submit" class="buttonS" value="Удалить">
                                </form>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </table>

            <div class="right pad5x5">
                <form method="post" action="<?=$admin_file;?>.php?op=BlocksAdmin">
                    <input type="hidden" name="block_action" value="normalize">
                    <input type="submit" class="buttonS" value=" Исправить порядок ">
                </form>
            </div>
        </div>
    </div>

    <div class="mn_wrap">
        <div class="tp1_title"><b>Доступные файловые блоки</b></div>
        <div class="tp1_body">
            <table class="tables2 w100p">
                <?php foreach ($availableFiles as $file => $label) { ?>
                    <tr>
                        <td><b><?=blocks_admin_h(blocks_admin_file_title($file));?></b> <span class="small"><?=blocks_admin_h($file);?></span></td>
                        <td class="right nw">
                            <?php if (isset($installedFiles[$file])) { ?>
                                <span class="green">Подключён</span>
                                <a class="buttonS" href="<?=$admin_file;?>.php?op=BlocksAdmin&amp;edit=<?=$installedFiles[$file];?>#block-form">Настроить</a>
                            <?php } else { ?>
                                <form method="post" action="<?=$admin_file;?>.php?op=BlocksAdmin">
                                    <input type="hidden" name="block_action" value="install">
                                    <input type="hidden" name="blockfile" value="<?=blocks_admin_h($file);?>">
                                    <input type="submit" class="buttonS" value=" Подключить ">
                                </form>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    </div>

    <div class="mn_wrap" id="block-form">
        <div class="tp1_title"><b><?=$editId > 0 ? 'Изменить блок' : 'Добавить блок';?></b></div>
        <div class="tp1_body">
            <form method="post" action="<?=$admin_file;?>.php?op=BlocksAdmin">
                <input type="hidden" name="block_action" value="save">
                <input type="hidden" name="bid" value="<?=(int)$edit['bid'];?>">
                <table class="tables1 w100p">
                    <tr>
                        <td class="rowhead w150"><label for="block-title">Название</label></td>
                        <td><input id="block-title" type="text" name="title" class="w300" maxlength="60" value="<?=blocks_admin_h($edit['title']);?>"></td>
                    </tr>
                    <tr>
                        <td class="rowhead"><label for="block-file">Файл блока</label></td>
                        <td>
                            <select id="block-file" name="blockfile" class="w300">
                                <option value="">HTML-блок</option>
                                <?php foreach ($availableFiles as $file => $label) { ?>
                                    <option value="<?=blocks_admin_h($file);?>" <?=$edit['blockfile'] === $file ? 'selected' : '';?>><?=blocks_admin_h(blocks_admin_file_title($file));?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="rowhead"><label for="block-content">HTML-содержимое</label></td>
                        <td><textarea id="block-content" name="content" rows="8" class="w98p"><?=blocks_admin_h($edit['content']);?></textarea></td>
                    </tr>
                    <tr>
                        <td class="rowhead"><label for="block-position">Позиция</label></td>
                        <td>
                            <select id="block-position" name="bposition" class="w300">
                                <?php foreach (blocks_admin_positions() as $key => $label) { ?>
                                    <option value="<?=$key;?>" <?=$edit['bposition'] === $key ? 'selected' : '';?>><?=blocks_admin_h($label);?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="rowhead">Страницы</td>
                        <td>
                            <label><input type="radio" name="scope" value="all" <?=$scope === 'all' ? 'checked' : '';?>> Везде</label>
                            <label><input type="radio" name="scope" value="home" <?=$scope === 'home' ? 'checked' : '';?>> Только главная</label>
                            <label><input type="radio" name="scope" value="custom" <?=$scope === 'custom' ? 'checked' : '';?>> Указать модули</label>
                            <div class="pad5x5">
                                <input type="text" name="modules" class="w300" value="<?=blocks_admin_h($modules);?>" placeholder="browse, details, radio">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="rowhead"><label for="block-view">Кто видит</label></td>
                        <td>
                            <select id="block-view" name="view" class="w300">
                                <?php foreach (blocks_admin_views() as $key => $label) { ?>
                                    <option value="<?=$key;?>" <?=(int)$edit['view'] === $key ? 'selected' : '';?>><?=blocks_admin_h($label);?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="rowhead">Настройки</td>
                        <td>
                            <label><input type="checkbox" name="active" value="1" <?=(int)$edit['active'] === 1 ? 'checked' : '';?>> Включён</label>
                            <label><input type="checkbox" name="allow_hide" value="1" <?=$edit['allow_hide'] === 'yes' ? 'checked' : '';?>> Можно свернуть</label>
                        </td>
                    </tr>
                    <tr>
                        <td class="rowhead"><label for="block-expire">Срок, дней</label></td>
                        <td>
                            <input id="block-expire" type="text" name="expire_days" value="<?=$expireDays;?>" maxlength="3" class="w60">
                            <select name="expire_action">
                                <option value="d" <?=$edit['action'] !== 'r' ? 'selected' : '';?>>затем выключить</option>
                                <option value="r" <?=$edit['action'] === 'r' ? 'selected' : '';?>>затем удалить</option>
                            </select>
                            <span class="small">0 — без ограничения</span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="right">
                            <input type="submit" class="buttonS" value=" Сохранить ">
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
    <?php
}
