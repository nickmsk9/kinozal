<?

require_once("include/bittorrent.php");
require_once("include/kz_messages.php");

dbconn(false);
loggedinorreturn();
parked();

$ids = isset($_POST['cbox']) && is_array($_POST['cbox']) ? array_map('intval', $_POST['cbox']) : array();
$ids = array_values(array_unique(array_filter($ids)));
$type = (string)($_POST['type'] ?? 'in');
$toarch = !empty($_POST['toarch']);

if (!$ids) {
	header('Location: /inbox.php' . ($type === 'out' ? '?out=1' : ($type === 'arch' ? '?arch=1' : '')));
	exit;
}

$uid = (int)$CURUSER['id'];
foreach ($ids as $id) {
	$res = sql_query("SELECT * FROM messages WHERE id = $id AND (receiver = $uid OR sender = $uid) LIMIT 1") or sqlerr(__FILE__, __LINE__);
	$msg = mysqli_fetch_assoc($res);
	if (!$msg) {
		continue;
	}

	if ($toarch) {
		if ((int)$msg['receiver'] === $uid || (int)$msg['sender'] === $uid) {
			sql_query("UPDATE messages SET location = " . KZ_PM_ARCHIVE . ", saved = 'yes', unread = 'no' WHERE id = $id") or sqlerr(__FILE__, __LINE__);
		}
		continue;
	}

	if ((int)$msg['receiver'] === $uid && (int)$msg['sender'] === $uid) {
		sql_query("DELETE FROM messages WHERE id = $id") or sqlerr(__FILE__, __LINE__);
	} elseif ((int)$msg['receiver'] === $uid && $msg['saved'] === 'no') {
		sql_query("DELETE FROM messages WHERE id = $id") or sqlerr(__FILE__, __LINE__);
	} elseif ((int)$msg['sender'] === $uid && (int)$msg['receiver'] !== $uid) {
		sql_query("UPDATE messages SET saved = 'no' WHERE id = $id") or sqlerr(__FILE__, __LINE__);
	} else {
		sql_query("UPDATE messages SET location = " . KZ_PM_ARCHIVE . ", unread = 'no' WHERE id = $id") or sqlerr(__FILE__, __LINE__);
	}
}

header('Location: /inbox.php' . ($type === 'out' ? '?out=1' : ($type === 'arch' ? '?arch=1' : '')));
exit;

?>
