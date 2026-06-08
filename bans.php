<?php

require_once __DIR__ . '/include/bittorrent.php';

dbconn(false);
loggedinorreturn();

if (get_user_class() < UC_MODERATOR) {
    stderr($tracker_lang['error'], $tracker_lang['access_denied']);
}

function bans_h($value): string
{
    return htmlspecialchars_uni((string)$value);
}

function bans_is_ipv4($ip): bool
{
    return filter_var((string)$ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
}

function bans_ip_to_db($ip): string
{
    return sprintf('%u', ip2long((string)$ip));
}

function bans_db_to_ip($ip): string
{
    $ip = trim((string)$ip);

    if ($ip === '' || !ctype_digit($ip)) {
        return '';
    }

    return long2ip((int)$ip);
}

function bans_user_link($id, $username): string
{
    $id = (int)$id;
    $username = trim((string)$username);

    if ($id <= 0 || $username === '') {
        return 'Пользователь удалён';
    }

    return '<a href="/userdetails.php?id=' . $id . '">' . bans_h($username) . '</a>';
}

function bans_redirect(): void
{
    header('Location: /bans.php');
    exit;
}

$isAdministrator = get_user_class() >= UC_ADMINISTRATOR;
$requestMethod = (string)($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($requestMethod === 'POST') {
    if (!$isAdministrator) {
        stderr($tracker_lang['error'], $tracker_lang['access_denied']);
    }

    $action = trim((string)($_POST['action'] ?? 'add'));

    if ($action === 'remove') {
        $banId = (int)($_POST['ban_id'] ?? 0);

        if (!is_valid_id($banId)) {
            stderr($tracker_lang['error'], 'Некорректный идентификатор блокировки.');
        }

        $res = sql_query("
            SELECT `first`, `last`
            FROM `bans`
            WHERE `id` = $banId
            LIMIT 1
        ") or sqlerr(__FILE__, __LINE__);
        $ban = mysqli_fetch_assoc($res);

        if (!$ban) {
            stderr($tracker_lang['error'], 'Блокировка не найдена.');
        }

        $firstIp = bans_db_to_ip($ban['first']);
        $lastIp = bans_db_to_ip($ban['last']);

        sql_query("DELETE FROM `bans` WHERE `id` = $banId LIMIT 1") or sqlerr(__FILE__, __LINE__);

        $rangeText = $firstIp === $lastIp ? $firstIp : $firstIp . ' - ' . $lastIp;
        write_log(
            'IP-блокировка №' . $banId . ' (' . $rangeText . ') снята пользователем ' . $CURUSER['username'] . '.'
        );

        bans_redirect();
    }

    if ($action !== 'add') {
        stderr($tracker_lang['error'], 'Неизвестное действие.');
    }

    $firstIp = trim((string)($_POST['first'] ?? ''));
    $lastIp = trim((string)($_POST['last'] ?? ''));
    $comment = trim((string)($_POST['comment'] ?? ''));

    if ($lastIp === '') {
        $lastIp = $firstIp;
    }

    if ($firstIp === '' || $comment === '') {
        stderr($tracker_lang['error'], 'Укажите IP-адрес и причину блокировки.');
    }

    if (!bans_is_ipv4($firstIp) || !bans_is_ipv4($lastIp)) {
        stderr($tracker_lang['error'], 'Укажите корректные IPv4-адреса.');
    }

    if (mb_strlen($comment, 'UTF-8') > 255) {
        stderr($tracker_lang['error'], 'Причина блокировки не должна превышать 255 символов.');
    }

    $firstLong = bans_ip_to_db($firstIp);
    $lastLong = bans_ip_to_db($lastIp);

    if ((float)$firstLong > (float)$lastLong) {
        stderr($tracker_lang['error'], 'Начальный IP-адрес не может быть больше конечного.');
    }

    $currentIp = getip();
    if (bans_is_ipv4($currentIp)) {
        $currentLong = bans_ip_to_db($currentIp);

        if ((float)$currentLong >= (float)$firstLong && (float)$currentLong <= (float)$lastLong) {
            stderr($tracker_lang['error'], 'Нельзя добавить диапазон, содержащий ваш текущий IP-адрес.');
        }
    }

    $overlapRes = sql_query("
        SELECT `id`, `first`, `last`
        FROM `bans`
        WHERE $firstLong <= `last`
          AND $lastLong >= `first`
        ORDER BY `first`
        LIMIT 1
    ") or sqlerr(__FILE__, __LINE__);
    $overlap = mysqli_fetch_assoc($overlapRes);

    if ($overlap) {
        $overlapFirst = bans_db_to_ip($overlap['first']);
        $overlapLast = bans_db_to_ip($overlap['last']);
        $overlapText = $overlapFirst === $overlapLast
            ? $overlapFirst
            : $overlapFirst . ' - ' . $overlapLast;

        stderr(
            $tracker_lang['error'],
            'Указанный диапазон пересекается с блокировкой №' . (int)$overlap['id'] . ': ' . bans_h($overlapText) . '.'
        );
    }

    sql_query("
        INSERT INTO `bans`
            (`added`, `addedby`, `first`, `last`, `comment`)
        VALUES
            (
                " . sqlesc(get_date_time()) . ",
                " . (int)$CURUSER['id'] . ",
                " . sqlesc($firstLong) . ",
                " . sqlesc($lastLong) . ",
                " . sqlesc($comment) . "
            )
    ") or sqlerr(__FILE__, __LINE__);

    $rangeText = $firstIp === $lastIp ? $firstIp : $firstIp . ' - ' . $lastIp;
    write_log(
        'IP-адрес ' . $rangeText . ' заблокирован пользователем ' . $CURUSER['username']
        . '. Причина: ' . $comment
    );

    bans_redirect();
}

$res = sql_query("
    SELECT
        b.`id`,
        b.`added`,
        b.`addedby`,
        b.`first`,
        b.`last`,
        b.`comment`,
        u.`username`
    FROM `bans` AS b
    LEFT JOIN `users` AS u ON u.`id` = b.`addedby`
    ORDER BY b.`first` ASC, b.`last` ASC, b.`id` DESC
") or sqlerr(__FILE__, __LINE__);

$bans = array();
while ($row = mysqli_fetch_assoc($res)) {
    $bans[] = $row;
}

$pageTitle = $tracker_lang['bans'] ?? 'Блокировка IP-адресов';
stdhead($pageTitle);
?>

<?php if ($isAdministrator) { ?>
    <div class="mn_wrap">
        <div class="tp1_title"><b>Добавить IP-блокировку</b></div>
        <div class="tp1_body">
            <form method="post" action="/bans.php">
                <input type="hidden" name="action" value="add">
                <table class="tables1 w100p">
                    <tr>
                        <td class="rowhead w150">
                            <label for="ban-first">Начальный IP</label>
                        </td>
                        <td>
                            <input
                                type="text"
                                id="ban-first"
                                name="first"
                                class="w300"
                                maxlength="15"
                                placeholder="192.168.1.10"
                                required
                            >
                        </td>
                    </tr>
                    <tr>
                        <td class="rowhead w150">
                            <label for="ban-last">Конечный IP</label>
                        </td>
                        <td>
                            <input
                                type="text"
                                id="ban-last"
                                name="last"
                                class="w300"
                                maxlength="15"
                                placeholder="Оставьте пустым для одного адреса"
                            >
                        </td>
                    </tr>
                    <tr>
                        <td class="rowhead w150">
                            <label for="ban-comment">Причина</label>
                        </td>
                        <td>
                            <input
                                type="text"
                                id="ban-comment"
                                name="comment"
                                class="w300"
                                maxlength="255"
                                required
                            >
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td class="small">
                            Блокировка действует на вход и просмотр сайта. Пересекающиеся диапазоны не добавляются.
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="right">
                            <input type="submit" class="buttonS" value=" Заблокировать IP ">
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
<?php } ?>

<div class="mn_wrap">
    <div class="tp1_title">
        <b>Заблокированные IP-адреса</b>
        <span class="floatright">Всего: <?=count($bans);?></span>
    </div>
    <div class="tp1_body">
        <table class="brd w100p">
            <tr>
                <th class="center">Диапазон</th>
                <th class="center w150">Добавлен</th>
                <th class="center w150">Кем добавлен</th>
                <th>Причина</th>
                <?php if ($isAdministrator) { ?>
                    <th class="center">Действие</th>
                <?php } ?>
            </tr>

            <?php if (!$bans) { ?>
                <tr>
                    <td colspan="<?=$isAdministrator ? 5 : 4;?>" class="center">
                        Активных IP-блокировок нет.
                    </td>
                </tr>
            <?php } else { ?>
                <?php foreach ($bans as $ban) {
                    $banId = (int)$ban['id'];
                    $firstIp = bans_db_to_ip($ban['first']);
                    $lastIp = bans_db_to_ip($ban['last']);
                    $rangeText = $firstIp === $lastIp ? $firstIp : $firstIp . ' - ' . $lastIp;
                ?>
                    <tr>
                        <td class="center nw"><b><?=bans_h($rangeText);?></b></td>
                        <td class="center nw"><?=bans_h($ban['added']);?></td>
                        <td class="center nw"><?=bans_user_link($ban['addedby'], $ban['username']);?></td>
                        <td><?=bans_h($ban['comment']);?></td>
                        <?php if ($isAdministrator) { ?>
                            <td class="center nw">
                                <form method="post" action="/bans.php" onsubmit="return confirm('Снять эту IP-блокировку?');">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="ban_id" value="<?=$banId;?>">
                                    <input type="submit" class="buttonS" value=" Снять ">
                                </form>
                            </td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            <?php } ?>
        </table>
    </div>
</div>

<?php
stdfoot();
?>
