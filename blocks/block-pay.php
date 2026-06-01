<?php

if (!defined('BLOCK_FILE')) {
	header('Location: ../index.php');
	exit;
}

require_once ROOT_PATH . 'include/pay.php';

$blocktitle = 'Меценаты';
$content = pay_home_block_html();

?>
