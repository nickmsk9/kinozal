<?php

if (!defined('IN_TRACKER')) {
	die('Прямой вызов запрещён.');
}

function torrenttable($res, $variant = 'index')
{
	global $pic_base_url, $CURUSER, $use_wait, $use_ttl, $ttl_days, $tracker_lang;

	$rows = array();

	if (!$res) {
		return $rows;
	}

	$is_logged = !empty($CURUSER) && is_array($CURUSER);
	$user_class = $is_logged && isset($CURUSER['class']) ? (int)$CURUSER['class'] : 0;
	$user_id = $is_logged && isset($CURUSER['id']) ? (int)$CURUSER['id'] : 0;
	$is_moderator = (function_exists('get_user_class') && get_user_class() >= UC_MODERATOR);

	$wait = 0;

	if (!empty($use_wait) && $is_logged && $user_class < UC_VIP) {
		$uploaded = isset($CURUSER['uploaded']) ? (float)$CURUSER['uploaded'] : 0;
		$downloaded = isset($CURUSER['downloaded']) ? (float)$CURUSER['downloaded'] : 0;

		$gigs = $uploaded / (1024 * 1024 * 1024);
		$ratio = ($downloaded > 0) ? ($uploaded / $downloaded) : 0;

		if ($ratio < 0.5 || $gigs < 5) {
			$wait = 48;
		} elseif ($ratio < 0.65 || $gigs < 6.5) {
			$wait = 24;
		} elseif ($ratio < 0.8 || $gigs < 8) {
			$wait = 12;
		} elseif ($ratio < 0.95 || $gigs < 9.5) {
			$wait = 6;
		}
	}

	$script = 'browse.php';

	if ($variant === 'mytorrents') {
		$script = 'mytorrents.php';
	} elseif ($variant === 'bookmarks') {
		$script = 'bookmarks.php';
	}

	$get = $_GET;
	unset($get['sort'], $get['type']);

	$oldlink = http_build_query($get, '', '&amp;');
	if ($oldlink !== '') {
		$oldlink .= '&amp;';
	}

	$current_sort = isset($_GET['sort']) ? (int)$_GET['sort'] : 0;
	$current_type = isset($_GET['type']) && $_GET['type'] === 'desc' ? 'desc' : 'asc';

	$sort_types = array(
		1 => 'asc',
		2 => 'desc',
		3 => 'desc',
		4 => 'desc',
		5 => 'desc',
		7 => 'desc',
		8 => 'desc',
		9 => 'desc',
		10 => 'desc'
	);

	foreach ($sort_types as $sort_id => $default_type) {
		if ($current_sort === $sort_id) {
			$sort_types[$sort_id] = ($current_type === 'desc') ? 'asc' : 'desc';
		}
	}

	$e = function ($value) {
		return htmlspecialchars_uni((string)$value);
	};

	$lang = function ($key, $default = '') use ($tracker_lang) {
		return isset($tracker_lang[$key]) ? $tracker_lang[$key] : $default;
	};

	$sort_link = function ($sort, $title) use ($script, $oldlink, $sort_types, $e) {
		$type = isset($sort_types[$sort]) ? $sort_types[$sort] : 'desc';

		return '<a href="' . $e($script . '?' . $oldlink . 'sort=' . $sort . '&type=' . $type) . '" class="altlink_white">' . $e($title) . '</a>';
	};

	$cols = 7;

	if ($wait > 0) {
		$cols++;
	}

	if ($variant === 'mytorrents') {
		$cols++;
	}

	if (!empty($use_ttl)) {
		$cols++;
	}

	if ($variant === 'index' || $variant === 'bookmarks') {
		$cols++;
	}

	if ($is_moderator && $variant === 'index') {
		$cols += 2;
	}

	if ($variant === 'bookmarks') {
		$cols++;
	}

	if ($is_moderator && $variant === 'index') {
		print('<form method="post" action="deltorrent.php?mode=delete">' . "\n");
	} elseif ($variant === 'bookmarks') {
		print('<form method="post" action="takedelbookmark.php">' . "\n");
	}

	print("<tr>\n");
	print('<td class="colhead center">' . $e($lang('type', 'Тип')) . "</td>\n");
	print('<td class="colhead left">' . $sort_link(1, $lang('name', 'Название')) . ' / ' . $sort_link(4, $lang('added', 'Добавлено')) . "</td>\n");

	if ($wait > 0) {
		print('<td class="colhead center">' . $e($lang('wait', 'Ожидание')) . "</td>\n");
	}

	if ($variant === 'mytorrents') {
		print('<td class="colhead center">' . $e($lang('visible', 'Видим')) . "</td>\n");
	}

	print('<td class="colhead center">' . $sort_link(2, $lang('files', 'Файлы')) . "</td>\n");
	print('<td class="colhead center">' . $sort_link(3, $lang('comments', 'Комментарии')) . "</td>\n");

	if (!empty($use_ttl)) {
		print('<td class="colhead center">' . $e($lang('ttl', 'TTL')) . "</td>\n");
	}

	print('<td class="colhead center">' . $sort_link(5, $lang('size', 'Размер')) . "</td>\n");
	print('<td class="colhead center">' . $sort_link(7, $lang('seeds', 'Сиды')) . ' | ' . $sort_link(8, $lang('leechers', 'Личи')) . "</td>\n");

	if ($variant === 'index' || $variant === 'bookmarks') {
		print('<td class="colhead center">' . $sort_link(9, $lang('uploadeder', 'Залил')) . "</td>\n");
	}

	if ($is_moderator && $variant === 'index') {
		print('<td class="colhead center">' . $sort_link(10, 'Проверен') . "</td>\n");
		print('<td class="colhead center">' . $e($lang('delete', 'Удалить')) . "</td>\n");
	}

	if ($variant === 'bookmarks') {
		print('<td class="colhead center">' . $e($lang('delete', 'Удалить')) . "</td>\n");
	}

	print("</tr>\n");
	print("<tbody id=\"highlighted\">\n");

	while ($row = mysqli_fetch_assoc($res)) {
		$rows[] = $row;

		$id = isset($row['id']) ? (int)$row['id'] : 0;
		if ($id <= 0) {
			continue;
		}

		$is_sticky = isset($row['not_sticky']) && $row['not_sticky'] === 'no';
		$row_class = $is_sticky ? ' class="highlight"' : '';

		print('<tr' . $row_class . ">\n");

		print('<td class="center" style="padding:0;">');

		if (!empty($row['cat_name'])) {
			$cat_id = isset($row['category']) ? (int)$row['category'] : 0;
			print('<a href="browse.php?cat=' . $cat_id . '">');

			if (!empty($row['cat_pic'])) {
				print('<img src="' . $e($pic_base_url . '/cat/' . $row['cat_pic']) . '" alt="' . $e($row['cat_name']) . '" />');
			} else {
				print($e($row['cat_name']));
			}

			print('</a>');
		} else {
			print('-');
		}

		print("</td>\n");

		$dispname = isset($row['name']) ? $row['name'] : '';
		$freepic = '';

		if (isset($row['free']) && $row['free'] === 'yes') {
			$freepic = '<img src="' . $e($pic_base_url . '/freedownload.gif') . '" title="' . $e($lang('golden', 'Золотая раздача')) . '" alt="' . $e($lang('golden', 'Золотая раздача')) . '" />';
		} elseif (isset($row['free']) && $row['free'] === 'silver') {
			$freepic = '<img src="' . $e($pic_base_url . '/silverdownload.gif') . '" title="' . $e($lang('silver', 'Серебряная раздача')) . '" alt="' . $e($lang('silver', 'Серебряная раздача')) . '" />';
		}

		print('<td class="left">');

		if ($is_sticky) {
			print('<b>Важный:</b> ');
		}

		$details_url = 'details.php?';

		if ($variant === 'mytorrents') {
			$return_to = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
			$details_url .= 'returnto=' . urlencode($return_to) . '&amp;';
		}

		$details_url .= 'id=' . $id;

		if ($variant === 'index' || $variant === 'bookmarks') {
			$details_url .= '&amp;hit=1';
		}

		print('<a href="' . $details_url . '"><b>' . $e($dispname) . '</b></a> ' . $freepic . "\n");

		if ($variant !== 'bookmarks' && $is_logged) {
			print('<a href="bookmark.php?torrent=' . $id . '"><img src="' . $e($pic_base_url . '/bookmark.gif') . '" alt="' . $e($lang('bookmark_this', 'Добавить в закладки')) . '" title="' . $e($lang('bookmark_this', 'Добавить в закладки')) . '" /></a>' . "\n");
		}

		print('<a href="download.php?id=' . $id . '"><img src="' . $e($pic_base_url . '/download.gif') . '" alt="' . $e($lang('download', 'Скачать')) . '" title="' . $e($lang('download', 'Скачать')) . '" /></a>' . "\n");

		if (isset($row['multitracker']) && $row['multitracker'] === 'yes' && function_exists('magnet')) {
			$hash = isset($row['info_hash']) ? $row['info_hash'] : '';
			$filename = isset($row['filename']) ? $row['filename'] : '';
			$size = isset($row['size']) ? (int)$row['size'] : 0;

			print('<a href="' . $e(magnet(true, $hash, $filename, $size)) . '"><img src="' . $e($pic_base_url . '/magnet.png') . '" alt="' . $e($lang('magnet', 'Magnet')) . '" title="' . $e($lang('magnet', 'Magnet')) . '" /></a>' . "\n");

			$last_update = !empty($row['last_mt_update']) ? strtotime($row['last_mt_update']) : 0;
			$allow_update = $last_update < (TIMENOW - 3600);
			$suffix = $allow_update ? '_update' : '';
			$external_key = 'external_torrent' . $suffix;
			$external_title = $lang($external_key, $allow_update ? 'Обновить внешний торрент' : 'Внешний торрент');

			$multi_image = '<img src="' . $e($pic_base_url . '/multitracker.png') . '" alt="' . $e($external_title) . '" title="' . $e($external_title) . '" />';

			if ($allow_update) {
				$multi_image = '<a href="update_multi.php?id=' . $id . '">' . $multi_image . '</a>';
			}

			print($multi_image . "\n");
		}

		$owner_id = isset($row['owner']) ? (int)$row['owner'] : 0;
		$owned = ($is_logged && $user_id === $owner_id) || $is_moderator;

		if ($owned) {
			print('<a href="edit.php?id=' . $id . '"><img src="' . $e($pic_base_url . '/pen.gif') . '" alt="' . $e($lang('edit', 'Редактировать')) . '" title="' . $e($lang('edit', 'Редактировать')) . '" /></a>' . "\n");
		}

		if (isset($row['readtorrent']) && (int)$row['readtorrent'] === 0 && $variant === 'index') {
			print(' <span class="red small"><b>[новый]</b></span>');
		}

		if (!empty($row['added'])) {
			print('<br /><i>' . $e($row['added']) . '</i>');
		}

		print("</td>\n");

		if ($wait > 0) {
			$added_time = !empty($row['added']) ? strtotime($row['added']) : 0;
			$elapsed = $added_time > 0 ? floor((gmtime() - $added_time) / 3600) : $wait;

			if ($elapsed < $wait) {
				$left = max(0, $wait - $elapsed);
				print('<td class="center nowrap"><a href="faq.php#dl8"><span class="red"><b>' . number_format($left) . ' ч.</b></span></a></td>' . "\n");
			} else {
				print('<td class="center nowrap">' . $e($lang('no', 'Нет')) . "</td>\n");
			}
		}

		if ($variant === 'mytorrents') {
			print('<td class="center">');

			if (isset($row['visible']) && $row['visible'] === 'no') {
				print('<span class="red"><b>' . $e($lang('no', 'Нет')) . '</b></span>');
			} else {
				print('<span class="green">' . $e($lang('yes', 'Да')) . '</span>');
			}

			print("</td>\n");
		}

		$numfiles = isset($row['numfiles']) ? (int)$row['numfiles'] : 0;
		$type = isset($row['type']) ? $row['type'] : '';

		if ($type === 'single') {
			print('<td class="right">' . $numfiles . "</td>\n");
		} else {
			if ($variant === 'index') {
				print('<td class="right"><b><a href="details.php?id=' . $id . '&amp;hit=1&amp;filelist=1">' . $numfiles . "</a></b></td>\n");
			} else {
				print('<td class="right"><b><a href="details.php?id=' . $id . '&amp;filelist=1#filelist">' . $numfiles . "</a></b></td>\n");
			}
		}

		$comments = isset($row['comments']) ? (int)$row['comments'] : 0;

		if ($comments <= 0) {
			print('<td class="right">0</td>' . "\n");
		} else {
			if ($variant === 'index') {
				print('<td class="right"><b><a href="details.php?id=' . $id . '&amp;hit=1&amp;tocomm=1">' . $comments . "</a></b></td>\n");
			} else {
				print('<td class="right"><b><a href="details.php?id=' . $id . '&amp;page=0#startcomments">' . $comments . "</a></b></td>\n");
			}
		}

		if (!empty($use_ttl)) {
			$added_unix = !empty($row['added']) && function_exists('sql_timestamp_to_unix_timestamp')
				? sql_timestamp_to_unix_timestamp($row['added'])
				: (!empty($row['added']) ? strtotime($row['added']) : 0);

			$ttl = ((int)$ttl_days * 24) - floor((gmtime() - (int)$added_unix) / 3600);
			$ttl = max(0, (int)$ttl);

			$ttl_text = $ttl . ' ' . ($ttl === 1 ? 'час' : 'часов');

			print('<td class="center nowrap">' . $ttl_text . "</td>\n");
		}

		$size = isset($row['size']) ? (float)$row['size'] : 0;
		print('<td class="center nowrap">' . str_replace(' ', '<br />', mksize($size)) . "</td>\n");

		$seeders = isset($row['seeders']) ? (int)$row['seeders'] : 0;
		$leechers = isset($row['leechers']) ? (int)$row['leechers'] : 0;

		print('<td class="center nowrap">');

		if ($seeders > 0) {
			if ($variant === 'index') {
				$ratio = $leechers > 0 ? ($seeders / $leechers) : 1;
				$color = function_exists('get_slr_color') ? get_slr_color($ratio) : 'green';

				print('<b><a href="details.php?id=' . $id . '&amp;hit=1&amp;toseeders=1"><font color="' . $e($color) . '">' . $seeders . '</font></a></b>');
			} else {
				$link_class = function_exists('linkcolor') ? linkcolor($seeders) : 'green';
				print('<b><a class="' . $e($link_class) . '" href="details.php?id=' . $id . '&amp;dllist=1#seeders">' . $seeders . '</a></b>');
			}
		} else {
			$link_class = function_exists('linkcolor') ? linkcolor(0) : 'red';
			print('<span class="' . $e($link_class) . '">0</span>');
		}

		print(' | ');

		if ($leechers > 0) {
			if ($variant === 'index') {
				print('<b><a href="details.php?id=' . $id . '&amp;hit=1&amp;todlers=1">' . number_format($leechers) . '</a></b>');
			} else {
				$link_class = function_exists('linkcolor') ? linkcolor($leechers) : 'red';
				print('<b><a class="' . $e($link_class) . '" href="details.php?id=' . $id . '&amp;dllist=1#leechers">' . $leechers . '</a></b>');
			}
		} else {
			print('0');
		}

		print("</td>\n");

		if ($variant === 'index' || $variant === 'bookmarks') {
			print('<td class="center">');

			if (!empty($row['username'])) {
				$owner_class = isset($row['class']) ? (int)$row['class'] : 0;
				$username = htmlspecialchars_uni($row['username']);

				print('<a href="userdetails.php?id=' . $owner_id . '"><b>' . get_user_class_color($owner_class, $username) . '</b></a>');
			} else {
				print('<i>неизвестно</i>');
			}

			print("</td>\n");
		}

		if ($is_moderator && $variant === 'index') {
			print('<td class="center">');

			if (isset($row['moderated']) && $row['moderated'] === 'no') {
				print('<span class="red"><b>Нет</b></span>');
			} else {
				$moderatedby = isset($row['moderatedby']) ? (int)$row['moderatedby'] : 0;

				if ($moderatedby > 0) {
					print('<a href="userdetails.php?id=' . $moderatedby . '"><span class="green"><b>Да</b></span></a>');
				} else {
					print('<span class="green"><b>Да</b></span>');
				}
			}

			print("</td>\n");
		}

		if ($is_moderator && $variant === 'index') {
			print('<td class="center"><input type="checkbox" name="delete[]" value="' . $id . '" /></td>' . "\n");
		}

		if ($variant === 'bookmarks') {
			$bookmark_id = isset($row['bookmarkid']) ? (int)$row['bookmarkid'] : 0;
			print('<td class="center"><input type="checkbox" name="delbookmark[]" value="' . $bookmark_id . '" /></td>' . "\n");
		}

		print("</tr>\n");
	}

	print("</tbody>\n");

	if ($variant === 'index' && $is_logged) {
		print('<tr><td class="colhead center" colspan="' . $cols . '"><a href="markread.php" class="altlink_white">Все торренты прочитаны</a></td></tr>' . "\n");
	}

	if ($variant === 'index' && $is_moderator) {
		print('<tr><td class="right" colspan="' . $cols . '"><input type="submit" class="buttonS" value="Удалить выбранные" /></td></tr>' . "\n");
	}

	if ($variant === 'bookmarks') {
		print('<tr><td class="right" colspan="' . $cols . '"><input type="submit" class="buttonS" value="' . $e($lang('delete', 'Удалить')) . '" /></td></tr>' . "\n");
	}

	if (($variant === 'index' && $is_moderator) || $variant === 'bookmarks') {
		print("</form>\n");
	}

	return $rows;
}

