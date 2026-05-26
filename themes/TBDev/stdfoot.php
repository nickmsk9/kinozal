<?php

if (!defined('UC_SYSOP')) {
	die('Direct access denied.');
}

$seconds = 0;
$phptime = 0;
$query_time = 0;
$percentphp = 0;
$percentsql = 0;

if (function_exists('timer') && isset($tstart)) {
	$seconds = timer() - $tstart;
}

if ($seconds <= 0) {
	$seconds = 0.001;
}

$querytime = isset($querytime) ? (float)$querytime : 0;
$queries = isset($queries) ? (int)$queries : 0;

$phptime = $seconds - $querytime;
$query_time = $querytime;

$percentphp = number_format(($phptime / $seconds) * 100, 2);
$percentsql = number_format(($query_time / $seconds) * 100, 2);
$seconds = substr((string)$seconds, 0, 8);

$year = date('Y');

if (isset($tracker_lang['page_generated'])) {
	$page_generated = sprintf(
		$tracker_lang['page_generated'],
		$seconds,
		$queries,
		$percentphp,
		$percentsql
	);
} else {
	$page_generated = 'Страница создана за ' . $seconds . ' сек. SQL-запросов: ' . $queries . '. PHP: ' . $percentphp . '%, SQL: ' . $percentsql . '%.';
}

$version = defined('TBVERSION') ? TBVERSION : '';
$beta = '';

if (defined('BETA') && BETA && defined('BETA_NOTICE')) {
	$beta = BETA_NOTICE;
}

if (function_exists('show_blocks')) {
	show_blocks('d');
}

?>


	</div><!-- /.mn3_content -->

	<div class="clr"></div>
</div><!-- /.content -->

<div class="clr"></div>
</div><!-- /#main -->

<div id="footer">
	<div class="footer_inner justify">
		Файлы для обмена предоставлены пользователями,<br>
		Администрация не несет ответственности за их содержание.<br>
		Просьба не заливать файлы, защищенные авторскими правами.<br>
		[ <a class="sba" href="/doku.php">Общие правила</a> ]
		[ <a class="sba" href="/photo.php">Доступные фотохостинги</a> ]
		@2006 - <?= $year ?>
	</div>
</div>

</div><!-- /#body_wrapper -->
</body>
</html>
