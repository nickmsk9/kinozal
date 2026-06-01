<?php

if (!defined('BLOCK_FILE')) {
	header('Location: ../index.php');
	exit;
}

require_once 'include/uarch.php';

$blocktitle = 'Улыбка';
$content = uarch_block_html();

?>
