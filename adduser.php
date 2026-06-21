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

require_once __DIR__ . '/include/bittorrent.php';

dbconn();
loggedinorreturn();

if (get_user_class() < UC_ADMINISTRATOR) {
    stderr($tracker_lang['error'], $tracker_lang['access_denied']);
}

$usernameRaw = '';
$emailRaw = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameRaw = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $password2 = (string)($_POST['password2'] ?? '');
    $emailRaw = trim((string)($_POST['email'] ?? ''));

    if ($usernameRaw === '' || $password === '' || $emailRaw === '') {
        stderr($tracker_lang['error'], 'Заполните логин, пароль и E-mail.');
    }

    if ($password !== $password2) {
        stderr($tracker_lang['error'], 'Пароли не совпадают.');
    }

    if (!filter_var($emailRaw, FILTER_VALIDATE_EMAIL)) {
        stderr($tracker_lang['error'], 'Некорректный E-mail адрес.');
    }

    $username = sqlesc($usernameRaw);
    $email = sqlesc($emailRaw);

    $dupeRes = sql_query("
        SELECT id
        FROM users
        WHERE username = $username OR email = $email
        LIMIT 1
    ") or sqlerr(__FILE__, __LINE__);

    if (mysqli_num_rows($dupeRes) > 0) {
        stderr($tracker_lang['error'], 'Пользователь с таким логином или E-mail уже существует.');
    }

    $secretRaw = mksecret();
    $secret = sqlesc($secretRaw);
    $passhash = sqlesc(tracker_password_hash($password));
    $added = sqlesc(get_date_time());

    try {
        sql_query("
            INSERT INTO users
                (added, last_access, secret, username, passhash, status, email, simpaty)
            VALUES
                ($added, $added, $secret, $username, $passhash, 'confirmed', $email, 0)
        ");
    } catch (mysqli_sql_exception $e) {
        if ((int)$e->getCode() === 1062) {
            stderr($tracker_lang['error'], 'Пользователь с таким логином или E-mail уже существует.');
        }

        throw $e;
    }

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

<div style="width: 100%; text-align: center;">
    <div style="width: 560px; display: inline-block; text-align: left;">
        <div class="mn_wrap">
            <div class="tp1_title">
                <b><?=htmlspecialchars_uni($tracker_lang['add_user']);?></b>
            </div>

            <div class="tp1_body">
                <form method="post" action="/adduser.php" autocomplete="off">
                    <table class="tables1 w100p">
                        <tr>
                            <td class="w150 nw b">
                                <label for="adduser-username"><?=htmlspecialchars_uni($tracker_lang['username']);?></label>
                            </td>
                            <td>
                                <input
                                    type="text"
                                    id="adduser-username"
                                    name="username"
                                    class="w300"
                                    value="<?=htmlspecialchars_uni($usernameRaw);?>"
                                    minlength="1"
                                    required
                                    autofocus
                                >
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="small">Минимальная длина логина: 1 символ.</td>
                        </tr>

                        <tr>
                            <td class="w150 nw b">
                                <label for="adduser-password"><?=htmlspecialchars_uni($tracker_lang['password']);?></label>
                            </td>
                            <td>
                                <input
                                    type="password"
                                    id="adduser-password"
                                    name="password"
                                    class="w300"
                                    minlength="1"
                                    required
                                >
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="small">Минимальная длина пароля: 1 символ.</td>
                        </tr>

                        <tr>
                            <td class="w150 nw b">
                                <label for="adduser-password2"><?=htmlspecialchars_uni($tracker_lang['repeat_password']);?></label>
                            </td>
                            <td>
                                <input
                                    type="password"
                                    id="adduser-password2"
                                    name="password2"
                                    class="w300"
                                    minlength="1"
                                    required
                                >
                            </td>
                        </tr>

                        <tr>
                            <td class="w150 nw b">
                                <label for="adduser-email">E-mail</label>
                            </td>
                            <td>
                                <input
                                    type="text"
                                    id="adduser-email"
                                    name="email"
                                    class="w300"
                                    value="<?=htmlspecialchars_uni($emailRaw);?>"
                                    required
                                >
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2" class="right">
                                <input type="submit" value=" Добавить пользователя " class="buttonS">
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>
    </div>
</div>

<?
stdfoot();
?>
