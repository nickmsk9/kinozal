<?

/*
// +--------------------------------------------------------------------------+
// | Project:    TBDevYSE - TBDev Yuna Scatari Edition                        |
// +--------------------------------------------------------------------------+
// | This file is part of TBDevYSE. TBDevYSE is based on TBDev,               |
// | originally by RedBeard of TorrentBits, extensively modified by           |
// | Gartenzwerg.                                                             |
// |                                                                          |
// | TBDevYSE is free software; you can redistribute it and/or modify         |
// | it under the terms of the GNU General Public License as published by     |
// | the Free Software Foundation; either version 2 of the License, or        |
// | (at your option) any later version.                                      |
// |                                                                          |
// | TBDevYSE is distributed in the hope that it will be useful,              |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
// | GNU General Public License for more details.                             |
// |                                                                          |
// | You should have received a copy of the GNU General Public License        |
// | along with TBDevYSE; if not, write to the Free Software Foundation,      |
// | Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA            |
// +--------------------------------------------------------------------------+
// |                                               Do not remove above lines! |
// +--------------------------------------------------------------------------+
*/

require_once "include/bittorrent.php";

dbconn();
loggedinorreturn();

if (get_user_class() < UC_ADMINISTRATOR) {
    stderr($tracker_lang['error'], $tracker_lang['access_denied']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameRaw = trim((string)($_POST['username'] ?? ''));
    $password    = (string)($_POST['password'] ?? '');
    $password2   = (string)($_POST['password2'] ?? '');
    $emailRaw    = trim((string)($_POST['email'] ?? ''));

    if ($usernameRaw === '' || $password === '' || $emailRaw === '') {
        stderr($tracker_lang['error'], $tracker_lang['missing_form_data']);
    }

    if ($password !== $password2) {
        stderr($tracker_lang['error'], $tracker_lang['password_mismatch']);
    }

    if (!filter_var($emailRaw, FILTER_VALIDATE_EMAIL)) {
        stderr($tracker_lang['error'], 'Некорректный E-mail адрес.');
    }

    $username = sqlesc(htmlspecialchars_uni($usernameRaw));
    $email    = sqlesc(htmlspecialchars_uni($emailRaw));

    $secretRaw = mksecret();
    $secret    = sqlesc($secretRaw);
    $passhash  = sqlesc(md5($secretRaw . $password . $secretRaw));

    $added = sqlesc(get_date_time());

    sql_query("
        INSERT INTO users 
            (added, last_access, secret, username, passhash, status, email) 
        VALUES
            ($added, $added, $secret, $username, $passhash, 'confirmed', $email)
    ") or sqlerr(__FILE__, __LINE__);

    global $link;

    $userId = mysqli_insert_id($link);

    if (!$userId) {
        stderr($tracker_lang['error'], $tracker_lang['unable_to_create_account']);
    }

    header('Location: ' . $DEFAULTBASEURL . '/userdetails.php?id=' . (int)$userId);
    exit;
}

stdhead($tracker_lang['add_user']);
?>

<div class="mn_wrap">
    <div class="tp1_title">
        <b><?=$tracker_lang['add_user'];?></b>
    </div>

    <div class="tp1_body">
        <form method="post" action="adduser.php">
            <table class="tables2 w100p">
                <tr>
                    <td class="rowhead w150"><?=$tracker_lang['username'];?></td>
                    <td>
                        <input type="text" name="username" class="w300" />
                    </td>
                </tr>

                <tr>
                    <td class="rowhead w150"><?=$tracker_lang['password'];?></td>
                    <td>
                        <input type="password" name="password" class="w300" />
                    </td>
                </tr>

                <tr>
                    <td class="rowhead w150"><?=$tracker_lang['repeat_password'];?></td>
                    <td>
                        <input type="password" name="password2" class="w300" />
                    </td>
                </tr>

                <tr>
                    <td class="rowhead w150">E-mail</td>
                    <td>
                        <input type="text" name="email" class="w300" />
                    </td>
                </tr>

                <tr>
                    <td colspan="2" class="center">
                        <input type="submit" value="OK" class="buttonS" />
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>

<?php

stdfoot();

?>
