<?php

if (!defined('BLOCK_FILE')) {
	header('Location: ../index.php');
	exit;
}

require_once 'include/kz_uarch.php';

$blocktitle = 'Улыбка';
$content = kz_uarch_block_html();

?>
