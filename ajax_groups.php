<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/groupex.php';

dbconn(false);
kz_groups_ensure_schema();

header('Content-Type: application/json; charset=UTF-8');

$q = (string)($_POST['q'] ?? $_GET['q'] ?? '');
if ($q !== 'subcat') {
	echo json_encode(array(), JSON_UNESCAPED_UNICODE);
	exit;
}

$category_id = (int)($_POST['index'] ?? $_GET['index'] ?? 0);
$rows = array();
foreach (kz_groups_subcategories_for($category_id) as $id => $name) {
	$rows[] = array(
		'id' => (int)$id,
		'name' => $name,
	);
}

echo json_encode($rows, JSON_UNESCAPED_UNICODE);

?>
