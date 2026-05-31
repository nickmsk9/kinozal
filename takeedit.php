<?

require_once("include/BDecode.php");
require_once("include/BEncode.php");
require_once("include/bittorrent.php");
require_once("include/kz_upload.php");
require_once("include/kz_multitracker.php");

function bark($msg) {
	stderr("Ошибка", $msg);
}

function kz_takeedit_file()
{
	if (isset($_FILES['file']) && !empty($_FILES['file']['name'])) {
		return $_FILES['file'];
	}
	if (isset($_FILES['tfile']) && !empty($_FILES['tfile']['name'])) {
		return $_FILES['tfile'];
	}
	return null;
}

function kz_takeedit_parse_torrent($file)
{
	global $announce_urls, $DEFAULTBASEURL, $SITENAME, $CURUSER;

	$fname = unesc($file["name"]);
	if (empty($fname)) {
		bark("Файл не загружен. Пустое имя файла!");
	}
	if (!validfilename($fname)) {
		bark("Неверное имя файла!");
	}
	if (!preg_match('/^(.+)\.torrent$/si', $fname, $matches)) {
		bark("Неверное имя файла (не .torrent).");
	}

	$tmpname = $file["tmp_name"];
	if (!is_uploaded_file($tmpname)) {
		bark("Ошибка загрузки torrent-файла.");
	}
	if (!filesize($tmpname)) {
		bark("Пустой файл!");
	}

	$dict = bdecode(file_get_contents($tmpname));
	if (!isset($dict) || !isset($dict['info'])) {
		bark("Загруженный файл не похож на корректный torrent.");
	}

	$external_info_hash = sha1(BEncode($dict['info']));
	$info = $dict['info'];
	$dname = $info['name'] ?? $matches[1];
	$pieces = $info['pieces'] ?? '';
	if (strlen($pieces) % 20 != 0) {
		bark("invalid pieces");
	}

	$filelist = array();
	if (isset($info['length'])) {
		$totallen = (int)$info['length'];
		$filelist[] = array($dname, $totallen);
		$type = "single";
	} else {
		if (empty($info['files']) || !is_array($info['files'])) {
			bark("missing both length and files");
		}
		$totallen = 0;
		foreach ($info['files'] as $fn) {
			$length = (int)($fn['length'] ?? 0);
			$path = $fn['path'] ?? array();
			if (!is_array($path) || !count($path)) {
				bark("filename error");
			}
			$filename = implode("/", $path);
			if ($filename === 'Thumbs.db') {
				bark("В торрентах запрещено держать файлы Thumbs.db!");
			}
			$totallen += $length;
			$filelist[] = array($filename, $length);
		}
		$type = "multi";
	}

	$announce_list = kz_mt_extract_announces($dict);
	$dict = kz_mt_apply_announces_to_dict($dict, $announce_list);
	$dict['info']['private'] = 1;
	$dict['info']['source'] = "[$DEFAULTBASEURL] $SITENAME";
	unset($dict['info']['crc32'], $dict['info']['ed2k'], $dict['info']['md5sum'], $dict['info']['sha1'], $dict['info']['tiger']);

	$dict = BDecode(BEncode($dict));
	$dict['comment'] = "Торрент создан для '$SITENAME'";
	$dict['created by'] = $CURUSER['username'];
	$dict['publisher'] = $CURUSER['username'];
	$dict['publisher.utf-8'] = $CURUSER['username'];
	$dict['publisher-url'] = "$DEFAULTBASEURL/userdetails.php?id=" . (int)$CURUSER['id'];
	$dict['publisher-url.utf-8'] = "$DEFAULTBASEURL/userdetails.php?id=" . (int)$CURUSER['id'];

	return array(
		'fname' => $fname,
		'save_as' => $dname,
		'info_hash' => sha1(BEncode($dict['info'])),
		'size' => $totallen,
		'numfiles' => count($filelist),
		'type' => $type,
		'filelist' => $filelist,
		'dict' => $dict,
		'announces' => $announce_list,
		'external_info_hash' => $external_info_hash,
	);
}

dbconn();
loggedinorreturn();

kz_upload_ensure_schema();
kz_mt_ensure_schema();

if (!isset($_POST['id'], $_POST['name'], $_POST['type'])) {
	bark("missing form data");
}

$id = (int)$_POST['id'];
if (!$id) {
	die();
}

$res = sql_query("SELECT owner, filename, save_as, size, multitracker FROM torrents WHERE id = $id");
$row = mysqli_fetch_array($res);
if (!$row) {
	die();
}

if ($CURUSER["id"] != $row["owner"] && get_user_class() < UC_MODERATOR) {
	bark("Вы не владелец этой раздачи.");
}

$name = trim(unesc((string)$_POST['name']));
if ($name === '') {
	bark("Вы должны указать название раздачи.");
}

$kind = kz_upload_normalize_kind($_POST['kind'] ?? 'video');
$catid = (int)$_POST['type'];
if (!kz_upload_is_valid_category($kind, $catid)) {
	bark("Вы должны выбрать раздел, в который поместить торрент.");
}

[$kind, $details_data] = kz_upload_collect_post((int)$row['size']);
$poster_url = trim((string)($_POST['imgl'] ?? ''));
if ($poster_url !== '' && !preg_match('#^(https?:)?//#i', $poster_url)) {
	bark("Ссылка на постер должна начинаться с http://, https:// или //.");
}

