<?

require_once("include/bittorrent.php");
require_once("include/upload.php");
require_once("include/test_torrents.php");
require_once("include/multitracker.php");

dbconn(false);

loggedinorreturn();
parked();

upload_ensure_schema();
test_torrents_ensure_schema();
multitracker_ensure_schema();

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
		'data' => upload_default_data(),
	),
);

?>
<div class="bx2">
	<? upload_render_info_sidebar(); ?>
	<div class="mn3_content">
		<? upload_render_form('/takeupload.php' . ($is_test_upload ? '?test=1' : ''), $is_test_upload ? 'Залить тестовую раздачу' : 'Залить раздачу', $state, false); ?>
	</div>
</div>
<? upload_render_online_block(); ?>
<?

stdfoot();

?>
