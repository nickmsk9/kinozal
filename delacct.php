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
require_once __DIR__ . '/include/account_delete.php';

dbconn();
loggedinorreturn();

function delacct_h($value): string
{
    return htmlspecialchars_uni((string)$value);
}

function delacct_query($sql): void
{
    sql_query($sql) or sqlerr(__FILE__, __LINE__);
}

function delacct_collect_ids($sql, $field): array
{
    $res = sql_query($sql) or sqlerr(__FILE__, __LINE__);
    $ids = array();

    while ($row = mysqli_fetch_assoc($res)) {
        $id = (int)($row[$field] ?? 0);
        if ($id > 0) {
            $ids[$id] = $id;
        }
    }

    return array_values($ids);
}

function delacct_reassign_groups($userid): array
{
    $userid = (int)$userid;
    $groupIds = delacct_collect_ids(
        "SELECT group_id FROM groupex_members WHERE userid = $userid
         UNION
         SELECT id AS group_id FROM groupex_groups WHERE owner_id = $userid",
        'group_id'
    );

    $ownedRes = sql_query("
        SELECT id
        FROM groupex_groups
        WHERE owner_id = $userid
    ") or sqlerr(__FILE__, __LINE__);

    while ($group = mysqli_fetch_assoc($ownedRes)) {
        $groupId = (int)$group['id'];
        $replacementRes = sql_query("
            SELECT userid
            FROM groupex_members
            WHERE group_id = $groupId
              AND userid <> $userid
              AND status = 'member'
            ORDER BY FIELD(role, 'moderator', 'member', 'owner'), id ASC
            LIMIT 1
        ") or sqlerr(__FILE__, __LINE__);
        $replacement = mysqli_fetch_assoc($replacementRes);
        $newOwnerId = (int)($replacement['userid'] ?? 0);

        delacct_query("UPDATE groupex_groups SET owner_id = $newOwnerId WHERE id = $groupId");

        if ($newOwnerId > 0) {
            delacct_query("
                UPDATE groupex_members
                SET role = 'owner', status = 'member', updated_at = NOW()
                WHERE group_id = $groupId AND userid = $newOwnerId
            ");
        }
    }

    delacct_query("DELETE FROM groupex_members WHERE userid = $userid");

    foreach ($groupIds as $groupId) {
        delacct_query("
            UPDATE groupex_groups AS g
            SET g.members_count = (
                SELECT COUNT(*)
                FROM groupex_members AS gm
                WHERE gm.group_id = g.id AND gm.status = 'member'
            )
            WHERE g.id = " . (int)$groupId
        );
    }

    return $groupIds;
}

function delacct_recalculate_ratings(array $torrentIds): void
{
    foreach ($torrentIds as $torrentId) {
        $torrentId = (int)$torrentId;
        delacct_query("
            UPDATE torrents AS t
            SET
                t.numratings = (
                    SELECT COUNT(*) FROM ratings AS r WHERE r.torrent = t.id
                ),
                t.ratingsum = (
                    SELECT COALESCE(SUM(r.rating), 0) FROM ratings AS r WHERE r.torrent = t.id
                )
            WHERE t.id = $torrentId
        ");
    }
}

function delacct_recalculate_reputation(array $userIds): void
{
    foreach ($userIds as $userId) {
        $userId = (int)$userId;
        delacct_query("
            UPDATE users AS u
            SET u.simpaty = GREATEST(
                0,
                1 + (
                    SELECT COALESCE(SUM(s.good) - SUM(s.bad), 0)
                    FROM simpaty AS s
                    WHERE s.touserid = u.id
                )
            )
            WHERE u.id = $userId
        ");
    }
}

function delacct_delete_user($userid): void
{
    global $link;

    $userid = (int)$userid;
    $ratedTorrentIds = delacct_collect_ids(
        "SELECT DISTINCT torrent FROM ratings WHERE user = $userid",
        'torrent'
    );
    $reputationUserIds = delacct_collect_ids(
        "SELECT DISTINCT touserid FROM simpaty WHERE fromuserid = $userid AND touserid <> $userid",
        'touserid'
    );

    mysqli_begin_transaction($link);

    try {
        delacct_reassign_groups($userid);

        // Личные сообщения, связи, подписки и история активности.
        delacct_query("DELETE FROM messages WHERE sender = $userid OR receiver = $userid OR poster = $userid");
        delacct_query("DELETE FROM friends WHERE userid = $userid OR friendid = $userid");
        delacct_query("DELETE FROM blocks WHERE userid = $userid OR blockid = $userid");
        delacct_query("DELETE FROM bookmarks WHERE userid = $userid");
        delacct_query("DELETE FROM person_bookmarks WHERE userid = $userid");
        delacct_query("DELETE FROM groupex_bookmarks WHERE userid = $userid");
        delacct_query("DELETE FROM user_bookmarks WHERE userid = $userid OR target_userid = $userid");
        delacct_query("DELETE FROM readtorrents WHERE userid = $userid");
        delacct_query("DELETE FROM checkcomm WHERE userid = $userid");
        delacct_query("DELETE FROM peers WHERE userid = $userid");
        delacct_query("DELETE FROM snatched WHERE userid = $userid");
        delacct_query("DELETE FROM user_torrent_downloads WHERE userid = $userid");
        delacct_query("DELETE FROM sessions WHERE uid = $userid");
        delacct_query("DELETE FROM users_ban WHERE userid = $userid");
        delacct_query("DELETE FROM user_cups WHERE userid = $userid");
        delacct_query("UPDATE user_cups SET assigned_by = 0 WHERE assigned_by = $userid");
        delacct_query("DELETE FROM user_status_assignments WHERE userid = $userid");
        delacct_query("UPDATE user_status_assignments SET assigned_by = 0 WHERE assigned_by = $userid");

        // Голоса и благодарности удаляются, агрегаты рейтинга пересчитываются.
        delacct_query("DELETE FROM ratings WHERE user = $userid");
        delacct_query("DELETE FROM thanks WHERE userid = $userid");
        delacct_recalculate_ratings($ratedTorrentIds);

        // Репутация удаляется с обеих сторон и пересчитывается у получателей.
        delacct_query("DELETE FROM simpaty WHERE fromuserid = $userid OR touserid = $userid");
        delacct_recalculate_reputation($reputationUserIds);

        // Публичный контент сохраняется, но больше не содержит персональных данных.
        delacct_query("UPDATE torrents SET owner = 0 WHERE owner = $userid");
        delacct_query("UPDATE torrents SET moderatedby = 0 WHERE moderatedby = $userid");
        delacct_query("
            UPDATE torrents
            SET test_helper_user_id = 0, test_helper_until = NULL
            WHERE test_helper_user_id = $userid
        ");
        delacct_query("UPDATE torrents SET test_approved_by = 0 WHERE test_approved_by = $userid");
        delacct_query("UPDATE comments SET user = 0, ip = '' WHERE user = $userid");
        delacct_query("UPDATE comments SET editedby = 0 WHERE editedby = $userid");
        delacct_query("UPDATE news SET userid = 0 WHERE userid = $userid");
        delacct_query("UPDATE persons SET created_by = 0 WHERE created_by = $userid");
        delacct_query("UPDATE persons SET updated_by = 0 WHERE updated_by = $userid");
        delacct_query("UPDATE bans SET addedby = 0 WHERE addedby = $userid");
        delacct_query("UPDATE groupex_log SET userid = 0 WHERE userid = $userid");
        delacct_query("UPDATE groupex_zabor SET userid = 0 WHERE userid = $userid");
        delacct_query("
            UPDATE radio_chat
            SET userid = 0, username = 'Удалённый пользователь', userclass = 0, ip = ''
            WHERE userid = $userid
        ");
        delacct_query("
            UPDATE pay_chat
            SET userid = 0, username = 'Удалённый пользователь', userclass = 0, ip = ''
            WHERE userid = $userid
        ");
        delacct_query("
            UPDATE pay_wishes
            SET userid = 0, username = 'Удалённый пользователь', userclass = 0
            WHERE userid = $userid
        ");
        delacct_query("
            UPDATE pay_transactions
            SET userid = 0, username = 'Удалённый пользователь', ip = ''
            WHERE userid = $userid
        ");
        delacct_query("
            UPDATE uarch_smiles
            SET userid = 0, username = 'Удалённый пользователь', userclass = 0, ip = ''
            WHERE userid = $userid
        ");

        // Технические записи, не имеющие смысла без аккаунта.
        delacct_query("DELETE FROM notconnectablepmlog WHERE user = $userid");
        delacct_query("DELETE FROM users WHERE id = $userid LIMIT 1");

        if (mysqli_affected_rows($link) !== 1) {
            throw new RuntimeException('Пользователь не был удалён.');
        }

        mysqli_commit($link);
    } catch (Throwable $e) {
        mysqli_rollback($link);
        throw $e;
    }
}

$username = !empty($CURUSER['username']) ? (string)$CURUSER['username'] : '';
$deleted = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    tracker_require_form_token('POST');

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirmed = (string)($_POST['confirm_delete'] ?? '') === 'yes';

    if ($username === '' || $password === '') {
        stderr($tracker_lang['error'], 'Введите имя пользователя и пароль.');
    }

    if (!$confirmed) {
        stderr($tracker_lang['error'], 'Подтвердите безвозвратное удаление аккаунта.');
    }

    if (strcasecmp($username, (string)$CURUSER['username']) !== 0) {
        stderr($tracker_lang['error'], 'Можно удалить только текущий аккаунт.');
    }

    $res = sql_query("
        SELECT id, username, class, secret, passhash
        FROM users
        WHERE id = " . (int)$CURUSER['id'] . "
        LIMIT 1
    ") or sqlerr(__FILE__, __LINE__);
    $user = mysqli_fetch_assoc($res);

    if (!$user || !tracker_password_verify($password, $user['secret'], $user['passhash'])) {
        stderr($tracker_lang['error'], 'Неверное имя пользователя или пароль.');
    }

    $userid = (int)$user['id'];

    if ((int)$user['class'] >= UC_SYSOP) {
        $sysopRes = sql_query("
            SELECT COUNT(*) AS sysop_count
            FROM users
            WHERE class >= " . UC_SYSOP . " AND enabled = 'yes'
        ") or sqlerr(__FILE__, __LINE__);
        $sysopRow = mysqli_fetch_assoc($sysopRes);

        if ((int)($sysopRow['sysop_count'] ?? 0) <= 1) {
            stderr($tracker_lang['error'], 'Нельзя удалить последнего системного администратора.');
        }
    }

    try {
        account_delete_user($userid);
    } catch (Throwable $e) {
        stderr($tracker_lang['error'], 'Не удалось полностью удалить аккаунт. Изменения отменены.');
    }

    write_log('Пользователь ' . $user['username'] . ' самостоятельно удалил свой аккаунт.');

    if (!empty($CURUSER) && (int)$CURUSER['id'] === $userid) {
        logoutcookie();
    }

    $deleted = true;
}

$hide_right_blocks = true;
stdhead('Удалить аккаунт');
?>

<div style="width: 100%; text-align: center;">
    <div style="width: 100%; max-width: 620px; display: inline-block; text-align: left;">
        <?php if ($deleted) { ?>
            <div class="bx1_0">
                <div class="pad10x10 center">
                    <p class="green"><b>Аккаунт полностью удалён.</b></p>
                    <p><a href="/" class="sbab">Перейти на главную страницу</a></p>
                </div>
            </div>
        <?php } else { ?>
            <div class="mn_wrap">
                <div class="tp1_title"><b>Удалить аккаунт</b></div>
                <div class="tp1_body">
                    <div class="bx1_0 red">
                        <div class="pad10x10">
                            Аккаунт и личные данные будут удалены без возможности восстановления.
                            Публичные раздачи и комментарии останутся без привязки к профилю.
                        </div>
                    </div>

                    <form method="post" action="/delacct.php" autocomplete="off">
                        <input type="hidden" name="hash4u" value="<?=delacct_h($CURUSER['hash4u'] ?? tracker_user_form_token());?>">
                        <table class="tables1 w100p">
                            <tr>
                                <td class="rowhead w150"><label for="delacct-username">Пользователь</label></td>
                                <td>
                                    <input
                                        type="text"
                                        id="delacct-username"
                                        name="username"
                                        class="w300"
                                        value="<?=delacct_h($username);?>"
                                        required
                                        autofocus
                                    >
                                </td>
                            </tr>
                            <tr>
                                <td class="rowhead"><label for="delacct-password">Пароль</label></td>
                                <td>
                                    <input
                                        type="password"
                                        id="delacct-password"
                                        name="password"
                                        class="w300"
                                        required
                                    >
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>
                                    <label class="red">
                                        <input type="checkbox" name="confirm_delete" value="yes" required>
                                        Я понимаю, что удаление необратимо
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" class="right">
                                    <input
                                        type="submit"
                                        class="buttonS"
                                        value=" Удалить аккаунт "
                                        onclick="return confirm('Безвозвратно удалить аккаунт?');"
                                    >
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<?php
stdfoot();
?>
