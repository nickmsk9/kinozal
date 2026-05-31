<?

require_once("include/bittorrent.php");
require_once("include/kz_messages.php");

dbconn(false);
loggedinorreturn();
parked();

$receiver_id = (int)($_POST['receiver'] ?? 0);
if (!is_valid_id($receiver_id)) {
	stderr($tracker_lang['error'], 'Неверный ID получателя.');
}

if (isset($_POST['hash4u']) && $_POST['hash4u'] !== '' && isset($CURUSER['hash4u']) && $_POST['hash4u'] !== $CURUSER['hash4u']) {
	stderr($tracker_lang['error'], 'Неверный ключ формы.');
}

$subject = trim((string)($_POST['subject'] ?? ''));
$msg = trim((string)($_POST['msg'] ?? ''));
if ($subject === '') {
	$subject = 'Без темы';
}
if ($msg === '') {
	stderr($tracker_lang['error'], 'Пожалуйста, введите сообщение.');
}

$res = sql_query("SELECT * FROM users WHERE id = $receiver_id LIMIT 1") or sqlerr(__FILE__, __LINE__);
$receiver = mysqli_fetch_assoc($res);
if (!$receiver) {
	stderr($tracker_lang['error'], 'Пользователь не найден.');
}

$deny = kz_msg_can_send_to($receiver);
if ($deny !== '') {
	stderr('Отклонено', $deny);
}

$save = (isset($_POST['save']) && $_POST['save'] === 'yes') ? 'yes' : 'no';
sql_query("
	INSERT INTO messages (poster, sender, receiver, added, msg, subject, saved, location)
	VALUES (" . (int)$CURUSER['id'] . ", " . (int)$CURUSER['id'] . ", $receiver_id, " . sqlesc(get_date_time()) . ", " . sqlesc($msg) . ", " . sqlesc($subject) . ", " . sqlesc($save) . ", " . KZ_PM_INBOX . ")
") or sqlerr(__FILE__, __LINE__);

$returnto = trim((string)($_POST['returnto'] ?? ''));
if ($returnto === '' || !preg_match('#^https?://|^/#i', $returnto)) {
	$returnto = '/inbox.php?out=1';
}
header('Location: ' . $returnto);
exit;

?>
