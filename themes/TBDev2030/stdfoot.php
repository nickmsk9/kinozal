<?php

if (!defined('UC_SYSOP'))
	die('Direct access denied.');

global $hide_right_blocks;

	show_blocks('d');
?>
<?php if (empty($hide_right_blocks)) { ?>
<td valign="top" width="155">
<?php
	show_blocks('r');
?>
</td>
<?php } ?>
<?php

// Variables for End Time
$seconds = (timer() - $tstart);

$phptime = 		$seconds - $querytime;
$query_time = 	$querytime;
$percentphp = 	$seconds > 0 ? number_format(($phptime/$seconds) * 100, 2) : 0;
$percentsql = 	$seconds > 0 ? number_format(($query_time/$seconds) * 100, 2) : 0;
$seconds = 		substr($seconds, 0, 8);
	// Хочешь убрать копирайт? (TBVERSION) - Поддержки разработчика, заплати! Не будь быдлом!
	print("</td></tr></table>\n");
	print("<table class=\"bottom\" width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\"><tr valign=\"top\">\n");
	$version = defined('TBVERSION') ? TBVERSION : '';
	$beta_notice = defined('BETA') && BETA && defined('BETA_NOTICE') ? BETA_NOTICE : '';
	print("<td width=\"49%\" class=\"bottom\"><div align=\"center\"><br /><b>".$version.$beta_notice."<br />".sprintf($tracker_lang["page_generated"], $seconds, $queries, $percentphp, $percentsql)."</b></div></td>\n");
	print("</tr></table>\n");
	print("</body></html>\n");
?>
