<?php


define ('IN_ANNOUNCE', true);
require_once('./include/core_announce.php');

gzip();

foreach (array('passkey','info_hash','peer_id','event','ip','localip') as $x) {
	if(isset($_GET[$x]))
		$GLOBALS[$x] = '' . $_GET[$x];
}

foreach (array('port','downloaded','uploaded','left') as $x)
	$GLOBALS[$x] = intval($_GET[$x]);

if (get_magic_quotes_gpc()) {
    $info_hash = stripslashes($info_hash);
    $peer_id = stripslashes($peer_id);
}

foreach (array('passkey','info_hash','peer_id','port','downloaded','uploaded','left') as $x)
	if (!isset($GLOBALS[$x])) err('Missing key: '.$x);
		foreach (array('info_hash','peer_id') as $x)
			if (strlen($GLOBALS[$x]) != 20)
				err('Invalid '.$x.' (' . strlen($GLOBALS[$x]) . ' - ' . urlencode($GLOBALS[$x]) . ')');
			if (strlen($passkey) != 32)
				err('Invalid passkey (' . strlen($passkey) . ' - ' . $passkey . ')');
$ip = getip();
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

if(substr($peer_id, 0, 6) == "exbc\08") err("BitComet 0.56 is Banned, Upgrade.");
if(substr($peer_id, 0, 4) == "FUTB") err("FUTB? Fuck You Too."); //patched version of BitComet 0.57 (FUTB- Fuck U TorrentBits)
if(substr($peer_id, 1, 2) == 'BC' && substr($peer_id, 5, 2) != 70 && substr($peer_id, 5, 2) != 63 && substr($peer_id, 5, 2) != 77 && substr($peer_id, 5, 2) >= 59/* && substr($peer_id, 5, 2) <= 88*/) err("BitComet ".substr($peer_id, 5, 2)." is banned. Use only 0.70 or switch to uTorrent 1.6.1.");
if(substr($peer_id, 1, 2) == 'UT' && substr($peer_id, 3, 3) >= 170 && substr($peer_id, 3, 3) <= 174) err("uTorrent ".substr($peer_id, 3, 3)." is banned. Downgrade to 1.6.1 or use 1.7.5 or higher.");
if(substr($peer_id, 0, 4) == "FUTB") err("FUTB? Fuck You Too.");
if(substr($peer_id, 0, 3) == "-TS") err("TorrentStorm is Banned.");
if(substr($peer_id, 0, 5) == "Mbrst") err("Burst! is Banned.");
if(substr($peer_id, 0, 3) == "-BB") err("BitBuddy is Banned.");
if(substr($peer_id, 0, 3) == "-SZ") err("Shareaza is Banned.");
if(substr($peer_id, 0, 5) == "turbo") err("TurboBT is banned.");
if(substr($peer_id, 0, 4) == "T03A") err("Please Update your BitTornado.");
if(substr($peer_id, 0, 4) == "T03B") err("Please Update your BitTornado.");
if(substr($peer_id, 0, 3 ) == "FRS") err("Rufus is Banned.");
if(substr($peer_id, 0, 2 ) == "eX") err("eXeem is Banned.");
if(substr($peer_id, 0, 8 ) == "-TR0005-") err("Transmission/0.5 is Banned.");
if(substr($peer_id, 0, 8 ) == "-TR0006-") err("Transmission/0.6 is Banned.");
if(substr($peer_id, 0, 8 ) == "-XX0025-") err("Transmission/0.6 is Banned.");
if(substr($peer_id, 0, 1 ) == ",") err ("RAZA is banned.");
if(substr($peer_id, 0, 3 ) == "-AG") err("This is a banned client. We recommend uTorrent or Azureus.");
if(substr($peer_id, 0, 3 ) == "R34") err("BTuga/Revolution-3.4 is not an acceptalbe client.");
if(substr($peer_id, 0, 4) == "exbc") err("This version of BitComet is banned! You can thank DHT for this ban!");
if(substr($peer_id, 0, 3) == '-FG') err("FlashGet is banned!");

dbconn();
$user_res = mysql_query('SELECT id, uploaded, downloaded, enabled, parked, class, passkey_ip FROM users WHERE passkey = ' . sqlesc($passkey) . ' LIMIT 1') or err('Users error 1 (select)');
$az = mysqli_fetch_array($user_res);
if (!$az)
	err('Invalid passkey! Re-download the .torrent from '.$DEFAULTBASEURL);
