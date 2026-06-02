<?php

if (!defined('ADMIN_FILE')) {
    die('Illegal File Access');
}

if (!function_exists('iusers_h')) {
    function iusers_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

function iUsers(): void
{
    global $admin_file, $CURUSER;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $iname = trim((string)($_POST['iname'] ?? ''));
        $ipass = (string)($_POST['ipass'] ?? '');
        $imail = trim((string)($_POST['imail'] ?? ''));

        if ($iname === '') {
            stdmsg('Ошибка', 'Не указано имя пользователя.', 'error');
            return;
        }

        $res = sql_query("
            SELECT `class`
            FROM `users`
            WHERE `username` = " . sqlesc($iname) . "
            LIMIT 1
        ") or sqlerr(__FILE__, __LINE__);

        $user = mysqli_fetch_assoc($res);

        if (!$user) {
            stdmsg('Ошибка', 'Пользователь не найден.', 'error');
            return;
        }

        $iclass = (int)$user['class'];

        if (get_user_class() <= $iclass) {
            stdmsg(
                'Ошибка',
                'Смена пароля завершилась неудачей! Вы пробовали изменить учетные данные пользователя классом выше. Действие записано в логах.',
                'error'
            );

            write_log(
                'Администратор ' . $CURUSER['username'] . ' пробовал изменить учетные данные пользователя ' . $iname . ' классом выше!',
                'red',
                'error'
            );

            return;
        }

        $updateset = [];
        $newPasswordChanged = false;
        $newEmailChanged = false;

        if ($ipass !== '') {
            $secret = mksecret();
            $hash = md5($secret . $ipass . $secret);

            $updateset[] = '`secret` = ' . sqlesc($secret);
            $updateset[] = '`passhash` = ' . sqlesc($hash);
            $updateset[] = '`passkey` = ' . sqlesc(tracker_generate_passkey());

            $newPasswordChanged = true;
        }

        if ($imail !== '') {
            if (!validemail($imail)) {
                stdmsg('Ошибка', 'Указан некорректный email.', 'error');
                return;
            }

            $updateset[] = '`email` = ' . sqlesc($imail);
            $newEmailChanged = true;
        }

        if (empty($updateset)) {
            stdmsg('Ошибка', 'Не указаны данные для изменения.', 'error');
            return;
        }

        sql_query("
            UPDATE `users`
            SET " . implode(', ', $updateset) . "
            WHERE `username` = " . sqlesc($iname) . "
            LIMIT 1
        ") or sqlerr(__FILE__, __LINE__);

        /*
         * В PHP 8 mysql_modified_rows() больше нет.
         * mysqli_affected_rows() требует объект подключения.
         * В старых TBDev-сборках он часто лежит в глобальной переменной $___mysqli_ston.
         */
        global $___mysqli_ston;

        $affectedRows = 1;

        if (isset($___mysqli_ston) && $___mysqli_ston instanceof mysqli) {
            $affectedRows = mysqli_affected_rows($___mysqli_ston);
        }

        if ($affectedRows < 1) {
            stdmsg(
                'Ошибка',
                'Изменения не были применены. Возможно, новые данные совпадают со старыми.',
                'error'
            );

            return;
        }

        $message = 'Имя пользователя: ' . iusers_h($iname);

        if ($newPasswordChanged) {
            $message .= '<br>Новый пароль: ' . iusers_h($ipass);
        }

        if ($newEmailChanged) {
            $message .= '<br>Новая почта: ' . iusers_h($imail);
        }

        stdmsg('Изменения пользователя прошли успешно', $message);
        return;
    }

    echo '<form method="post" action="' . iusers_h($admin_file) . '.php?op=iUsers">';
    echo '<table border="0" cellspacing="0" cellpadding="3">';
    echo '<tr><td class="colhead" colspan="2">Смена пароля</td></tr>';

    echo '<tr>';
    echo '<td><b>Пользователь</b></td>';
    echo '<td><input name="iname" type="text"></td>';
    echo '</tr>';

    echo '<tr>';
    echo '<td><b>Новый пароль</b></td>';
    echo '<td><input name="ipass" type="password"></td>';
    echo '</tr>';

    echo '<tr>';
    echo '<td><b>Новая почта</b></td>';
    echo '<td><input name="imail" type="text"></td>';
    echo '</tr>';

    echo '<tr>';
    echo '<td colspan="2" align="center">';
    echo '<input type="submit" name="isub" value="Сделать" class="buttonS">';
    echo '</td>';
    echo '</tr>';

    echo '</table>';
    echo '<input type="hidden" name="op" value="iUsers">';
    echo '</form>';
}

switch ($op ?? '') {
    case 'iUsers':
        iUsers();
        break;
}
