<?php
if (!defined('UC_SYSOP')) {
    die('Доступ запрещён');
}

$title       = isset($title) ? (string)$title : '';
$keywords    = isset($keywords) ? (string)$keywords : '';
$description = isset($description) ? (string)$description : '';
$ss_uri      = isset($ss_uri) ? (string)$ss_uri : 'default';

$site_url  = !empty($DEFAULTBASEURL) ? rtrim((string)$DEFAULTBASEURL, '/') : '';
$site_name = !empty($SITENAME) ? (string)$SITENAME : 'Кинозал.ТВ';

if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

$is_logged = !empty($CURUSER) && is_array($CURUSER);

$user_id        = $is_logged ? (int)$CURUSER['id'] : 0;
$username       = $is_logged ? (string)$CURUSER['username'] : 'Гость';
$user_class     = $is_logged ? (int)$CURUSER['class'] : 0;
$user_class_css = 'u' . $user_class;
$user_uploaded  = $is_logged ? (float)$CURUSER['uploaded'] : 0;
$user_download  = $is_logged ? (float)$CURUSER['downloaded'] : 0;
$user_bonus     = $is_logged && function_exists('pay_user_votes_from_array') ? pay_user_votes_from_array($CURUSER) : ($is_logged && isset($CURUSER['bonus']) ? $CURUSER['bonus'] : 0);

$uped   = ($is_logged && function_exists('mksize')) ? mksize($user_uploaded) : '0 Б';
$downed = ($is_logged && function_exists('mksize')) ? mksize($user_download) : '0 Б';

if ($is_logged && $user_download > 0) {
    $ratio_num = $user_uploaded / $user_download;
    $ratio_txt = number_format($ratio_num, 3);

    if (function_exists('get_ratio_color')) {
        $color = get_ratio_color($ratio_txt);
        $ratio = $color
            ? '<span style="color:' . h($color) . '">' . h($ratio_txt) . '</span>'
            : h($ratio_txt);
    } else {
        $ratio = h($ratio_txt);
    }
} elseif ($is_logged && $user_uploaded > 0) {
    $ratio = 'Inf.';
} else {
    $ratio = '---';
}

$messages    = 0;
$unread      = 0;
$outmessages = 0;
$activeseed  = 0;
$activeleech = 0;

