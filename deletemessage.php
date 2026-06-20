<?

require_once("include/bittorrent.php");
require_once("include/messages.php");

dbconn(false);
loggedinorreturn();
parked();
tracker_require_form_token('POST');

$ids = msg_selected_ids($_POST['cbox'] ?? array());
$type = (string)($_POST['type'] ?? 'in');
$toarch = !empty($_POST['toarch']);
$redirect = msg_box_url($type);

if (!$ids) {
	header('Location: ' . $redirect);
	exit;
}

msg_apply_bulk_action($ids, $toarch, (int)$CURUSER['id']);

header('Location: ' . $redirect);
exit;

?>
