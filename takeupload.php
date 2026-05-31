<?

require_once("include/BDecode.php");
require_once("include/BEncode.php");
require_once("include/bittorrent.php");
require_once("include/kz_upload.php");
require_once("include/kz_test_torrents.php");
require_once("include/kz_multitracker.php");

ini_set("upload_max_filesize", $max_torrent_size);

function bark($msg) {
	global $tracker_lang;
	genbark($msg, $tracker_lang['error']);
}

function kz_takeupload_file()
{
	if (isset($_FILES['file'])) {
		return $_FILES['file'];
	}

	if (isset($_FILES['tfile'])) {
		return $_FILES['tfile'];
	}

	return null;
}

function kz_takeupload_parse_torrent($file)
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
		bark("Ошибка загрузки торрент-файла.");
	}
	if (!filesize($tmpname)) {
		bark("Пустой файл!");
	}

	$dict = bdecode(file_get_contents($tmpname));
	if (!isset($dict) || !isset($dict['info'])) {
		bark("Загруженный файл не похож на корректный torrent.");
	}

	$info = $dict['info'];
	$dname = $info['name'] ?? $matches[1];
	$pieces = $info['pieces'] ?? '';
	if (strlen($pieces) % 20 != 0) {
		bark("Некорректный torrent: invalid pieces.");
	}

	$filelist = array();
	if (isset($info['length'])) {
		$totallen = (int)$info['length'];
		$filelist[] = array($dname, $totallen);
		$type = "single";
	} else {
		if (empty($info['files']) || !is_array($info['files'])) {
			bark("Некорректный torrent: missing both length and files.");
		}

		$totallen = 0;
		foreach ($info['files'] as $fn) {
			$length = (int)($fn['length'] ?? 0);
			$path = $fn['path'] ?? array();
			if (!is_array($path) || !count($path)) {
				bark("Некорректный torrent: filename error.");
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
		'shortname' => $matches[1],
		'save_as' => $dname,
		'info_hash' => sha1(BEncode($dict['info'])),
		'size' => $totallen,
		'numfiles' => count($filelist),
		'type' => $type,
		'filelist' => $filelist,
		'dict' => $dict,
		'announces' => $announce_list,
	);
}

dbconn();

loggedinorreturn();
parked();

kz_upload_ensure_schema();
kz_test_torrents_ensure_schema();
kz_mt_ensure_schema();

$is_test_upload = get_user_class() < UC_VIP || !empty($_GET['test']) || !empty($_POST['test']);

$is_preview = isset($_GET['preview']) || isset($_POST['preview']);

if (!isset($_POST['name'], $_POST['type'])) {
	bark("missing form data");
}

$name = trim(unesc((string)$_POST['name']));
if ($name === '') {
	bark("Вы должны указать название раздачи.");
}

$kind = kz_upload_normalize_kind($_POST['kind'] ?? 'video');
$catid = (int)$_POST['type'];
if (!$is_preview && !kz_upload_is_valid_category($kind, $catid)) {
	bark("Вы должны выбрать раздел, в который поместить торрент.");
}

[$kind, $details_data] = kz_upload_collect_post();
$poster_url = trim((string)($_POST['imgl'] ?? ''));
if ($poster_url !== '' && !preg_match('#^(https?:)?//#i', $poster_url)) {
	bark("Ссылка на постер должна начинаться с http://, https:// или //.");
}

$rgroup = (int)($_POST['rgroup'] ?? 0);
$rgroup_button = trim((string)($_POST['rbut'] ?? ''));

if ($is_preview) {
	$descr = kz_upload_build_description($details_data, $kind, $name, 0);
	if ($descr === '') {
		bark("Нечего показывать: заполните описание.");
	}

	$hide_right_blocks = true;
	stdhead("Предварительный просмотр");
	print("<div class=\"bx1\"><ul class=\"men\"><li class=\"tp2 b\">Предварительный просмотр</li><li class=\"pad5x5\">" . format_comment($descr) . "</li></ul></div>");
	print("<div class=\"bx1 center\"><input type=\"button\" class=\"buttonS\" value=\"Вернуться\" onclick=\"history.back();\"></div>");
	stdfoot();
	exit;
}

$file = kz_takeupload_file();
if (!$file) {
	bark("missing form data");
}

$torrent_data = kz_takeupload_parse_torrent($file);
kz_upload_apply_torrent_size($details_data, $kind, $torrent_data['size']);

$descr = kz_upload_build_description($details_data, $kind, $name, $torrent_data['size']);
if ($descr === '') {
	bark("Вы должны ввести описание!");
}

$torrent = htmlspecialchars_uni(str_replace("_", " ", $name));
$keywords = kz_upload_keywords($details_data, $kind, $torrent);
$meta_description = kz_upload_meta_description($descr);

$free = 'no';
$not_sticky = 'yes';

if (get_user_class() >= UC_ADMINISTRATOR && isset($_POST['free']) && in_array($_POST['free'], array('yes', 'silver', 'no'), true)) {
	$free = $_POST['free'];
}
if (get_user_class() >= UC_ADMINISTRATOR && isset($_POST['not_sticky']) && $_POST['not_sticky'] == 'no') {
	$not_sticky = 'no';
}

global $link, $torrent_dir;

$ret = sql_query("INSERT INTO torrents (filename, owner, visible, not_sticky, info_hash, name, keywords, description, size, numfiles, type, descr, ori_descr, free, image1, image2, image3, image4, image5, category, save_as, added, last_action, multitracker, is_test) VALUES (" . implode(",", array_map("sqlesc", array(
	$torrent_data['fname'],
	$CURUSER["id"],
	"yes",
	$not_sticky,
	$torrent_data['info_hash'],
	$torrent,
	$keywords,
	$meta_description,
	$torrent_data['size'],
	$torrent_data['numfiles'],
	$torrent_data['type'],
	$descr,
	$descr,
	$free,
	'',
	'',
	'',
	'',
	'',
	$catid,
	$torrent_data['save_as'],
))) . ", '" . get_date_time() . "', '" . get_date_time() . "', " . sqlesc(count($torrent_data['announces']) > 1 ? 'yes' : 'no') . ", " . sqlesc($is_test_upload ? 'yes' : 'no') . ")");

if (!$ret) {
	if (mysqli_errno($link) == 1062) {
		bark("torrent already uploaded!");
	}
	bark("mysql puked: " . mysqli_error($link));
}

$id = mysqli_insert_id($link);
kz_mt_save_trackers($id, $torrent_data['announces']);

kz_upload_save_details($id, $kind, $poster_url, $rgroup, $rgroup_button, $details_data);

sql_query('INSERT INTO torrents_descr (tid, descr_hash, descr_parsed) VALUES (' . implode(', ', array_map('sqlesc', array($id, md5($descr), format_comment($descr)))) . ')') or sqlerr(__FILE__, __LINE__);
sql_query("INSERT INTO checkcomm (checkid, userid, torrent) VALUES ($id, " . (int)$CURUSER['id'] . ", 1)") or sqlerr(__FILE__, __LINE__);

sql_query("DELETE FROM files WHERE torrent = $id");
foreach ($torrent_data['filelist'] as $file_row) {
	sql_query("INSERT INTO files (torrent, filename, size) VALUES ($id, " . sqlesc($file_row[0]) . ", " . (int)$file_row[1] . ")");
}

if (!is_dir($torrent_dir) && !@mkdir($torrent_dir, 0777, true)) {
	bark("Не удалось создать каталог для torrent-файлов.");
}

$encoded = BEncode($torrent_data['dict']);
if (file_put_contents("$torrent_dir/$id.torrent", $encoded) === false) {
	bark("Не удалось сохранить torrent-файл.");
}

write_log("Торрент номер $id ($torrent) был залит пользователем " . $CURUSER["username"] . ($is_test_upload ? " как тестовая раздача" : ""), "5DDB6E", "torrent");

header("Location: $DEFAULTBASEURL/details.php?id=$id");

?>