if ($is_logged) {
    $res = sql_query("
		SELECT
			COALESCE(SUM(receiver = $user_id AND location = 1), 0) AS messages,
			COALESCE(SUM(receiver = $user_id AND location = 1 AND unread = 'yes'), 0) AS unread,
			COALESCE(SUM(sender = $user_id AND saved = 'yes'), 0) AS outmessages
		FROM messages
		WHERE receiver = $user_id OR sender = $user_id
	");

    if ($res && ($row = mysqli_fetch_assoc($res))) {
        $messages    = (int)$row['messages'];
        $unread      = (int)$row['unread'];
        $outmessages = (int)$row['outmessages'];
    }

    $res = sql_query("
		SELECT
			COALESCE(SUM(seeder = 'yes'), 0) AS activeseed,
			COALESCE(SUM(seeder = 'no'), 0) AS activeleech
		FROM peers
		WHERE userid = $user_id
	");

    if ($res && ($row = mysqli_fetch_assoc($res))) {
        $activeseed  = (int)$row['activeseed'];
        $activeleech = (int)$row['activeleech'];
    }
}

$logout_url = '/logout.php';

if ($is_logged) {
    if (!empty($CURUSER['logout_hash'])) {
        $logout_url = '/logout.php?hash4u=' . urlencode((string)$CURUSER['logout_hash']);
    } elseif (!empty($CURUSER['hash4u'])) {
        $logout_url = '/logout.php?hash4u=' . urlencode((string)$CURUSER['hash4u']);
    }
}

$page_title = $title !== '' ? $title : $site_name;
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title><?= h($page_title) ?></title>

    <?php if ($description !== '') { ?>
        <meta name="description" content="<?= h($description) ?>">
    <?php } else { ?>
        <meta name="description" content="Торрент трекер <?= h($site_name) ?> - фильмы и сериалы, мультфильмы, книги и музыка">
    <?php } ?>

    <?php if ($keywords !== '') { ?>
        <meta name="keywords" content="<?= h($keywords) ?>">
    <?php } ?>

    <meta name="robots" content="index,follow">

    <link rel="shortcut icon" href="/pic/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="./themes/<?= h($ss_uri) ?>/<?= h($ss_uri) ?>.css" type="text/css">
    <?php if (in_array(basename($_SERVER['PHP_SELF'] ?? ''), array('upload.php', 'edit.php'), true)) { ?>
        <link rel="stylesheet" href="/pic/upload/_upload.css" type="text/css">
    <?php } ?>

    <script type="text/javascript" src="js/jquery.js"></script>
    <script type="text/javascript" src="js/jquery.migrate.js"></script>
    <script type="text/javascript" src="js/jquery.cookies.js"></script>
    <script type="text/javascript" src="js/resizer.js"></script>
    <script type="text/javascript" src="js/blocks.js"></script>
    <script type="text/javascript" src="js/use.js"></script>

    <script type="text/javascript">
        function mess_out(text) {
            return confirm(text || 'Вы действительно хотите выйти?');
        }

        function initSpoilers(context) {
            context = context || 'body';

            if (typeof jQuery === 'undefined') {
                return;
            }

            $('div.spoiler-head', $(context)).off('click.spoiler').on('click.spoiler', function() {
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

        function getRetio() {

            var block = document.getElementById('user_retio');
            if (!block) {
                return false;
            }
            if (block.innerHTML.replace(/\s+/g, '') !== '') {
                block.innerHTML = '';
                return false;
            }
            block.innerHTML =
                '<div class="bx2_0" style="margin-top:6px;">' +
                '<table class="w100p" cellpadding="0" cellspacing="0">' +
                '<tr>' +
                '<td style="width:54px; vertical-align:top; padding:5px;">' +
                '<img src="/pic/default_avatar.gif" alt="" style="width:48px; height:48px; border:1px solid #e7c98e;">' +
                '</td>' +
                '<td class="small" style="vertical-align:middle; text-align:right; padding:5px; line-height:1.45;">' +
                '<b>Рейтинг:</b> <?= addslashes(strip_tags((string)$ratio)) ?><br>' +
                '<b>Залил:</b> <?= addslashes(strip_tags((string)$uped)) ?><br>' +
                '<b>Скачал:</b> <?= addslashes(strip_tags((string)$downed)) ?>' +
                '</td>' +
                '</tr>' +
                '</table>' +
                '</div>';
            return false;
        }

        $(document).ready(function() {
            initSpoilers('body');

            if ($.fn.lightBox) {
                $('a[rel*=lightbox]').lightBox();
            }
        });
    </script>
</head>

<body>
    <div id="body_wrapper">

        <div id="header">
            <table style="width:100%; padding:0; margin:0; border:0;">
                <tr>
                    <td style="width:43%;">
                        <div class="logo_new">
                            <a href="<?= h($site_url !== '' ? $site_url : '/') ?>" title="<?= h($site_name) ?>">
                                <img src="./themes/<?= h($ss_uri) ?>/images/logo.gif" alt="<?= h($site_name) ?>">
                            </a>
                        </div>
                    </td>

                    <td style="width:57%; text-align:center;">
                        <div class="rb_new" style="height:93px; overflow:hidden;">
                            <?php
                            // Место под баннер / рекламу.
                            ?>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="clr"></div>

            <div class="menu">
                <ul>
                    <li><a href="/" title="Главная">Главная</a></li>
                    <li><a href="/forums.php" title="Форум">Форум</a></li>
                    <?php if (!$is_logged) { ?>
                        <li><a href="/signup.php" title="Регистрация">Регистрация</a></li>
                    <?php } ?>
                    <li><a href="/browse.php" title="Каталог раздач">Раздачи</a></li>
                    <li><a href="/top.php" title="Топ раздач">Топ раздач</a></li>
                    <li><a href="/personsearch.php" title="Персоны">Персоны</a></li>
                    <li><a href="/novinki.php" title="Новинки кино">Новинки кино</a></li>
                    <li><a href="/groupexlist.php" title="Каталог групп">Группы</a></li>
                    <li><a href="/radio.php" title="Радио">Радио</a></li>
                </ul>

                <form action="/browse.php" method="get" id="srchform">
                    <div>
                        <input type="text" class="inp" id="s" name="s" size="15" value="">
                        <input class="s_submit" type="submit" title="Поиск раздач" value="">
                    </div>
                </form>

                <div class="clr"></div>
            </div>

            <span class="zan_l"></span>
            <span class="zan_r"></span>
        </div>

        <div class="clr"></div>

        <div id="main">
            <div class="menu">

                <div class="bx2_0">
                    <ul class="men">
                        <?php if ($is_logged) { ?>
                            <li class="tp2 center b">
                                <a href="/userdetails.php?id=<?= $user_id ?>" class="<?= h($user_class_css) ?>"><?= h($username) ?></a>
                                (
                                <a onclick="return mess_out('Вы действительно хотите выйти?')" href="<?= h($logout_url) ?>">Выход</a>
                                )
                            </li>

                            <li style="padding-left:14px;">
                                <span class="bulet"></span><a href="/userdetails.php?id=<?= $user_id ?>">Ваш профиль</a>
                            </li>

                            <li style="padding-left:14px;">
                                <span class="bulet"></span><a href="/my.php">Конфигурация</a>
                            </li>

                            <li style="padding-left:14px;">
                                <span class="bulet"></span>
                                <a href="/inbox.php">
                                    ЛС:
                                    <?php if ($unread > 0) { ?>
                                        ( <?= $unread ?> новых )
                                    <?php } else { ?>
                                        ( нет новых )
                                    <?php } ?>
                                </a>
                            </li>

                            <li style="padding-left:14px;">
                                <span class="bulet"></span><a href="/friends.php">Друзья и враги</a>
                            </li>

                            <li style="padding-left:14px;" class="b">
                                <span class="bulet"></span>
                                <a onclick="return getRetio();" href="#" class="sbab">Рейтинг</a>,
                                время: <?= date('H:i') ?>
                            </li>
                        <?php } else { ?>
                            <form method="post" action="/takelogin.php">
                                <li class="tp2 center"><a href="/signup.php" class="sbab">Гость! ( Зарегистрируйтесь )</a></li>
                                <li class="right b">Логин: <input type="text" name="username" class="w90" value=""></li>
                                <li class="right b">Пароль: <input type="password" name="password" class="w90" value=""></li>
                                <li class="right b">
                                    <a href="/recover.php" class="sbab">Восстановление!</a>
                                    <input type="hidden" name="returnto" value="<?= h($_SERVER['REQUEST_URI'] ?? '') ?>">
                                    <input class="buttonS" type="submit" value="Вход">
                                </li>
                            </form>
                        <?php } ?>
                    </ul>

                    <div id="user_retio"></div>
                </div>

                <?php if ($is_logged) { ?>
                    <div class="bx2_0">
                        <a href="/pay.php" title="Раздел Меценатов">
                            <img src="./themes/<?= h($ss_uri) ?>/images/bnr_pay_sm.jpg" class="block" height="35" width="184" alt="">
                        </a>
                    </div>

                    <div class="bx2_0">
                        <ul class="men">
                            <li><span class="bulet"></span><a href="/pay.php">Поднять рейтинг и голоса</a></li>
                            <li><span class="bulet"></span><a href="/pay_wishes.php">Пожелания проекту</a></li>
                            <li><span class="bulet"></span><a href="/pay_help.php">Техподдержка</a></li>
                            <li><span class="bulet"></span><a href="/users.php">Список пользователей</a></li>
                        </ul>
                    </div>
                <?php } ?>

                <div class="bx2_0">
                    <ul class="men imgmn">
                        <li class="tp2 center">Меню раздач</li>
                        <li><span class="bulet"></span><a href="/browse.php">Раздачи трекера</a></li>
                        <li><span class="bulet"></span><a href="/top.php">Топ раздач</a></li>
                        <li><span class="bulet"></span><a href="/persons.php">Персоны</a></li>
                        <li><span class="bulet"></span><a href="/novinki.php">Новинки кино</a></li>
                        <?php if ($is_logged) { ?>
                            <li><span class="bulet"></span><a href="/browsetest.php">Тестовые раздачи</a></li>
                            <li><span class="bulet"></span><a href="/upload.php">Залить раздачу</a></li>
                            <li><span class="bulet"></span><a href="/group.php">Релиз-группы</a></li>
                        <?php } ?>
                    </ul>
                </div>

                <?php if (!$is_logged) { ?>
                    <div class="bx2_0">
                        <ul class="men">
                            <li class="justify">На сайте представлено невероятное количество классических и современных кинолент мирового и отечественного кинематографа: блокбастеры, комедии, сериалы, мультфильмы, новинки кино, а также разнообразная музыка, игры и программы. Любой киноман найдет фильм по своему вкусу. Заходите, знакомьтесь и присоединяйтесь к нам! Вы не останетесь равнодушными, окунувшись в сказочный мир кино и доброжелательную атмосферу.</li>
                        </ul>
                    </div>
                <?php } ?>

                <div class="bx2_0">
                    <ul class="men">
                        <li class="tp2 center">Прочтите рекомендации</li>
                        <li><span class="bulet"></span><a href="/doku.php">Общие правила</a></li>
                        <li><span class="bulet"></span><a href="/doku.php?type=1">Правила пользователей</a></li>
                        <li><span class="bulet"></span><a href="/doku.php?type=2">Правила Кинооператоров</a></li>
                        <li><span class="bulet"></span><a href="/doku.php?type=3">Как скачать фильм</a></li>
                        <li><span class="bulet"></span><a href="/copyright.php">Для правообладателей</a></li>
                    </ul>
                </div>



            </div>

            <div class="content">

                <?php $hide_right_blocks = !empty($hide_right_blocks); ?>

                <?php if (!$hide_right_blocks) { ?>
                    <div class="mn3_menu">
                        <?php
                        $current_module = str_replace('.php', '', basename($_SERVER['PHP_SELF'] ?? ''));

                        require_once ROOT_PATH . 'include/uarch.php';
                        echo uarch_block_html();
                        echo '<div class="pad0x0x5x0 center">';
                        echo '<a href="/radio.php" title="Радио Кинозал.ТВ">';
                        echo '<img src="/pic/radio_ban.jpg" height="57" class="w190 block" alt="Радио Кинозал.ТВ">';
                        echo '</a>';
                        echo '</div>';

                        if (function_exists('show_blocks')) {
                            show_blocks('r');
                        }
                        ?>
                    </div>
                <?php } ?>

                <div class="mn3_content" <?= $hide_right_blocks ? ' style="margin-right:0; width:auto;"' : '' ?>>

                    <?php
                    if ($is_logged && $unread > 0) {
                        echo '<div class="bx1">';
                        echo '<div class="pad10x10 center b">';
                        echo '<a href="/inbox.php" class="sba">У вас новых личных сообщений: ' . (int)$unread . '</a>';
                        echo '</div>';
                        echo '</div>';
                    }
                    $current_module = isset($current_module)
                        ? $current_module
                        : str_replace('.php', '', basename($_SERVER['PHP_SELF'] ?? ''));

                    if ($current_module !== 'radio') {
                        show_blocks('c');
                    }
