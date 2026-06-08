<?php

if (!defined('IN_TRACKER')) {
    die('Прямой доступ запрещён.');
}

function account_delete_query($sql): void
{
    sql_query($sql) or sqlerr(__FILE__, __LINE__);
}

function account_delete_ids($sql, $field): array
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

function account_delete_user($userid): void
{
    global $link;

    $userid = (int)$userid;
    if (!is_valid_id($userid)) {
        throw new InvalidArgumentException('Некорректный пользователь.');
    }

    $ratedTorrentIds = account_delete_ids(
        "SELECT DISTINCT torrent FROM ratings WHERE user = $userid",
        'torrent'
    );
    $reputationUserIds = account_delete_ids(
        "SELECT DISTINCT touserid FROM simpaty WHERE fromuserid = $userid AND touserid <> $userid",
        'touserid'
    );
    $groupIds = account_delete_ids(
        "SELECT group_id FROM groupex_members WHERE userid = $userid
         UNION
         SELECT id AS group_id FROM groupex_groups WHERE owner_id = $userid",
        'group_id'
    );

    mysqli_begin_transaction($link);

    try {
        $ownedRes = sql_query("SELECT id FROM groupex_groups WHERE owner_id = $userid") or sqlerr(__FILE__, __LINE__);
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

            account_delete_query("UPDATE groupex_groups SET owner_id = $newOwnerId WHERE id = $groupId");
            if ($newOwnerId > 0) {
                account_delete_query("
                    UPDATE groupex_members
                    SET role = 'owner', status = 'member', updated_at = NOW()
                    WHERE group_id = $groupId AND userid = $newOwnerId
                ");
            }
        }

        account_delete_query("DELETE FROM groupex_members WHERE userid = $userid");
        foreach ($groupIds as $groupId) {
            account_delete_query("
                UPDATE groupex_groups AS g
                SET members_count = (
                    SELECT COUNT(*) FROM groupex_members AS gm
                    WHERE gm.group_id = g.id AND gm.status = 'member'
                )
                WHERE g.id = " . (int)$groupId
            );
        }

        $deleteQueries = array(
            "DELETE FROM messages WHERE sender = $userid OR receiver = $userid OR poster = $userid",
            "DELETE FROM friends WHERE userid = $userid OR friendid = $userid",
            "DELETE FROM blocks WHERE userid = $userid OR blockid = $userid",
            "DELETE FROM bookmarks WHERE userid = $userid",
            "DELETE FROM person_bookmarks WHERE userid = $userid",
            "DELETE FROM groupex_bookmarks WHERE userid = $userid",
            "DELETE FROM user_bookmarks WHERE userid = $userid OR target_userid = $userid",
            "DELETE FROM readtorrents WHERE userid = $userid",
            "DELETE FROM checkcomm WHERE userid = $userid",
            "DELETE FROM peers WHERE userid = $userid",
            "DELETE FROM snatched WHERE userid = $userid",
            "DELETE FROM user_torrent_downloads WHERE userid = $userid",
            "DELETE FROM sessions WHERE uid = $userid",
            "DELETE FROM users_ban WHERE userid = $userid",
            "DELETE FROM user_cups WHERE userid = $userid",
            "DELETE FROM user_status_assignments WHERE userid = $userid",
            "DELETE FROM ratings WHERE user = $userid",
            "DELETE FROM thanks WHERE userid = $userid",
            "DELETE FROM simpaty WHERE fromuserid = $userid OR touserid = $userid",
            "DELETE FROM notconnectablepmlog WHERE user = $userid",
        );
        foreach ($deleteQueries as $query) {
            account_delete_query($query);
        }

        account_delete_query("UPDATE user_cups SET assigned_by = 0 WHERE assigned_by = $userid");
        account_delete_query("UPDATE user_status_assignments SET assigned_by = 0 WHERE assigned_by = $userid");

        foreach ($ratedTorrentIds as $torrentId) {
            account_delete_query("
                UPDATE torrents AS t
                SET numratings = (SELECT COUNT(*) FROM ratings AS r WHERE r.torrent = t.id),
                    ratingsum = (SELECT COALESCE(SUM(r.rating), 0) FROM ratings AS r WHERE r.torrent = t.id)
                WHERE t.id = " . (int)$torrentId
            );
        }

        foreach ($reputationUserIds as $targetId) {
            account_delete_query("
                UPDATE users AS u
                SET simpaty = GREATEST(0, 1 + (
                    SELECT COALESCE(SUM(s.good) - SUM(s.bad), 0)
                    FROM simpaty AS s WHERE s.touserid = u.id
                ))
                WHERE u.id = " . (int)$targetId
            );
        }

        $updateQueries = array(
            "UPDATE torrents SET owner = 0 WHERE owner = $userid",
            "UPDATE torrents SET moderatedby = 0 WHERE moderatedby = $userid",
            "UPDATE torrents SET test_approved_by = 0 WHERE test_approved_by = $userid",
            "UPDATE torrents SET test_helper_user_id = 0, test_helper_until = NULL WHERE test_helper_user_id = $userid",
            "UPDATE comments SET user = 0, ip = '' WHERE user = $userid",
            "UPDATE comments SET editedby = 0 WHERE editedby = $userid",
            "UPDATE news SET userid = 0 WHERE userid = $userid",
            "UPDATE persons SET created_by = 0 WHERE created_by = $userid",
            "UPDATE persons SET updated_by = 0 WHERE updated_by = $userid",
            "UPDATE bans SET addedby = 0 WHERE addedby = $userid",
            "UPDATE groupex_log SET userid = 0 WHERE userid = $userid",
            "UPDATE groupex_zabor SET userid = 0 WHERE userid = $userid",
            "UPDATE radio_chat SET userid = 0, username = 'Удалённый пользователь', userclass = 0, ip = '' WHERE userid = $userid",
            "UPDATE pay_chat SET userid = 0, username = 'Удалённый пользователь', userclass = 0, ip = '' WHERE userid = $userid",
            "UPDATE pay_wishes SET userid = 0, username = 'Удалённый пользователь', userclass = 0 WHERE userid = $userid",
            "UPDATE pay_transactions SET userid = 0, username = 'Удалённый пользователь', ip = '' WHERE userid = $userid",
            "UPDATE uarch_smiles SET userid = 0, username = 'Удалённый пользователь', userclass = 0, ip = '' WHERE userid = $userid",
        );
        foreach ($updateQueries as $query) {
            account_delete_query($query);
        }

        account_delete_query("DELETE FROM users WHERE id = $userid LIMIT 1");
        if (mysqli_affected_rows($link) !== 1) {
            throw new RuntimeException('Пользователь не был удалён.');
        }

        mysqli_commit($link);
    } catch (Throwable $e) {
        mysqli_rollback($link);
        throw $e;
    }
}
