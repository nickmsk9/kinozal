<?php

if (!defined('BLOCK_FILE')) {
	header('Location: ../index.php');
	exit;
}

require_once ROOT_PATH . 'include/kz_pay.php';

$blocktitle = 'Меценаты';
$content = kz_pay_home_block_html();

?>