$hash = bin2hex($info_hash);
// Announce works with local peers only; external tracker stats live outside `peers`.
$res = mysql_query('SELECT id, visible, banned, free, seeders + leechers AS numpeers, UNIX_TIMESTAMP(added) AS ts FROM torrents WHERE info_hash = "'.$hash.'"') or err('Torrents error 1 (select)');
$torrent = mysqli_fetch_array($res);
if (!$torrent)
	err('Torrent not registered with this tracker.');
$torrentid = $torrent['id'];
$fields = 'seeder, peer_id, ip, port, uploaded, downloaded, userid, passkey, last_action, UNIX_TIMESTAMP(NOW()) AS nowts, UNIX_TIMESTAMP(prev_action) AS prevts';
$numpeers = $torrent['numpeers'];
$selfwhere = 'torrent = '.$torrentid.' AND peer_id = '.sqlesc($peer_id).' AND passkey = '.sqlesc($passkey);
$selfexpr = 'peer_id = '.sqlesc($peer_id).' AND passkey = '.sqlesc($passkey);
if ($numpeers > $rsize) {
	$res = mysql_query('(SELECT '.$fields.', 1 AS is_self FROM peers WHERE '.$selfwhere.' LIMIT 1) UNION ALL (SELECT '.$fields.', 0 AS is_self FROM peers WHERE torrent = '.$torrentid.' AND NOT ('.$selfexpr.') ORDER BY RAND() LIMIT '.$rsize.')') or err('Peers error 1 (select)');
} else {
	$res = mysql_query('SELECT '.$fields.', IF('.$selfexpr.', 1, 0) AS is_self FROM peers WHERE torrent = '.$torrentid) or err('Peers error 1 (select)');
}
$compact = (isset($_GET['compact']) && $_GET['compact'] == 1);
$resp = 'd' . benc_str('interval') . 'i' . $announce_interval . 'e' . benc_str('peers') . ($compact ? '' : 'l');
$no_peer_id = (isset($_GET['no_peer_id']) && $_GET['no_peer_id'] == 1);
$plist = '';
unset($self);
while ($row = mysqli_fetch_array($res)) {
	if (!empty($row['is_self'])) {
		$userid = $row['userid'];
		$self = $row;
		continue;
	}
	if($compact) {
		$peer_ip = explode('.', $row["ip"]);
		$plist .= pack("C*", $peer_ip[0], $peer_ip[1], $peer_ip[2], $peer_ip[3]). pack("n*", (int) $row["port"]);
	} else {
		$resp .= 'd' .
			benc_str('ip') . benc_str($row['ip']) .
			(!$no_peer_id ? benc_str("peer id") . benc_str($row["peer_id"]) : '') .
			benc_str('port') . 'i' . $row['port'] . 'e' . 'e';
	}
}
$resp .= ($compact ? benc_str($plist) : '') . (substr($peer_id, 0, 4) == '-BC0' ? "e7:privatei1ee" : "ee");

$announce_wait = 10;
$dt = sqlesc(date('Y-m-d H:i:s', time()));
$updateset = array();
$snatch_updateset = array();

if (isset($self) && ($self['prevts'] > ($self['nowts'] - $announce_wait )) )
	err('There is a minimum announce time of ' . $announce_wait . ' seconds');
if (!isset($self)) {
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
        $sockres = check_port($ip, $port, 5);
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
} else {
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

if ($event == 'completed') {
    $snatch_updateset[] = "finished = 'yes'";
    $snatch_updateset[] = "completedat = $dt";
    $snatch_updateset[] = "seeder = 'yes'";
    $updateset[] = 'times_completed = times_completed + 1';
}

if ($seeder == 'yes') {
	if ($torrent['banned'] != 'yes' && $torrent['visible'] != 'yes')
		$updateset[] = 'visible = \'yes\'';
	$updateset[] = 'last_action = NOW()';
}
if (count($updateset))
	mysql_query('UPDATE LOW_PRIORITY torrents SET ' . join(", ", $updateset) . ' WHERE id = '.$torrentid) or err('Torrents error 2 (update)');

if (count($snatch_updateset))
	mysql_query('UPDATE LOW_PRIORITY snatched SET ' . join(", ", $snatch_updateset) . ' WHERE torrent = '.$torrentid.' AND userid = '.$userid) or err('Snatched error 2 (update)');

benc_resp_raw($resp);

?>
