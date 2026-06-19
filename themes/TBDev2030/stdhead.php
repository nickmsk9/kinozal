<?php
if (!defined('UC_SYSOP'))
    die('Direct access denied.');

$title = $title ?? '';
$keywords = $keywords ?? '';
$description = $description ?? '';
$DEFAULTBASEURL = $GLOBALS['DEFAULTBASEURL'] ?? '';
$pic_base_url = $GLOBALS['pic_base_url'] ?? './pic';
$theme_uri = htmlspecialchars((string)($ss_uri ?? 'TBDev2030'), ENT_QUOTES, 'UTF-8');
$site_name = htmlspecialchars((string)($SITENAME ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="engine-copyright" content="<?= engine_copyright_notice('attr') ?>">
    <link rel="stylesheet" href="./themes/<?= $theme_uri ?>/TBDev.css?v=20260617-1" type="text/css">
    <link rel="stylesheet" href="./themes/<?= $theme_uri ?>/engine.css?v=20260617-1" type="text/css">
    <?php if (in_array(basename($_SERVER['PHP_SELF'] ?? ''), array('upload.php', 'edit.php'), true)) { ?>
        <link rel="stylesheet" href="./themes/<?= $theme_uri ?>/upload.css?v=20260617-1" type="text/css">
    <?php } ?>
    <script language="javascript" type="text/javascript" src="js/resizer.js"></script>
    <script language="javascript" type="text/javascript" src="js/jquery.js"></script>
    <script language="javascript" type="text/javascript" src="js/blocks.js"></script>
    <script type="text/javascript">
        <!--
        var ExternalLinks_InNewWindow = '1';

        function initSpoilers(context) {
            var context = context || 'body';
            $('div.spoiler-head', $(context))
                .click(function() {
                    var ctx = $(this).next('div.spoiler-body');
                    var code = ctx.children('textarea').text();
                    if (code) {
                        ctx.children('textarea').replaceWith(code);
                        initSpoilers(ctx);
                    }
                    $(this).toggleClass('unfolded');
                    $(this).next('div.spoiler-body').slideToggle('fast');
                    $(this).next('div.spoiler-body').next().slideToggle('fast');
                });
        }

        $(document).ready(function() {
            initSpoilers('body');
        });

        //
        -->
    </script>
    <?php
    if ($keywords)
        echo '<meta name="keywords" content="' . htmlspecialchars((string)$keywords, ENT_QUOTES, 'UTF-8') . "\" />\n";
    if ($description)
        echo '<meta name="description" content="' . htmlspecialchars((string)$description, ENT_QUOTES, 'UTF-8') . "\" />\n";
    ?>
    <link rel="alternate" type="application/rss+xml" title="Последние торренты" href="<?= $DEFAULTBASEURL ?>/rss.php">
    <link rel="shortcut icon" href="<?= $DEFAULTBASEURL; ?>/favicon.ico" type="image/x-icon" />
</head>

<body>

    <table width="90%" class="clear" align="center" border="0" cellspacing="0" cellpadding="0" style="background: transparent;">
        <tr>
            <td class="embedded" width="50%" background="./themes/<?= $theme_uri ?>/images/logobg.gif">
                <a href="<?= $DEFAULTBASEURL ?>"><img style="border: none" alt="<?= $site_name ?>" title="<?= $site_name ?>" src="./themes/<?= $theme_uri ?>/images/logo.gif" /></a>
            </td>
            <td class="embedded" width="50%" align="right" style="text-align: right" background="./themes/<?= $theme_uri ?>/images/logobg.gif">
            </td>
        </tr>
    </table>

    <!-- Top navigation -->
    <table width="90%" align="center" border="0" cellspacing="0" cellpadding="6">
        <tr>
            <td align="center" class="topnav">
                <a href="/">Главная</a><span class="nav-sep">•</span>
                <a href="/browse.php">Раздачи</a><span class="nav-sep">•</span>
                <a href="/top.php">Топ раздач</a><span class="nav-sep">•</span>
                <a href="/personsearch.php">Персоны</a><span class="nav-sep">•</span>
                <a href="/novinki.php">Новинки кино</a><span class="nav-sep">•</span>
                <a href="/groupexlist.php">Группы</a><span class="nav-sep">•</span>
                <a href="/radio.php">Радио</a>
            </td>
        </tr>
    </table>
    <!-- /Top navigation -->

    <!-- /////// some vars for the statusbar;o) //////// -->

    <?php if ($CURUSER) { ?>

        <?php

        $uped = mksize($CURUSER['uploaded']);
        $downed = mksize($CURUSER['downloaded']);

        if ($CURUSER["downloaded"] > 0) {
            $ratio = $CURUSER['uploaded'] / $CURUSER['downloaded'];
            $ratio = number_format($ratio, 3);
            $color = get_ratio_color($ratio);
            if ($color)
                $ratio = "<font color=$color>$ratio</font>";
        } elseif ($CURUSER["uploaded"] > 0)
            $ratio = "Inf.";
        else
            $ratio = "---";

        $medaldon = $warn = '';

        if ($CURUSER['donor'] == "yes")
            $medaldon = "<img src=\"{$pic_base_url}/star.gif\" alt=\"Донор\" title=\"Донор\">";
        if ($CURUSER['warned'] == "yes")
            $warn = "<img src=\"{$pic_base_url}/warned.gif\" alt=\"Предупрежден\" title=\"Предупрежден\">";

        //// check for messages //////////////////
        $uid = (int)$CURUSER["id"];
        $res1 = sql_query("
                SELECT
                        SUM(receiver = $uid AND location = 1) AS messages,
                        SUM(receiver = $uid AND location = 1 AND unread = 'yes') AS unread,
                        SUM(sender = $uid AND saved = 'yes') AS outmessages
                FROM messages
                WHERE (receiver = $uid AND location = 1)
                   OR (sender = $uid AND saved = 'yes')
        ") or sqlerr(__FILE__, __LINE__);
        $arr1 = mysqli_fetch_assoc($res1);
        $messages = (int)($arr1['messages'] ?? 0);
        $unread = (int)($arr1['unread'] ?? 0);
        $outmessages = (int)($arr1['outmessages'] ?? 0);
        if ($unread)
            $inboxpic = "<img height=\"16px\" style=\"border:none\" alt=\"inbox\" title=\"Есть новые сообщения\" src=\"{$pic_base_url}/pn_inboxnew.gif\">";
        else
            $inboxpic = "<img height=\"16px\" style=\"border:none\" alt=\"inbox\" title=\"Нет новых сообщений\" src=\"{$pic_base_url}/pn_inbox.gif\">";

        $res2 = sql_query("
        SELECT
                SUM(seeder = 'yes') AS activeseed,
                SUM(seeder = 'no') AS activeleech
        FROM peers
        WHERE userid = $uid
") or sqlerr(__FILE__, __LINE__);
        $row = mysqli_fetch_assoc($res2);
        $activeseed = (int)($row['activeseed'] ?? 0);
        $activeleech = (int)($row['activeleech'] ?? 0);

        //// end

        ?>

        <!-- //////// start the statusbar ///////////// -->

        <table align="center" cellpadding="4" cellspacing="0" border="0" style="width:90%">
            <tr>
                <td class="tablea">
                    <table align="center" style="width:100%" cellspacing="0" cellpadding="0" border="0">
                        <tr>
                            <td class="bottom" align="left"><span class="smallfont"><?= $tracker_lang['welcome_back']; ?><b><a
                                            href="userdetails.php?id=<?= $CURUSER['id'] ?>"><?= get_user_class_color($CURUSER['class'], $CURUSER['username']) ?></a></b><?= $medaldon ?><?= $warn ?>
                                    &nbsp; [<a href="bookmarks.php">Закладки</a>] [<a href="pay.php">Голоса и рейтинг</a>] [<a
                                        href="logout.php">Выйти</a>]<br />
                                    <font color=1900D1><?= $tracker_lang['ratio']; ?>:</font> <?= $ratio ?>&nbsp;&nbsp;<font
                                        color=green><?= $tracker_lang['uploaded']; ?>:</font>
                                    <font
                                        color=black><?= $uped ?></font>&nbsp;&nbsp;<font
                                        color=darkred><?= $tracker_lang['downloaded']; ?>:</font>
                                    <font
                                        color=black><?= $downed ?></font>&nbsp;&nbsp;<font
                                        color=darkblue><?= $tracker_lang['bonus']; ?>:</font> <a href="pay.php"
                                        class="online">
                                        <font
                                            color=black><?= $CURUSER["bonus"] ?></font>
                                    </a>&nbsp;&nbsp;<font color="1900D1"><?= $tracker_lang['torrents']; ?>:&nbsp;</font>
                                </span>
                                <img alt="<?= $tracker_lang['seeding']; ?>" title="<?= $tracker_lang['seeding']; ?>" src="./themes/<?= $ss_uri; ?>/images/arrowup.gif">&nbsp;<font
                                    color="black"><span class="smallfont"><?= $activeseed ?></span></font>&nbsp;&nbsp;<img
                                    alt="<?= $tracker_lang['leeching']; ?>" title="<?= $tracker_lang['leeching']; ?>" src="./themes/<?= $ss_uri; ?>/images/arrowdown.gif">&nbsp;<font
                                    color="black"><span class="smallfont"><?= $activeleech ?></span></font>
                            </td>
                            <td class="bottom" align="right"><span class="smallfont"><?= $tracker_lang['clock']; ?>: <span
                                        id="clock"><?= $tracker_lang['loading']; ?>...</span>

                                    <!-- clock hack -->
                                    <script type="text/javascript">
                                        function refrClock() {
                                            var d = new Date();
                                            var s = d.getSeconds();
                                            var m = d.getMinutes();
                                            var h = d.getHours();
                                            var day = d.getDay();
                                            var date = d.getDate();
                                            var month = d.getMonth();
                                            var year = d.getFullYear();
                                            var am_pm;
                                            if (s < 10) {
                                                s = "0" + s
                                            }
                                            if (m < 10) {
                                                m = "0" + m
                                            }
                                            if (h <= 12) {
                                                am_pm = "AM"
                                            } else {
                                                h -= 12;
                                                am_pm = "PM"
                                            }
                                            if (h < 10) {
                                                h = "0" + h
                                            }
                                            document.getElementById("clock").innerHTML = h + ":" + m + ":" + s + " " + am_pm;
                                            setTimeout("refrClock()", 1000);
                                        }
                                        refrClock();
                                    </script>
                                    <!-- / clock hack -->

                                    <?php
                                    if ($messages) {
                                        print("<span class=smallfont><a href=/inbox.php>$inboxpic</a> $messages ($unread новых)</span>");
                                        if ($outmessages)
                                            print("<span class=smallfont>&nbsp;&nbsp;<a href=/inbox.php><img height=16px style=border:none alt=Отправленые title=Отправленые src={$pic_base_url}/pn_sentbox.gif></a> $outmessages</span>");
                                        else
                                            print("<span class=smallfont>&nbsp;&nbsp;<a href=/inbox.php><img height=16px style=border:none alt=Отправленые title=Отправленые src={$pic_base_url}/pn_sentbox.gif></a> 0</span>");
                                    } else {
                                        print("<span class=smallfont><a href=/inbox.php><img height=16px style=border:none alt=Полученные title=Полученные src={$pic_base_url}/pn_inbox.gif></a> 0</span>");
                                        if ($outmessages)
                                            print("<span class=smallfont>&nbsp;&nbsp;<a href=/inbox.php><img height=16px style=border:none alt=Отправленые title=Отправленые src={$pic_base_url}/pn_sentbox.gif></a> $outmessages</span>");
                                        else
                                            print("<span class=smallfont>&nbsp;&nbsp;<a href=/inbox.php><img height=16px style=border:none alt=Отправленые title=Отправленые src={$pic_base_url}/pn_sentbox.gif></a> 0</span>");
                                    }
                                    print("&nbsp;<a href=friends.php><img style=border:none alt=Друзья title=Друзья src={$pic_base_url}/buddylist.gif></a>");
                                    print("&nbsp;<a href=getrss.php><img style=border:none alt=RSS title=RSS src={$pic_base_url}/rss.gif></a>");
                                    ?>
                                </span></td>

                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <p>

        <?php } else { ?>

            <br />

        <?php } ?>
        <!-- /////////// here we go, with the menu //////////// -->

        <?php

        $w = "width=\"90%\"";
        //if ($_SERVER["REMOTE_ADDR"] == $_SERVER["SERVER_ADDR"]) $w = "width=984";

        ?>
        <table class="mainouter" align="center" <?= $w; ?> border="1" cellspacing="0" cellpadding="5">
            <tr>

                <!------------- MENU ------------------------------------------------------------------------>

                <?php $fn = substr($_SERVER['PHP_SELF'], strrpos($_SERVER['PHP_SELF'], "/") + 1); ?>

                <td valign="top" width="170">
                    <?php

                    $messages = $messages ?? 0;
                    $unread = $unread ?? 0;
                    $outmessages = $outmessages ?? 0;
                    $inboxpic = $inboxpic ?? '';
                    $activeseed = $activeseed ?? 0;
                    $activeleech = $activeleech ?? 0;
                    $ratio = $ratio ?? '---';
                    $uped = $uped ?? '0 B';
                    $downed = $downed ?? '0 B';
                    $medaldon = $medaldon ?? '';
                    $warn = $warn ?? '';
                    $usrclass = '';

                    show_blocks("l");

                    if ($messages) {
                        $message_in = "<span class=\"smallfont\">&nbsp;<a href=\"/inbox.php\">$inboxpic</a> $messages " . sprintf($tracker_lang["new_pm"], $unread) . "</span>";
                        if ($outmessages)
                            $message_out = "<span class=\"smallfont\">&nbsp;<a href=\"/inbox.php\"><img height=\"16px\" style=\"border:none\" alt=\"" . $tracker_lang['outbox'] . "\" title=\"" . $tracker_lang['outbox'] . "\" src=\"{$pic_base_url}/pn_sentbox.gif\"></a> $outmessages</span>";
                        else
                            $message_out = "<span class=\"smallfont\">&nbsp;<a href=\"/inbox.php\"><img height=\"16px\" style=\"border:none\" alt=\"" . $tracker_lang['outbox'] . "\" title=\"" . $tracker_lang['outbox'] . "\" src=\"{$pic_base_url}/pn_sentbox.gif\"></a> 0</span>";
                    } else {
                        $message_in = "<span class=\"smallfont\">&nbsp;<a href=\"/inbox.php\"><img height=\"16px\" style=\"border:none\" alt=\"{$tracker_lang['inbox']}\" title=\"{$tracker_lang['inbox']}\" src=\"{$pic_base_url}/pn_inbox.gif\"></a> 0</span>";
                        if ($outmessages)
                            $message_out = "<span class=\"smallfont\">&nbsp;<a href=\"/inbox.php\"><img height=\"16px\" style=\"border:none\" alt=\"" . $tracker_lang['outbox'] . "\" title=\"" . $tracker_lang['outbox'] . "\" src=\"{$pic_base_url}/pn_sentbox.gif\"></a> $outmessages</span>";
                        else
                            $message_out = "<span class=\"smallfont\">&nbsp;<a href=\"/inbox.php\"><img height=\"16px\" style=\"border:none\" alt=\"" . $tracker_lang['outbox'] . "\" title=\"" . $tracker_lang['outbox'] . "\" src=\"{$pic_base_url}/pn_sentbox.gif\"></a> 0</span>";
                    }

                    if ($CURUSER) {
                        $remoteAddr = htmlspecialchars_uni($_SERVER["REMOTE_ADDR"] ?? '');

                        $userbar = "<center><a href=\"my.php\"><img src=\"" . ($CURUSER["avatar"] ? $CURUSER["avatar"] : "./themes/$ss_uri/images/default_avatar.gif") . "\" width=\"100\" alt=\"{$tracker_lang['avatar']}\" title=\"{$tracker_lang['avatar']}\" border=\"0\" /></a></center>
	<br />
	<font color=\"1900D1\">{$tracker_lang['ratio']}:</font>&nbsp;{$ratio}<br />
	<font color=\"green\">{$tracker_lang['uploaded']}:</font>&nbsp;{$uped}<br />
	<font color=\"red\">{$tracker_lang['downloaded']}:</font>&nbsp;{$downed}<br />
	<font color=\"darkblue\">{$tracker_lang['bonus']}:</font>&nbsp;<a href=\"pay.php\" class=\"online\"><font color=black>$CURUSER[bonus]</font></a><br />
	<font color=\"blue\">{$tracker_lang['pm']}:</font>&nbsp;{$message_in} {$message_out}<br />
	{$tracker_lang['torrents']}:&nbsp;
	<img alt=\"{$tracker_lang['seeding']}\" title=\"{$tracker_lang['seeding']}\" src=\"./themes/$ss_uri/images/arrowup.gif\">&nbsp;<font color=green><span class=\"smallfont\">{$activeseed}</span></font>&nbsp;
	<img alt=\"{$tracker_lang['leeching']}\" title=\"{$tracker_lang['leeching']}\" src=\"./themes/$ss_uri/images/arrowdown.gif\">&nbsp;<font color=red><span class=\"smallfont\">{$activeleech}</span></font><br />
	{$tracker_lang['clock']}:&nbsp;<span id=\"clock2\">{$tracker_lang['loading']}...</span>

<!-- clock hack -->
<script type=\"text/javascript\">
function refrClock2()
{
var d=new Date();
var s=d.getSeconds();
var m=d.getMinutes();
var h=d.getHours();
var day=d.getDay();
var date=d.getDate();
var month=d.getMonth();
var year=d.getFullYear();
var am_pm;
if (s<10) {s=\"0\" + s}
if (m<10) {m=\"0\" + m}
if (h>12) {h-=12;am_pm = \"PM\"}
else {am_pm=\"AM\"}
if (h<10) {h=\"0\" + h}
document.getElementById(\"clock2\").innerHTML=h + \":\" + m + \":\" + s + \" \" + am_pm;
setTimeout(\"refrClock2()\",1000);
}
refrClock2();
</script>
<!-- / clock hack --><br />
	<font color=\"#FF6600\">" . $tracker_lang['your_ip'] . ": " . $remoteAddr . "</font><br />
	<br />
	<center><img src=\"{$pic_base_url}/disabled.gif\" border=\"0\" />&nbsp;[<a href=\"logout.php\">{$tracker_lang['logout']}</a>]</center>
	";
                    } else {
                        $userbar = '<center><form method="post" action="takelogin.php">
<br />
' . $tracker_lang['username'] . ': <br />
<input type="text" size=20 name="username" /><br />
' . $tracker_lang['password'] . ': <br />

<input type="password" size=20 name="password" /><br />
<input type="submit" value="' . $tracker_lang['login'] . '!" class=\"btn\"><br /><br />
</form></center>
<a class="menu" href="signup.php"><center>' . $tracker_lang['signup'] . '</center></a>';
                    }

                    if ($CURUSER && $CURUSER['override_class'] != 255) {
                        $className = htmlspecialchars_uni(get_user_class_name($CURUSER['class']));
                        $usrclass = "&nbsp;<img src=\"{$pic_base_url}/warning.gif\" title=\"{$className}\" alt=\"{$className}\">&nbsp;";
                    } elseif ($CURUSER && get_user_class() >= UC_MODERATOR) {
                        $className = htmlspecialchars_uni(get_user_class_name($CURUSER['class']));
                        $usrclass = "&nbsp;<img src=\"{$pic_base_url}/warning.gif\" title=\"{$className}\" alt=\"{$className}\" border=\"0\">&nbsp;";
                    }

                    blok_menu($tracker_lang['welcome_back'] . ($CURUSER ? "<a href=\"$DEFAULTBASEURL/userdetails.php?id=" . $CURUSER["id"] . "\">" . $CURUSER["username"] . "</a>&nbsp;" . $usrclass . "&nbsp;" : "гость") . $medaldon . $warn, $userbar, "155");

                    $mainmenu = "<a class=\"menu\" href=\"/\">Главная</a>"
                        . "<a class=\"menu\" href=\"/browse.php\">Раздачи</a>"
                        . "<a class=\"menu\" href=\"/top.php\">Топ раздач</a>"
                        . "<a class=\"menu\" href=\"/personsearch.php\">Персоны</a>"
                        . "<a class=\"menu\" href=\"/novinki.php\">Новинки кино</a>"
                        . "<a class=\"menu\" href=\"/groupexlist.php\">Группы</a>"
                        . "<a class=\"menu\" href=\"/radio.php\">Радио</a>"
                        . "<a class=\"menu\" href=\"/doku.php\">Правила и помощь</a>";

                    blok_menu($tracker_lang['main_menu'], $mainmenu, "155");

                    if ($CURUSER) {

                        $usermenu = "<a class=\"menu\" href=\"/my.php\">Настройки профиля</a>"
                            . "<a class=\"menu\" href=\"/userdetails.php?id={$CURUSER['id']}\">Мой профиль</a>"
                            . "<a class=\"menu\" href=\"/bookmarks.php\">Закладки</a>"
                            . "<a class=\"menu\" href=\"/pay.php\">Голоса и рейтинг</a>"
                            . "<a class=\"menu\" href=\"/users.php\">Пользователи</a>"
                            . "<a class=\"menu\" href=\"/friends.php\">Друзья и враги</a>"
                            . "<a class=\"menu\" href=\"/mytorrents.php\">Мои раздачи</a>"
                            . "<a class=\"menu\" href=\"/logout.php\">Выйти</a>";

                        blok_menu($tracker_lang['user_menu'], $usermenu, "155");

                        $messages = "<a class=\"menu\" href=\"/inbox.php\">Личные сообщения</a>"
                            . "<a class=\"menu\" href=\"/sendmessage.php\">Написать сообщение</a>";

                        blok_menu($tracker_lang['messages'], $messages, "155");
                    }

                    $bt_clients = '&nbsp;&nbsp;<a href="http://bitconjurer.org/BitTorrent/download.html" target="_blank"><font class=small color=green>' . $tracker_lang['official'] . '</font></a><br />'
                        . '&nbsp;&nbsp;<a href="http://azureus.sourceforge.net/" target="_blank"><font class=small color=green>Azureus (Java)</font></a><br />'
                        . '&nbsp;&nbsp;<a href="http://www.bittornado.com/" target="_blank"><font class=small color=green>BitTornado</font></a><br />'
                        . '&nbsp;&nbsp;<a href="http://www.bitcomet.com/" target="_blank"><font class=small color=green>BitComet</font></a><br />'
                        . '&nbsp;&nbsp;<a href="http://www.bitlord.com/" target="_blank"><font class=small color=green>BitLord</font></a><br />'
                        . '&nbsp;&nbsp;<a href="http://www.macupdate.com/info.php/id/7170" target="_blank"><font class="small" color=green>Acquisition (Mac)</font></a><br />'
                        . '&nbsp;&nbsp;<a href="http://www.167bt.com/intl/" target="_blank"><font class=small color=green>BitSpirit</font></a><br />'
                        . '<hr width=100% color=#ffc58c size=1>'
                        . '<font class=small color=red>&nbsp;&nbsp;' . $tracker_lang['clients_recomened_by_us'] . '</font>';

                    blok_menu($tracker_lang['torrent_clients'], $bt_clients, "155");

                    ?>
                </td>

                <td align="left" valign="top" class="outer" style="padding-top: 5px; padding-bottom: 5px">
                    <?php

                    if ($CURUSER) {
                        if ($unread) {
                            print("<p><table border=0 cellspacing=0 cellpadding=10 bgcolor=red><tr><td style='padding: 10px; background: red'>\n");
                            print("<b><a href=\"/inbox.php\"><font color=white>" . sprintf($tracker_lang['new_pms'], $unread) . "</font></a></b>");
                            print("</td></tr></table></p>\n");
                        }
                    }

                    if (COOKIE_SALT == 'default') {
                        print("<p><table border=0 cellspacing=0 cellpadding=10 bgcolor=orange><tr><td style='padding: 10px; background: orange'>\n");
                        print("<b><font color=white>Администратор, измени COOKIE_SALT в include/init.php прежде, чем выпустить трекер в сеть!</font></b>");
                        print("</td></tr></table></p>\n");
                    }

                    if ($CURUSER && $CURUSER['override_class'] != 255) { // Second condition needed so that this box isn't displayed for non members/logged out members.
                        print("<p><table border=0 cellspacing=0 cellpadding=10 bgcolor=green><tr><td style='padding: 10px; background: green'>\n");
                        print("<b><a href=\"$DEFAULTBASEURL/restoreclass.php\"><font color=white>{$tracker_lang['lower_class']}</font></a></b>");
                        print("</td></tr></table></p>\n");
                    }

                    $current_module = basename($_SERVER['PHP_SELF'] ?? '');
                    if ($current_module !== 'radio.php') {
                        show_blocks('c');
                    }
