<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/account_delete.php';

dbconn();
loggedinorreturn();

if (get_user_class() < UC_ADMINISTRATOR) {
    stderr($tracker_lang['error'], $tracker_lang['access_denied']);
}

function delacctadmin_h($value): string
{
    return htmlspecialchars_uni((string)$value);
}

$username = '';
$deletedUser = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $confirmed = (string)($_POST['confirm_delete'] ?? '') === 'yes';

    if ($username === '') {
        stderr($tracker_lang['error'], 'Введите имя пользователя.');
    }

    if (!$confirmed) {
        stderr($tracker_lang['error'], 'Подтвердите удаление аккаунта.');
    }

    $res = sql_query("
        SELECT id, username, class, enabled, email
        FROM users
        WHERE username = " . sqlesc($username) . "
        LIMIT 1
    ") or sqlerr(__FILE__, __LINE__);
    $user = mysqli_fetch_assoc($res);

    if (!$user) {
        stderr($tracker_lang['error'], 'Пользователь не найден.');
    }

    $userid = (int)$user['id'];
    $userClass = (int)$user['class'];
    $adminClass = (int)$CURUSER['class'];

    if ($userid === (int)$CURUSER['id']) {
        stderr($tracker_lang['error'], 'Свой аккаунт удаляйте через настройки профиля.');
    }

    if ($userClass >= $adminClass) {
        stderr($tracker_lang['error'], 'Нельзя удалить пользователя равного или более высокого класса.');
    }

    if ($userClass >= UC_SYSOP) {
        $sysopRes = sql_query("
            SELECT COUNT(*) AS sysop_count
            FROM users
            WHERE class >= " . UC_SYSOP . " AND enabled = 'yes'
        ") or sqlerr(__FILE__, __LINE__);
        $sysop = mysqli_fetch_assoc($sysopRes);

        if ((int)($sysop['sysop_count'] ?? 0) <= 1) {
            stderr($tracker_lang['error'], 'Нельзя удалить последнего системного администратора.');
        }
    }

    try {
        account_delete_user($userid);
    } catch (Throwable $e) {
        stderr($tracker_lang['error'], 'Не удалось полностью удалить аккаунт. Изменения отменены.');
    }

    $deletedUser = (string)$user['username'];
    write_log(
        'Пользователь ' . $deletedUser . ' удалён администратором ' . $CURUSER['username'] . '.'
    );
    $username = '';
}

$hide_right_blocks = true;
stdhead('Удаление аккаунта администратором');
?>

<div style="width: 100%; text-align: center;">
    <div style="width: 100%; max-width: 620px; display: inline-block; text-align: left;">
        <?php if ($deletedUser !== '') { ?>
            <div class="bx1_0">
                <div class="pad10x10 center green">
                    <b>Аккаунт <?=delacctadmin_h($deletedUser);?> полностью удалён.</b>
                </div>
            </div>
        <?php } ?>

        <div class="mn_wrap">
            <div class="tp1_title"><b>Удалить аккаунт пользователя</b></div>
            <div class="tp1_body">
                <div class="bx1_0 red">
                    <div class="pad10x10">
                        Действие необратимо. Личные данные будут удалены, публичные раздачи и комментарии останутся обезличенными.
                    </div>
                </div>

                <form method="post" action="/delacctadmin.php" autocomplete="off">
                    <table class="tables1 w100p">
                        <tr>
                            <td class="rowhead w150">
                                <label for="delacctadmin-username">Пользователь</label>
                            </td>
                            <td>
                                <input
                                    type="text"
                                    id="delacctadmin-username"
                                    name="username"
                                    class="w300"
                                    value="<?=delacctadmin_h($username);?>"
                                    required
                                    autofocus
                                >
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td>
                                <label class="red">
                                    <input type="checkbox" name="confirm_delete" value="yes" required>
                                    Подтверждаю безвозвратное удаление
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="right">
                                <input
                                    type="submit"
                                    class="buttonS"
                                    value=" Удалить пользователя "
                                    onclick="return confirm('Безвозвратно удалить этого пользователя?');"
                                >
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
stdfoot();
?>
