<?php

if (!defined('IN_TRACKER')) {
	die('Прямой вызов запрещён.');
}

function commenttable_h($value)
{
	return htmlspecialchars_uni((string)$value);
}

function commenttable_format_text($text)
{
	$text = html_entity_decode((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	$text = preg_replace('#\[(color|size|font|family|left|right|center|justify|hide|spoiler|code|php)[^\]]*\](.*?)\[/\1\]#isu', '$2', $text);
	$text = htmlspecialchars_uni($text);
	$text = preg_replace('#\[b\](.*?)\[/b\]#isu', '<b>$1</b>', $text);
	$text = preg_replace('#\[i\](.*?)\[/i\]#isu', '<i>$1</i>', $text);
	$text = preg_replace('#\[u\](.*?)\[/u\]#isu', '<u>$1</u>', $text);
	$text = preg_replace('#\[url\](https?://[^\s\[]+)\[/url\]#isu', '<a href="$1" class="sba" target="_blank">$1</a>', $text);
	$text = preg_replace('#\[url=(https?://[^\]\s]+)\](.*?)\[/url\]#isu', '<a href="$1" class="sba" target="_blank">$2</a>', $text);
	$text = preg_replace('#\[img\](https?://[^\s\[]+)\[/img\]#isu', '<img src="$1" class="p200" alt="">', $text);
	$text = format_quote_fieldsets($text);

	$text = preg_replace_callback('#(?<![">])(https?://[^\s<]+)#iu', function ($m) {
		$url = rtrim($m[1], '.,!?');
		$tail = substr($m[1], strlen($url));
		return '<a href="' . commenttable_h($url) . '" class="sba" target="_blank">' . commenttable_h($url) . '</a>' . commenttable_h($tail);
	}, $text);

	return nl2br($text);
}

function commenttable_user_link(array $row)
{
	$userid = (int)($row['user'] ?? $row['userid'] ?? 0);
	$username = (string)($row['username'] ?? '');
	$class = (int)($row['class'] ?? 0);
	if ($userid <= 0 || $username === '') {
		return '<i>unknown</i>';
	}

	$icons = function_exists('get_user_icons') ? get_user_icons(array_merge($row, array('id' => $userid, 'class' => $class))) : '';
	return '<a href="/userdetails.php?id=' . $userid . '" class="u' . $class . '">' . commenttable_h($username) . '</a>' . $icons;
}

function commenttable($rows, $redaktor = "comment")
{
	global $CURUSER;

	foreach ($rows as $row) {
		$avatar = trim((string)($row['avatar'] ?? ''));
		$avatar_html = '';
		if ($avatar !== '' && (!$CURUSER || ($CURUSER['avatars'] ?? 'yes') === 'yes')) {
			$avatar_html = '<img class="cmet_ava" src="' . commenttable_h($avatar) . '" alt="">';
		}

		$country = (int)($row['country'] ?? 0);
		$flag = function_exists('tracker_country_flag_html')
			? tracker_country_flag_html($country, $row['country_flagpic'] ?? '', $row['country_name'] ?? '')
			: ($country > 0 ? "<img src='/pic/emty.gif' class='i2 c$country'/>" : '');
		$user = commenttable_user_link($row);
		$commentid = (int)($row['id'] ?? 0);
		$userid = (int)($row['user'] ?? $row['userid'] ?? 0);
		$actions = array();

		if ($CURUSER) {
			$actions[] = '<a href="/' . commenttable_h($redaktor) . '.php?action=quote&amp;cid=' . $commentid . '" class="sba">Ответить</a>';
		}
		if ($CURUSER && ($userid === (int)$CURUSER['id'] || get_user_class() >= UC_MODERATOR)) {
			$actions[] = '<a href="/' . commenttable_h($redaktor) . '.php?action=edit&amp;cid=' . $commentid . '" class="sba">Изменить</a>';
		}
		if (get_user_class() >= UC_MODERATOR) {
			$token = ($CURUSER && !empty($CURUSER['hash4u'])) ? '&amp;hash4u=' . commenttable_h($CURUSER['hash4u']) : '';
			$actions[] = '<a href="/' . commenttable_h($redaktor) . '.php?action=delete&amp;cid=' . $commentid . $token . '" class="sba">Удалить</a>';
			if (!empty($row['editedby'])) {
				$actions[] = '<a href="/' . commenttable_h($redaktor) . '.php?action=vieworiginal&amp;cid=' . $commentid . '" class="sba">Оригинал</a>';
			}
			if (!empty($row['ip'])) {
				$actions[] = '<a href="/usersearch.php?ip=' . commenttable_h($row['ip']) . '" class="sba">IP</a>';
			}
		}

		$text = commenttable_format_text($row['text'] ?? '');
		if (!empty($row['editedby'])) {
			$text .= '<br><span class="small">Изменено: ' . commenttable_h($row['editedat'] ?? '') . ' пользователем ' . commenttable_h($row['editedbyname'] ?? $row['editedby']) . '</span>';
		}

		print '<div class="mn2 cmet_bx">' . $avatar_html;
		print '<div class="cmet_sbx"><dl class="mn"><dt>' . $flag . $user . '</dt><dd>' . commenttable_h($row['added'] ?? '') . (count($actions) ? ' | ' . implode(' | ', $actions) : '') . '</dd></dl>';
		print '<div class="tx" id="cm' . $commentid . '">' . $text . '</div></div><div class="clr"></div></div>';
	}
}

?>
