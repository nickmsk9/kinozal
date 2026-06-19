<?php

if (!defined('UC_SYSOP'))
    die('Direct access denied.');

global $hide_right_blocks;

show_blocks('d');
?>
</td>
<?php if (empty($hide_right_blocks)) { ?>
    <td valign="top" width="170">
        <?php
        show_blocks('r');
        ?>
    </td>
<?php } ?>
<?php

$seconds = timer() - $tstart;

$sqlTime = (float)$querytime;
$phpTime = max(0, $seconds - $sqlTime);

$sqlPercent = $seconds > 0 ? number_format(($sqlTime / $seconds) * 100, 2) : '0.00';
$phpPercent = $seconds > 0 ? number_format(($phpTime / $seconds) * 100, 2) : '0.00';

$secondsView = number_format($seconds, 4);
$sqlTimeView = number_format($sqlTime, 4);
$phpTimeView = number_format($phpTime, 4);

$gzipStatus = !empty($gzip) ? 1 : 0;

// Если переменные кеша/отложенных запросов есть в движке — покажет их.
// Если нет — не будет сыпать ошибками.
$cachedQueries = isset($cached_queries) ? (int)$cached_queries : 0;
$delayedTime = isset($delayed_time) ? number_format((float)$delayed_time, 4) : '0.0000';
$footerColspan = empty($hide_right_blocks) ? 3 : 2;
$engineCopyrightHtml = engine_copyright_notice();
$engineCopyrightTitle = engine_copyright_notice('attr');

print("
</tr>
<tr>
    <td colspan='{$footerColspan}' class=\"is_foot\">
        <b>
            .:Кинозал.ТВ
            <noindex>
                <a href=\"?copyright\" class=\"copyright\" title=\"{$engineCopyrightTitle}\">©</a>
            </noindex>
            2026 TBDev v.Core:.
        </b>
        <br />
        {$engineCopyrightHtml}
        <br />
        Страничка сгенерирована за {$secondsView} секунд (gzip {$gzipStatus}, cache showing)
        <br />
        <b>{$queries}</b>, <b>{$sqlPercent}%</b>
        (queries, {$sqlTimeView} -> sql, {$cachedQueries} -> cached) -
        <b>{$phpPercent}%</b> ({$phpTimeView} -> php, {$delayedTime} -> delayed)
    </td>
</tr>
</table>
</body>
</html>
");

?>
