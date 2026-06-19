<?php


define ('IN_ANNOUNCE', true);
require_once('./include/core_announce.php');

gzip();

foreach (array('passkey','info_hash','peer_id','event','ip','ipv6','localip') as $x) {
	if(isset($_GET[$x]))
		$GLOBALS[$x] = '' . $_GET[$x];
}

foreach (array('port','downloaded','uploaded','left') as $x) {
	if (isset($_GET[$x])) {
		$GLOBALS[$x] = intval($_GET[$x]);
	}
}

if (get_magic_quotes_gpc()) {
	if (isset($info_hash)) {
		$info_hash = stripslashes($info_hash);
	}
	if (isset($peer_id)) {
		$peer_id = stripslashes($peer_id);
	}
}

foreach (array('passkey','info_hash','peer_id','port','downloaded','uploaded','left') as $x) {
	if (!isset($GLOBALS[$x])) {
		err('Missing key: '.$x);
	}
}
foreach (array('info_hash','peer_id') as $x) {
	if (strlen($GLOBALS[$x]) != 20) {
		err('Invalid '.$x.' (' . strlen($GLOBALS[$x]) . ' - ' . urlencode($GLOBALS[$x]) . ')');
	}
}
if (!tracker_valid_passkey($passkey)) {
	err('Invalid passkey (' . strlen($passkey) . ' - ' . $passkey . ')');
}
$ip = getip();
if (!empty($ipv6) && tracker_ip_version($ipv6) === 6) {
	$ip = $ipv6;
} elseif (!empty($ip) && tracker_ip_version($ip) === 0) {
	$ip = getip();
}
if (tracker_ip_version($ip) === 0)
	err('Invalid IP');
$rsize = 50;

foreach(array('num want', 'numwant', 'num_want') as $k) {
	if (isset($_GET[$k]))
	{
		$rsize = (int) $_GET[$k];
		break;
	}
}
$rsize = max(0, min(200, $rsize));

$agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

if (!$port || $port > 0xffff)
	err("Invalid port");
if (!isset($event))
	$event = '';
$seeder = ($left == 0) ? 'yes' : 'no';

if (function_exists('getallheaders'))
	$headers = getallheaders();
else
	$headers = emu_getallheaders();
if (isset($headers['Cookie']) || isset($headers['Accept-Language']) || isset($headers['Accept-Charset']))
	err('Anti-Cheater: You cannot use this agent');

dbconn();
$user_res = mysql_query('SELECT id, uploaded, downloaded, enabled, parked, class, passkey_ip FROM users WHERE passkey = ' . sqlesc($passkey) . ' LIMIT 1') or err('Users error 1 (select)');
$az = mysqli_fetch_array($user_res);
if (!$az)
	err('Invalid passkey! Re-download the .torrent from '.$DEFAULTBASEURL);
$hash = bin2hex($info_hash);
// Announce works with local peers only; external tracker stats live outside `peers`.
$res = mysql_query('SELECT id, visible, banned, free, seeders, leechers, times_completed, seeders + leechers AS numpeers, UNIX_TIMESTAMP(added) AS ts FROM torrents WHERE info_hash = "'.$hash.'"') or err('Torrents error 1 (select)');
$torrent = mysqli_fetch_array($res);
if (!$torrent)
	err('Torrent not registered with this tracker.');
$torrentid = (int)$torrent['id'];
$numpeers = (int)$torrent['numpeers'];
$selfwhere = 'torrent = '.$torrentid.' AND peer_id = '.sqlesc($peer_id).' AND passkey = '.sqlesc($passkey);
$selfexpr = 'peer_id = '.sqlesc($peer_id).' AND passkey = '.sqlesc($passkey);

$announce_wait = 10;
$self = null;
$self_fields = 'seeder, peer_id, ip, port, uploaded, downloaded, userid, passkey, last_action, UNIX_TIMESTAMP(NOW()) AS nowts, UNIX_TIMESTAMP(prev_action) AS prevts';
$self_res = mysql_query('SELECT '.$self_fields.' FROM peers WHERE '.$selfwhere.' LIMIT 1') or err('Peers error 1 (select)');
if ($self_res && ($self_row = mysqli_fetch_array($self_res))) {
	$self = $self_row;
	$userid = (int)$self['userid'];
}
if (isset($self) && ((int)$self['prevts'] > ((int)$self['nowts'] - $announce_wait)))
	err('There is a minimum announce time of ' . $announce_wait . ' seconds');

