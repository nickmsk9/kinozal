<?

require_once("include/bittorrent.php");
require_once("include/kz_upload.php");
require_once("include/kz_test_torrents.php");

dbconn(false);

loggedinorreturn();
parked();

if (strlen($CURUSER['passkey']) != 32) {
	$CURUSER['passkey'] = md5($CURUSER['username'] . get_date_time() . $CURUSER['passhash']);
	sql_query("UPDATE users SET passkey = " . sqlesc($CURUSER['passkey']) . " WHERE id = " . (int)$CURUSER['id']);
}

kz_upload_ensure_schema();
kz_test_torrents_ensure_schema();

$is_test_upload = get_user_class() < UC_VIP || !empty($_GET['test']);

$hide_right_blocks = true;
stdhead($is_test_upload ? 'Залить тестовую раздачу' : $tracker_lang['upload_torrent']);

$state = array(
	'id' => 0,
	'name' => '',
	'kind' => 'video',
	'category' => 0,
	'allow_file' => true,
	'details' => array(
		'poster_url' => '',
		'rgroup' => 0,
		'rgroup_button' => '',
		'data' => kz_upload_default_data(),
	),
);

?>
<div class="bx2">
	<? kz_upload_render_info_sidebar(); ?>
	<div class="mn3_content">
		<? kz_upload_render_form('/takeupload.php' . ($is_test_upload ? '?test=1' : ''), $is_test_upload ? 'Залить тестовую раздачу' : 'Залить раздачу', $state, false); ?>
	</div>
</div>
<? kz_upload_render_online_block(); ?>
<?

stdfoot();

?>