$rgroup = (int)($_POST['rgroup'] ?? 0);
$rgroup_button = trim((string)($_POST['rbut'] ?? ''));

$file = kz_takeedit_file();
$torrent_data = null;
if ($file) {
	if ($row['multitracker'] == 'yes') {
		bark("Для мультитрекерной раздачи torrent-файл не обновляется.");
	}
	$torrent_data = kz_takeedit_parse_torrent($file);
	kz_upload_apply_torrent_size($details_data, $kind, $torrent_data['size']);
} else {
	kz_upload_apply_torrent_size($details_data, $kind, (int)$row['size']);
}

$descr = kz_upload_build_description($details_data, $kind, $name, $torrent_data ? $torrent_data['size'] : (int)$row['size']);
if ($descr === '') {
	bark("Вы должны ввести описание!");
}

if (isset($_GET['preview']) || isset($_POST['preview'])) {
	$hide_right_blocks = true;
	stdhead("Предварительный просмотр");
	print("<div class=\"bx1\"><ul class=\"men\"><li class=\"tp2 b\">Предварительный просмотр</li><li class=\"pad5x5\">" . format_comment($descr) . "</li></ul></div>");
	print("<div class=\"bx1 center\"><input type=\"button\" class=\"buttonS\" value=\"Вернуться\" onclick=\"history.back();\"></div>");
	stdfoot();
	exit;
}

$updateset = array();
$safe_name = html_uni($name);
$keywords = kz_upload_keywords($details_data, $kind, $safe_name);
$meta_description = kz_upload_meta_description($descr);

$updateset[] = "name = " . sqlesc($safe_name);
$updateset[] = "keywords = " . sqlesc($keywords);
$updateset[] = "description = " . sqlesc($meta_description);
$updateset[] = "descr = " . sqlesc($descr);
$updateset[] = "ori_descr = " . sqlesc($descr);
$updateset[] = "category = " . $catid;

if ($torrent_data) {
	global $torrent_dir;
	$encoded = BEncode($torrent_data['dict']);
	if (file_put_contents("$torrent_dir/$id.torrent", $encoded) === false) {
		bark("Не удалось сохранить torrent-файл.");
	}

	$updateset[] = "info_hash = " . sqlesc($torrent_data['info_hash']);
	$updateset[] = "filename = " . sqlesc($torrent_data['fname']);
	$updateset[] = "save_as = " . sqlesc($torrent_data['save_as']);
	$updateset[] = "size = " . sqlesc($torrent_data['size']);
	$updateset[] = "type = " . sqlesc($torrent_data['type']);
	$updateset[] = "numfiles = " . (int)$torrent_data['numfiles'];

	sql_query("DELETE FROM files WHERE torrent = $id");
	foreach ($torrent_data['filelist'] as $file_row) {
		sql_query("INSERT INTO files (torrent, filename, size) VALUES ($id, " . sqlesc($file_row[0]) . ", " . (int)$file_row[1] . ")");
	}

	kz_mt_save_trackers($id, $torrent_data['announces'], $torrent_data['external_info_hash']);
}

if (get_user_class() >= UC_ADMINISTRATOR) {
	if (!empty($_POST["banned"])) {
		$updateset[] = "banned = 'yes'";
		$_POST["visible"] = 0;
	} else {
		$updateset[] = "banned = 'no'";
	}

	$updateset[] = "not_sticky = " . sqlesc((isset($_POST["not_sticky"]) && $_POST["not_sticky"] == "no") ? 'no' : 'yes');

	if (isset($_POST['free']) && in_array($_POST['free'], array('yes', 'silver', 'no'), true)) {
		$updateset[] = "free = " . sqlesc($_POST['free']);
	}
}

if (get_user_class() >= UC_MODERATOR && !$torrent_data && isset($_POST['external_trackers'])) {
	$posted_trackers = kz_mt_parse_posted_urls($_POST['external_trackers']);
	kz_mt_save_trackers($id, $posted_trackers, kz_mt_recover_external_info_hash($id, $row['info_hash'] ?? ''));
	kz_mt_rewrite_torrent_file_announces($id, $posted_trackers);
}

$updateset[] = "visible = " . sqlesc(!empty($_POST["visible"]) ? "yes" : "no");
$updateset[] = "moderated = 'yes'";
$updateset[] = "moderatedby = " . sqlesc($CURUSER["id"]);

sql_query("UPDATE torrents SET " . join(", ", $updateset) . " WHERE id = $id") or sqlerr(__FILE__, __LINE__);
sql_query('REPLACE INTO torrents_descr (tid, descr_hash, descr_parsed) VALUES (' . implode(', ', array_map('sqlesc', array($id, md5($descr), format_comment($descr)))) . ')') or sqlerr(__FILE__, __LINE__);

kz_upload_save_details($id, $kind, $poster_url, $rgroup, $rgroup_button, $details_data);

write_log("Торрент '$safe_name' был отредактирован пользователем {$CURUSER['username']}", "F25B61", "torrent");

$returl = "details.php?id=$id";
if (isset($_POST["returnto"]) && $_POST["returnto"] !== '') {
	$returl .= "&returnto=" . urlencode($_POST["returnto"]);
}

header("Refresh: 0; url=$returl");

?>