$dt = sqlesc(date('Y-m-d H:i:s', time()));
$updateset = array();
$snatch_updateset = array();
$stopped_without_self = (!isset($self) && $event == 'stopped');
if (!isset($self) && !$stopped_without_self) {
	if ($az['enabled'] == 'no')
		err('This account is disabled.');
	$userid = $az['id'];
	if ($az['class'] < UC_VIP) {
		if ($use_wait) {
			$gigs = $az['uploaded'] / (1024*1024*1024);
			$elapsed = floor((strtotime(date('Y-m-d H:i:s')) - $torrent['ts']) / 3600);
			$ratio = (($az['downloaded'] > 0) ? ($az['uploaded'] / $az['downloaded']) : 1);
			if ($ratio < 0.5 || $gigs < 5)
				$wait = 48;
			elseif ($ratio < 0.65 || $gigs < 6.5)
				$wait = 24;
			elseif ($ratio < 0.8 || $gigs < 8)
				$wait = 12;
			elseif ($ratio < 0.95 || $gigs < 9.5)
				$wait = 6;
			else
				$wait = 0;
			if ($elapsed < $wait)
				err('Not authorized (' . ($wait - $elapsed) . 'h)');
		}
	}
	$passkey_ip = $az['passkey_ip'];
	if ($passkey_ip != '' && getip() != $passkey_ip)
		err('Unauthorized IP for this passkey!');
    if ($az['parked'] == 'yes')
        err('Error, your account is parked!');
    if (portblacklisted($port))
        err('Port '.$port.' is blacklisted.');
    else {
        $connect_timeout = ($nc == 'yes') ? 3 : 1;
        $sockres = check_port($ip, $port, $connect_timeout);
        if (!$sockres) {
            $connectable = 'no';
            if ($nc == 'yes')
                err('Your client is not connectable! Check your Port-configuration or search on forums.');
        }else {
            $connectable = 'yes';
            @fclose($sockres);
        }
    }

    mysql_query("INSERT LOW_PRIORITY INTO snatched (torrent, userid, port, startdat, last_action, connectable) SELECT $torrentid, $userid, $port, $dt, $dt, '$connectable' WHERE NOT EXISTS (SELECT 1 FROM snatched WHERE torrent = $torrentid AND userid = $userid LIMIT 1)") or err('Snatched error 1 (insert)');
    $ret = mysql_query("INSERT LOW_PRIORITY INTO peers (connectable, torrent, peer_id, ip, port, uploaded, downloaded, to_go, started, last_action, seeder, userid, agent, uploadoffset, downloadoffset, passkey) VALUES ('$connectable', $torrentid, " . sqlesc($peer_id) . ", " . sqlesc($ip) . ", $port, $uploaded, $downloaded, $left, NOW(), NOW(), '$seeder', $userid, " . sqlesc($agent) . ", $uploaded, $downloaded, " . sqlesc($passkey) . ")") or err('Peers error 4 (insert)');
    if ($ret && mysql_affected_rows()) {
        if ($seeder == 'yes')
            $updateset[] = 'seeders = seeders + 1';
        else
            $updateset[] = 'leechers = leechers + 1';
    }
} elseif (isset($self)) {
	$upthis = max(0, $uploaded - $self['uploaded']);
	$downthis = max(0, $downloaded - $self['downloaded']);
	if ($upthis > 0 || $downthis > 0)
		mysql_query('UPDATE LOW_PRIORITY users SET uploaded = uploaded + '.$upthis.', downloaded = downloaded + '.$downthis.' WHERE id='.$userid) or err('Users error 2 (update)');
    $downloaded2 = max(0, $downloaded - $self['downloaded']);
    $uploaded2 = max(0, $uploaded - $self['uploaded']);
    if ($downloaded2 > 0 || $uploaded2 > 0) {
        $snatch_updateset[] = "uploaded = uploaded + $uploaded2";
        $snatch_updateset[] = "downloaded = downloaded + $downloaded2";
        $snatch_updateset[] = "to_go = $left";
    }
    $snatch_updateset[] = "port = $port";
    $snatch_updateset[] = "last_action = $dt";
    $prev_action = $self['last_action'];

    if ($event == 'stopped') {
        mysql_query('DELETE FROM peers WHERE '.$selfwhere);
        if (mysql_affected_rows()) {
            if ($self['seeder'] == 'yes')
                $updateset[] = 'seeders = IF(seeders > 0, seeders - 1, 0)';
            else
                $updateset[] = 'leechers = IF(leechers > 0, leechers - 1, 0)';
        }
        $snatch_updateset[] = "seeder = 'no'";
        $snatch_updateset[] = "connectable = 'no'";
    } else {
        $snatch_updateset[] = "seeder = '$seeder'";
        mysql_query("UPDATE LOW_PRIORITY peers SET uploaded = $uploaded, downloaded = $downloaded, uploadoffset = $uploaded2, downloadoffset = $downloaded2, to_go = $left, last_action = NOW(), prev_action = ".sqlesc($prev_action).", seeder = '$seeder'"
            . ($seeder == "yes" && $self["seeder"] != $seeder ? ", finishedat = " . time() : "") . ", agent = ".sqlesc($agent)." WHERE $selfwhere") or err('Peers error 3 (update)');
        if (mysql_affected_rows() && $self['seeder'] != $seeder) {
            if ($seeder == 'yes') {
                $updateset[] = 'seeders = seeders + 1';
                $updateset[] = 'leechers = IF(leechers > 0, leechers - 1, 0)';
            } else {
                $updateset[] = 'leechers = leechers + 1';
                $updateset[] = 'seeders = IF(seeders > 0, seeders - 1, 0)';
            }
        }
    }

}

