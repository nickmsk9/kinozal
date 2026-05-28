<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/groupex.php';

dbconn(false);
loggedinorreturn();
kz_groups_ensure_schema();

$id = (int)($_GET['id'] ?? 0);
$group = kz_groups_fetch($id);
if (!$group) {
	stderr('Группа', 'Группа не найдена.');
}

$action = (string)($_GET['action'] ?? '');
$userid = (int)$CURUSER['id'];
$targetid = (int)($_GET['userid'] ?? 0);
$redirect = '/groupex.php?id=' . $id;

if ($action === 'join') {
	$member = kz_groups_member($id, $userid);
	if ($member && $member['status'] === 'member') {
		header('Location: ' . $redirect);
		exit;
	}

	$status = $group['private'] === 'yes' ? 'pending' : 'member';
	if ($member) {
		sql_query("
			UPDATE groupex_members
			SET status = " . sqlesc($status) . ", role = 'member', updated_at = NOW()
			WHERE group_id = $id AND userid = $userid
		") or sqlerr(__FILE__, __LINE__);
	} else {
		sql_query("
			INSERT INTO groupex_members (group_id, userid, role, status, added_at, updated_at)
			VALUES ($id, $userid, 'member', " . sqlesc($status) . ", NOW(), NOW())
		") or sqlerr(__FILE__, __LINE__);
	}
	kz_groups_log($id, $userid, $status === 'member' ? 'join' : 'request', $status === 'member' ? 'Пользователь вступил в группу' : 'Пользователь подал заявку');
	kz_groups_refresh_counts($id);
	header('Location: ' . $redirect);
	exit;
}

if ($action === 'leavegroup') {
	$member = kz_groups_member($id, $userid);
	if (!$member) {
		header('Location: ' . $redirect);
		exit;
	}
	if ($member['role'] === 'owner' && (int)$group['owner_id'] === $userid) {
		stderr('Группа', 'Руководитель не может покинуть собственную группу. Передайте руководство другому участнику или обратитесь к администрации.');
	}
	sql_query("DELETE FROM groupex_members WHERE group_id = $id AND userid = $userid") or sqlerr(__FILE__, __LINE__);
	kz_groups_log($id, $userid, 'leave', 'Пользователь покинул группу');
	kz_groups_refresh_counts($id);
	header('Location: /mygroups.php');
	exit;
}

if (in_array($action, array('approve', 'decline', 'kick', 'make_moderator', 'make_member'), true)) {
	if (!kz_groups_can_manage($group)) {
		stderr('Группа', 'У Вас нет прав для управления участниками этой группы.');
	}
	if ($targetid <= 0) {
		stderr('Группа', 'Не указан пользователь.');
	}
	if ($targetid === (int)$group['owner_id'] && $action !== 'approve') {
		stderr('Группа', 'Нельзя изменить статус владельца группы этим действием.');
	}

	$redirect = '/groupexmembers.php?id=' . $id;
	if ($action === 'approve') {
		sql_query("
			UPDATE groupex_members
			SET status = 'member', updated_at = NOW()
			WHERE group_id = $id AND userid = $targetid
		") or sqlerr(__FILE__, __LINE__);
		kz_groups_log($id, $userid, 'approve', 'Одобрена заявка пользователя #' . $targetid);
	} elseif ($action === 'decline' || $action === 'kick') {
		sql_query("DELETE FROM groupex_members WHERE group_id = $id AND userid = $targetid") or sqlerr(__FILE__, __LINE__);
		kz_groups_log($id, $userid, $action, ($action === 'decline' ? 'Отклонена заявка пользователя #' : 'Исключен пользователь #') . $targetid);
	} elseif ($action === 'make_moderator') {
		sql_query("
			UPDATE groupex_members
			SET role = 'moderator', updated_at = NOW()
			WHERE group_id = $id AND userid = $targetid AND status = 'member'
		") or sqlerr(__FILE__, __LINE__);
		kz_groups_log($id, $userid, 'role', 'Пользователь #' . $targetid . ' назначен модератором');
	} elseif ($action === 'make_member') {
		sql_query("
			UPDATE groupex_members
			SET role = 'member', updated_at = NOW()
			WHERE group_id = $id AND userid = $targetid AND status = 'member'
		") or sqlerr(__FILE__, __LINE__);
		kz_groups_log($id, $userid, 'role', 'Пользователь #' . $targetid . ' переведен в участники');
	}
	kz_groups_refresh_counts($id);
	header('Location: ' . $redirect);
	exit;
}

stderr('Группа', 'Неизвестное действие.');

?>
