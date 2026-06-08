<?php

# IMPORTANT: Do not edit below unless you know what you are doing!
if (!defined('IN_TRACKER')) {
    die('Прямой вызов запрещён.');
}

$orbital_blocks = array();

/**
 * Подставляет переменные в HTML-шаблон блока.
 * Старый код делал это через eval/create_function, что в PHP 8 плохо и небезопасно.
 */
function tracker_blocks_active_rows()
{
	if (!function_exists('tracker_cache_remember')) {
		$rows = array();
		$blocks_res = sql_query('
			SELECT *
			FROM orbital_blocks
			WHERE active = 1
			ORDER BY weight ASC
		') or sqlerr(__FILE__, __LINE__);

		while ($blocks_row = mysqli_fetch_assoc($blocks_res)) {
			$rows[] = $blocks_row;
		}

		return $rows;
	}

	return tracker_cache_remember('blocks:active', 30, function () {
		$rows = array();
		$blocks_res = sql_query('
			SELECT *
			FROM orbital_blocks
			WHERE active = 1
			ORDER BY weight ASC
		') or sqlerr(__FILE__, __LINE__);

		while ($blocks_row = mysqli_fetch_assoc($blocks_res)) {
			$rows[] = $blocks_row;
		}

		return $rows;
	});
}

function tracker_block_cache_ttl($blockfile)
{
    $ttl = array(
        'block-online.php' => 20,
        'block-top-torrents.php' => 60,
        'block-stats.php' => 60,
        'block-releases.php' => 60,
        'block-pay.php' => 120,
        'block-uarch.php' => 120,
        'block-news.php' => 300,
        'block-cups.php' => 300,
        'block-birthday.php' => 1800,
    );

    $blockfile = basename((string) $blockfile);
    return isset($ttl[$blockfile]) ? (int) $ttl[$blockfile] : 60;
}

function tracker_block_cache_key($blockfile, $bid, $position)
{
    global $CURUSER;

    $blockfile = basename((string) $blockfile);
    $path = ROOT_PATH . 'blocks' . DIRECTORY_SEPARATOR . $blockfile;
    $mtime = is_file($path) ? (int) filemtime($path) : 0;
    $module = str_replace('.php', '', basename((string) ($_SERVER['PHP_SELF'] ?? 'index.php')));
    $request = substr(md5((string) ($_SERVER['REQUEST_URI'] ?? '')), 0, 12);
    $class = function_exists('get_user_class') ? (int) get_user_class() : 0;
    $auth = empty($CURUSER) ? 'guest' : 'user';
    $page = isset($_GET['relpage']) ? max(0, (int) $_GET['relpage']) : 0;

    return implode(':', array(
        'block',
        $blockfile,
        (int) $bid,
        (string) $position,
        $module,
        $request,
        $auth,
        $class,
        'page' . $page,
        date('Ymd'),
        'v' . $mtime,
    ));
}

function tracker_block_render_file($blockPath, $fallbackTitle, $fallbackContent)
{
    $blocktitle = $fallbackTitle;
    $content = $fallbackContent;

    ob_start();
    require $blockPath;
    $extra = ob_get_clean();

    if ($extra !== '') {
        $content .= $extra;
    }

    return array(
        'title' => (string) $blocktitle,
        'content' => (string) $content,
    );
}

function render_block_template(string $template, string $title, string $content): string
{
    global $ss_uri, $tracker_lang;

    $replace = array(
        '$title'        => $title,
        '{$title}'      => $title,
        '$content'      => $content,
        '{$content}'    => $content,
        '$ss_uri'       => $ss_uri ?? '',
        '{$ss_uri}'     => $ss_uri ?? '',
    );

    if (is_array($tracker_lang)) {
        foreach ($tracker_lang as $key => $value) {
            if (is_scalar($value)) {
                $replace['$tracker_lang[\'' . $key . '\']'] = (string) $value;
                $replace['$tracker_lang["' . $key . '"]'] = (string) $value;
            }
        }
    }

    return strtr($template, $replace);
}

function render_blocks($blockfile, $blocktitle, $content, $bid, $bposition, $allow_hide)
{
    global $allow_block_hide;

    if ($blockfile !== '') {
        $blockPath = 'blocks/' . basename($blockfile);

        if (is_file($blockPath)) {
            if (!defined('BLOCK_FILE')) {
                define('BLOCK_FILE', 1);
            }

            $cacheKey = tracker_block_cache_key($blockfile, $bid, $bposition);
            $cached = function_exists('tracker_cache_get') ? tracker_cache_get($cacheKey) : null;

            if (is_array($cached) && isset($cached['title'], $cached['content'])) {
                $blocktitle = (string) $cached['title'];
                $content = (string) $cached['content'];
            } else {
                $rendered = tracker_block_render_file($blockPath, $blocktitle, $content);
                $blocktitle = $rendered['title'];
                $content = $rendered['content'];

                if (function_exists('tracker_cache_set')) {
                    tracker_cache_set($cacheKey, $rendered, tracker_block_cache_ttl($blockfile));
                }
            }
        } else {
            $content = '<center>Существует проблема с этим блоком!</center>';
        }
    }

    if (!isset($content) || $content === '') {
        $content = '<center>Существует проблема с этим блоком!</center>';
    }

    $hide_control_allowed = !in_array(
        basename((string) $blockfile),
        array('block-top-torrents.php', 'block-birthday.php'),
        true
    );

    if ($hide_control_allowed && $allow_block_hide && ($allow_hide || get_user_class() >= UC_ADMINISTRATOR)) {
        $hidden_blocks = array();

        if (!empty($_COOKIE['hb'])) {
            $tmp = @unserialize($_COOKIE['hb'], array('allowed_classes' => false));

            if (is_array($tmp)) {
                $hidden_blocks = $tmp;
            }
        }

        $display = 'block';
        $picture = 'minus';
        $alt = 'Скрыть';

        if (in_array($bid, $hidden_blocks, true)) {
            $display = 'none';
            $picture = 'plus';
            $alt = 'Показать';
        }

        $safeBid = htmlspecialchars((string) $bid, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeAlt = htmlspecialchars($alt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $blocktitle .= '&nbsp;<span style="cursor: pointer;" onclick="block_switch(\'' . $safeBid . '\');">'
            . '<img border="0" src="pic/' . $picture . '.gif" id="picb' . $safeBid . '" title="' . $safeAlt . '" alt="' . $safeAlt . '">'
            . '</span>';

        $content = '<span id="sb' . $safeBid . '" style="display: ' . $display . ';">' . $content . '</span>';
    }

    themesidebox($blocktitle, $content, $bposition);

    return null;
}

function themesidebox($title, $content, $pos)
{
    global $blockfile, $b_id, $ss_uri;

    static $bl_mass = array();

    if ($pos === 's' || $pos === 'o') {
        if (empty($blockfile)) {
            $bl_name = 'fly-block-' . $b_id;
        } else {
            $bl_name = 'fly-' . str_replace('.php', '', basename($blockfile));
        }
    } else {
        if (empty($blockfile)) {
            $bl_name = 'block-' . $b_id;
        } else {
            $bl_name = str_replace('.php', '', basename($blockfile));
        }
    }

    $theme = basename((string) $ss_uri);
    $templatePath = 'themes/' . $theme . '/html/' . $bl_name . '.html';

    if (!isset($bl_mass[$bl_name])) {
        $bl_mass[$bl_name] = array(
            'exists' => is_file($templatePath),
            'template' => is_file($templatePath) ? file_get_contents($templatePath) : '',
        );
    }

    if ($bl_mass[$bl_name]['exists']) {
        $html = render_block_template($bl_mass[$bl_name]['template'], (string) $title, (string) $content);

        if ($pos === 'o') {
            return $html;
        }

        echo $html;
        return null;
    }

    switch ($pos) {
        case 'l':
            $fallbackName = 'block-left';
            break;

        case 'r':
            $fallbackName = 'block-right';
            break;

        case 'c':
            $fallbackName = 'block-center';
            break;

        case 'd':
            $fallbackName = 'block-down';
            break;

        case 's':
        case 'o':
            $fallbackName = 'block-fly';
            break;

        default:
            $fallbackName = 'block-all';
            break;
    }

    $fallbackPath = 'themes/' . $theme . '/html/' . $fallbackName . '.html';

    if (!isset($bl_mass[$fallbackName])) {
        $bl_mass[$fallbackName] = array(
            'exists' => is_file($fallbackPath),
            'template' => is_file($fallbackPath) ? file_get_contents($fallbackPath) : '',
        );
    }

    if ($bl_mass[$fallbackName]['exists']) {
        $html = render_block_template($bl_mass[$fallbackName]['template'], (string) $title, (string) $content);

        if ($pos === 'o') {
            return $html;
        }

        echo $html;
        return null;
    }

    $html = '<fieldset><legend>' . $title . '</legend>' . $content . '</fieldset>';

    if ($pos === 'o') {
        return $html;
    }

    echo $html;
    return null;
}

function show_blocks($position)
{
    global $CURUSER, $use_blocks, $already_used, $orbital_blocks, $blockfile;

    static $showed_show_hide = false;

    if (!$use_blocks) {
        return;
    }

    if (empty($already_used)) {
        $orbital_blocks = tracker_blocks_active_rows();
        $already_used = true;
    }

    foreach ($orbital_blocks as $block) {
        if (!$showed_show_hide) {
            echo '<script type="text/javascript" src="js/show_hide.js"></script>';
            $showed_show_hide = true;
        }

        $bid = $block['bid'];
        $content = $block['content'];
        $title = $block['title'];
        $blockfile = $block['blockfile'];
        $bposition = $block['bposition'];
        $allow_hide = ($block['allow_hide'] === 'yes');

        if ($position !== $bposition) {
            continue;
        }

        $view = (int) $block['view'];
        $which = array_map('trim', explode(',', (string) $block['which']));
        $module_name = str_replace('.php', '', basename($_SERVER['PHP_SELF'] ?? ''));
        $is_home_like_module = in_array($module_name, array('index', 'radio'), true);
        $is_home_module = $module_name === 'index';

        if (
            !in_array($module_name, $which, true)
            && !in_array('all', $which, true)
            && !(in_array('ihome', $which, true) && $is_home_like_module)
            && !(in_array('home', $which, true) && $is_home_module)
        ) {
            continue;
        }

        if ($view === 0) {
            render_blocks($blockfile, $title, $content, $bid, $bposition, $allow_hide);
        } elseif ($view === 1 && !empty($CURUSER)) {
            render_blocks($blockfile, $title, $content, $bid, $bposition, $allow_hide);
        } elseif ($view === 2 && get_user_class() >= UC_MODERATOR) {
            render_blocks($blockfile, $title, $content, $bid, $bposition, $allow_hide);
        } elseif ($view === 3 && empty($CURUSER)) {
            render_blocks($blockfile, $title, $content, $bid, $bposition, $allow_hide);
        }
    }
}

?>