if (!$stopped_without_self && $event == 'completed') {
    $snatch_updateset[] = "finished = 'yes'";
    $snatch_updateset[] = "completedat = $dt";
    $snatch_updateset[] = "seeder = 'yes'";
    $updateset[] = 'times_completed = times_completed + 1';
}

if (!$stopped_without_self && $seeder == 'yes') {
	if ($torrent['banned'] != 'yes' && $torrent['visible'] != 'yes')
		$updateset[] = 'visible = \'yes\'';
	$updateset[] = 'last_action = NOW()';
}
if (count($updateset))
	mysql_query('UPDATE LOW_PRIORITY torrents SET ' . join(", ", $updateset) . ' WHERE id = '.$torrentid) or err('Torrents error 2 (update)');

if (count($snatch_updateset))
	mysql_query('UPDATE LOW_PRIORITY snatched SET ' . join(", ", $snatch_updateset) . ' WHERE torrent = '.$torrentid.' AND userid = '.$userid) or err('Snatched error 2 (update)');

$compact = (isset($_GET['compact']) && $_GET['compact'] == 1);
$resp = 'd'
	. benc_str('interval') . 'i' . $announce_interval . 'e'
	. benc_str('min interval') . 'i' . max(60, (int)($announce_interval / 2)) . 'e'
	. benc_str('complete') . 'i' . (int)$torrent['seeders'] . 'e'
	. benc_str('incomplete') . 'i' . (int)$torrent['leechers'] . 'e'
	. benc_str('downloaded') . 'i' . (int)$torrent['times_completed'] . 'e'
	. benc_str('private') . 'i1e';
$no_peer_id = (isset($_GET['no_peer_id']) && $_GET['no_peer_id'] == 1);
$plist4 = '';
$plist6 = '';
$peerlist = '';
if ($event != 'stopped' && $rsize > 0) {
	$peer_fields = 'peer_id, ip, port';
	if ($numpeers > $rsize) {
		$peer_offset_max = max(0, $numpeers - $rsize - 1);
		$peer_offset = $peer_offset_max > 0 ? mt_rand(0, $peer_offset_max) : 0;
		$res = mysql_query('SELECT '.$peer_fields.' FROM peers WHERE torrent = '.$torrentid.' AND NOT ('.$selfexpr.') ORDER BY id ASC LIMIT '.$peer_offset.', '.$rsize) or err('Peers error 5 (select)');
	} else {
		$res = mysql_query('SELECT '.$peer_fields.' FROM peers WHERE torrent = '.$torrentid.' AND NOT ('.$selfexpr.')') or err('Peers error 5 (select)');
	}
	while ($row = mysqli_fetch_array($res)) {
		if ($compact) {
			$packed_ip = @inet_pton($row['ip']);
			if ($packed_ip === false) {
				continue;
			}
			if (strlen($packed_ip) === 4) {
				$plist4 .= $packed_ip . pack('n', (int)$row['port']);
			} elseif (strlen($packed_ip) === 16) {
				$plist6 .= $packed_ip . pack('n', (int)$row['port']);
			}
		} else {
			$peerlist .= 'd' .
				benc_str('ip') . benc_str($row['ip']) .
				(!$no_peer_id ? benc_str('peer id') . benc_str($row['peer_id']) : '') .
				benc_str('port') . 'i' . (int)$row['port'] . 'e' . 'e';
		}
	}
}
if ($compact) {
	$resp .= benc_str('peers') . benc_str($plist4);
	if ($plist6 !== '') {
		$resp .= benc_str('peers6') . benc_str($plist6);
	}
} else {
	$resp .= benc_str('peers') . 'l' . $peerlist . 'e';
}
$resp .= 'e';

benc_resp_raw($resp);

?>
