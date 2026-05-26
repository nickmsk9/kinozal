<?php

if (!defined('ADMIN_FILE')) {
    die('Illegal File Access');
}

if (!function_exists('faq_h')) {
    function faq_h($value): string
    {
        if (function_exists('htmlspecialchars_uni')) {
            return htmlspecialchars_uni((string)$value);
        }

        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('faq_status_html')) {
    function faq_status_html(int $flag, string $rootpath = '', string $pic_base_url = ''): string
    {
        switch ($flag) {
            case 0:
                return '<span class="red">Скрыто</span>';

            case 2:
                $src = faq_h($rootpath . $pic_base_url . '/updated.png');
                return '<img src="' . $src . '" alt="Обновлено" style="vertical-align: middle;">';

            case 3:
                $src = faq_h($rootpath . $pic_base_url . '/new.png');
                return '<img src="' . $src . '" alt="Новое" style="vertical-align: middle;">';

            default:
                return 'Обычный';
        }
    }
}

function FaqAdmin(): void
{
    global $rootpath, $pic_base_url, $admin_file;

    $faq_categ = [];
    $faq_orphaned = [];

    $adminUrl = faq_h($admin_file . '.php');

    /*
     * Собираем секции FAQ.
     */
    $res = sql_query("
        SELECT `id`, `question`, `flag`, `order`
        FROM `faq`
        WHERE `type` = 'categ'
        ORDER BY `order` ASC
    ") or sqlerr(__FILE__, __LINE__);

    while ($arr = mysqli_fetch_assoc($res)) {
        $id = (int)$arr['id'];

        $faq_categ[$id]['title'] = $arr['question'];
        $faq_categ[$id]['flag'] = (int)$arr['flag'];
        $faq_categ[$id]['order'] = (int)$arr['order'];
        $faq_categ[$id]['items'] = [];
    }

    /*
     * Собираем элементы FAQ.
     */
    $res = sql_query("
        SELECT `id`, `question`, `flag`, `categ`, `order`
        FROM `faq`
        WHERE `type` = 'item'
        ORDER BY `order` ASC
    ") or sqlerr(__FILE__, __LINE__);

    while ($arr = mysqli_fetch_assoc($res)) {
        $categId = (int)$arr['categ'];
        $itemId = (int)$arr['id'];

        $faq_categ[$categId]['items'][$itemId] = [
            'question' => $arr['question'],
            'flag'     => (int)$arr['flag'],
            'order'    => (int)$arr['order'],
        ];
    }

    /*
     * Отделяем элементы, у которых удалена/отсутствует секция.
     */
    foreach ($faq_categ as $id => $data) {
        if (!array_key_exists('title', $data)) {
            if (!empty($data['items'])) {
                foreach ($data['items'] as $itemId => $item) {
                    $faq_orphaned[$itemId] = $item;
                }
            }

            unset($faq_categ[$id]);
        }
    }

    print '<form method="post" action="' . $adminUrl . '?op=FaqAction&amp;action=reorder">';

    if (!empty($faq_categ)) {
        foreach ($faq_categ as $id => $section) {
            print '<br>';
            print '<table border="1" cellspacing="0" cellpadding="5" align="center" width="95%">';
            print '<tr>';
            print '<td class="colhead center" colspan="2">Позиция</td>';
            print '<td class="colhead left">Секция/Название</td>';
            print '<td class="colhead center">Статус</td>';
            print '<td class="colhead center">Действие</td>';
            print '</tr>';

            print '<tr>';
            print '<td align="center" width="40">';
            print '<select name="order[' . (int)$id . ']">';

            for ($n = 1, $cnt = count($faq_categ); $n <= $cnt; $n++) {
                $selected = ($n === (int)$section['order']) ? ' selected="selected"' : '';
                print '<option value="' . $n . '"' . $selected . '>' . $n . '</option>';
            }

            print '</select>';
            print '</td>';
            print '<td align="center" width="40">&nbsp;</td>';
            print '<td><b>' . faq_h($section['title']) . '</b></td>';
            print '<td align="center" width="60">' . faq_status_html((int)$section['flag'], $rootpath, $pic_base_url) . '</td>';
            print '<td align="center" width="60">';
            print '<a href="' . $adminUrl . '?op=FaqAction&amp;action=edit&amp;id=' . (int)$id . '">E</a> / ';
            print '<a href="' . $adminUrl . '?op=FaqAction&amp;action=delete&amp;id=' . (int)$id . '">D</a>';
            print '</td>';
            print '</tr>';

            if (!empty($section['items'])) {
                foreach ($section['items'] as $itemId => $item) {
                    print '<tr>';
                    print '<td align="center" width="40">&nbsp;</td>';
                    print '<td align="center" width="40">';
                    print '<select name="order[' . (int)$itemId . ']">';

                    for ($n = 1, $cnt = count($section['items']); $n <= $cnt; $n++) {
                        $selected = ($n === (int)$item['order']) ? ' selected="selected"' : '';
                        print '<option value="' . $n . '"' . $selected . '>' . $n . '</option>';
                    }

                    print '</select>';
                    print '</td>';
                    print '<td>' . faq_h($item['question']) . '</td>';
                    print '<td align="center" width="60">' . faq_status_html((int)$item['flag'], $rootpath, $pic_base_url) . '</td>';
                    print '<td align="center" width="60">';
                    print '<a href="' . $adminUrl . '?op=FaqAction&amp;action=edit&amp;id=' . (int)$itemId . '">E</a> / ';
                    print '<a href="' . $adminUrl . '?op=FaqAction&amp;action=delete&amp;id=' . (int)$itemId . '">D</a>';
                    print '</td>';
                    print '</tr>';
                }
            }

            print '<tr>';
            print '<td colspan="5" align="center">';
            print '<a href="' . $adminUrl . '?op=FaqAction&amp;action=additem&amp;inid=' . (int)$id . '">Добавить новый элемент</a>';
            print '</td>';
            print '</tr>';

            print '</table>';
        }
    }

    /*
     * Удалённые/осиротевшие элементы.
     */
    if (!empty($faq_orphaned)) {
        print '<br>';
        print '<table border="1" cellspacing="0" cellpadding="5" align="center" width="95%">';
        print '<tr><td align="center" colspan="3"><b class="red">Удаленные элементы</b></td></tr>';
        print '<tr>';
        print '<td class="colhead left">Название элемента</td>';
        print '<td class="colhead center">Статус</td>';
        print '<td class="colhead center">Действие</td>';
        print '</tr>';

        foreach ($faq_orphaned as $id => $item) {
            print '<tr>';
            print '<td>' . faq_h($item['question']) . '</td>';
            print '<td align="center" width="60">' . faq_status_html((int)$item['flag'], $rootpath, $pic_base_url) . '</td>';
            print '<td align="center" width="60">';
            print '<a href="' . $adminUrl . '?op=FaqAction&amp;action=edit&amp;id=' . (int)$id . '">edit</a> ';
            print '<a href="' . $adminUrl . '?op=FaqAction&amp;action=delete&amp;id=' . (int)$id . '">delete</a>';
            print '</td>';
            print '</tr>';
        }

        print '</table>';
    }

    print '<br>';
    print '<table border="1" cellspacing="0" cellpadding="5" align="center" width="95%">';
    print '<tr><td align="center"><a href="' . $adminUrl . '?op=FaqAction&amp;action=addsection">Добавить новую секцию</a></td></tr>';
    print '</table>';

    print '<p align="center">';
    print '<input type="submit" name="reorder" value="Сортировать" class="buttonS">';
    print '</p>';

    print '</form>';
}

function FaqAction(): void
{
    global $admin_file;

    $adminUrl = faq_h($admin_file . '.php');
    $action = $_GET['action'] ?? '';

    /*
     * Сортировка.
     */
    if ($action === 'reorder') {
        if (!empty($_POST['order']) && is_array($_POST['order'])) {
            foreach ($_POST['order'] as $id => $position) {
                $id = (int)$id;
                $position = (int)$position;

                if ($id > 0 && $position > 0) {
                    sql_query("
                        UPDATE `faq`
                        SET `order` = " . $position . "
                        WHERE `id` = " . $id . "
                    ") or sqlerr(__FILE__, __LINE__);
                }
            }
        }

        header('Location: ' . $admin_file . '.php?op=FaqAdmin');
        exit;
    }

    /*
     * Форма редактирования секции или элемента.
     */
    if ($action === 'edit' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];

        print '<h2>Редактирование секции или элемента</h2>';

        $res = sql_query("
            SELECT *
            FROM `faq`
            WHERE `id` = " . $id . "
            LIMIT 1
        ") or sqlerr(__FILE__, __LINE__);

        if ($arr = mysqli_fetch_assoc($res)) {
            $faqId = (int)$arr['id'];
            $type = (string)$arr['type'];
            $question = faq_h($arr['question']);
            $answer = faq_h($arr['answer']);
            $flag = (int)$arr['flag'];
            $categ = (int)$arr['categ'];

            if ($type === 'item') {
                print '<form method="post" action="' . $adminUrl . '?op=FaqAction&amp;action=edititem">';
                print '<table border="1" cellspacing="0" cellpadding="5" align="center" width="100%">';

                print '<tr><td>ID:</td><td>' . $faqId . '<input type="hidden" name="id" value="' . $faqId . '"></td></tr>';
                print '<tr><td>Вопрос:</td><td><input type="text" name="question" value="' . $question . '" size="50"></td></tr>';
                print '<tr><td style="vertical-align: top;">Ответ:</td><td><textarea rows="15" cols="80" name="answer">' . $answer . '</textarea></td></tr>';

                print '<tr><td>Статус:</td><td>';
                print '<select name="flag" style="width: 110px;">';
                print '<option value="0" style="color: #FF0000;"' . ($flag === 0 ? ' selected="selected"' : '') . '>Скрыто</option>';
                print '<option value="1" style="color: #000000;"' . ($flag === 1 ? ' selected="selected"' : '') . '>Обычный</option>';
                print '<option value="2" style="color: #0000FF;"' . ($flag === 2 ? ' selected="selected"' : '') . '>Обновлено</option>';
                print '<option value="3" style="color: #008000;"' . ($flag === 3 ? ' selected="selected"' : '') . '>Новое</option>';
                print '</select>';
                print '</td></tr>';

                print '<tr><td>Категория:</td><td>';
                print '<select style="width: 300px;" name="categ">';

                $res2 = sql_query("
                    SELECT `id`, `question`
                    FROM `faq`
                    WHERE `type` = 'categ'
                    ORDER BY `order` ASC
                ") or sqlerr(__FILE__, __LINE__);

                while ($arr2 = mysqli_fetch_assoc($res2)) {
                    $catId = (int)$arr2['id'];
                    $selected = ($catId === $categ) ? ' selected="selected"' : '';

                    print '<option value="' . $catId . '"' . $selected . '>' . faq_h($arr2['question']) . '</option>';
                }

                print '</select>';
                print '</td></tr>';

                print '<tr><td colspan="2" align="center"><input type="submit" name="edit" value="Отредактировать" class="buttonS"></td></tr>';
                print '</table>';
                print '</form>';
            } elseif ($type === 'categ') {
                print '<form method="post" action="' . $adminUrl . '?op=FaqAction&amp;action=editsect">';
                print '<table border="1" cellspacing="0" cellpadding="5" width="100%" align="center">';

                print '<tr><td>ID:</td><td>' . $faqId . '<input type="hidden" name="id" value="' . $faqId . '"></td></tr>';
                print '<tr><td>Название:</td><td><input style="width: 300px;" type="text" name="title" value="' . $question . '"></td></tr>';

                print '<tr><td>Статус:</td><td>';
                print '<select name="flag" style="width: 110px;">';
                print '<option value="0" style="color: #FF0000;"' . ($flag === 0 ? ' selected="selected"' : '') . '>Скрыто</option>';
                print '<option value="1" style="color: #000000;"' . ($flag === 1 ? ' selected="selected"' : '') . '>Обычный</option>';
                print '</select>';
                print '</td></tr>';

                print '<tr><td colspan="2" align="center"><input type="submit" class="buttonS" name="edit" value="Отредактировать"></td></tr>';

                print '</table>';
                print '</form>';
            }
        }

        return;
    }

    /*
     * Сохранение элемента.
     */
    if (
        $action === 'edititem'
        && isset($_POST['id'], $_POST['question'], $_POST['answer'], $_POST['flag'], $_POST['categ'])
    ) {
        $id = (int)$_POST['id'];
        $question = trim((string)$_POST['question']);
        $answer = (string)$_POST['answer'];
        $flag = (int)$_POST['flag'];
        $categ = (int)$_POST['categ'];

        if ($id > 0 && $question !== '' && $categ > 0) {
            sql_query("
                UPDATE `faq`
                SET
                    `question` = " . sqlesc($question) . ",
                    `answer` = " . sqlesc($answer) . ",
                    `flag` = " . $flag . ",
                    `categ` = " . $categ . "
                WHERE `id` = " . $id . "
            ") or sqlerr(__FILE__, __LINE__);
        }

        header('Location: ' . $admin_file . '.php?op=FaqAdmin');
        exit;
    }

    /*
     * Сохранение секции.
     */
    if (
        $action === 'editsect'
        && isset($_POST['id'], $_POST['title'], $_POST['flag'])
    ) {
        $id = (int)$_POST['id'];
        $title = trim((string)$_POST['title']);
        $flag = (int)$_POST['flag'];

        if ($id > 0 && $title !== '') {
            sql_query("
                UPDATE `faq`
                SET
                    `question` = " . sqlesc($title) . ",
                    `answer` = '',
                    `flag` = " . $flag . ",
                    `categ` = 0
                WHERE `id` = " . $id . "
            ") or sqlerr(__FILE__, __LINE__);
        }

        header('Location: ' . $admin_file . '.php?op=FaqAdmin');
        exit;
    }

    /*
     * Удаление секции или элемента.
     */
    if ($action === 'delete' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $confirm = $_GET['confirm'] ?? '';

        if ($id > 0 && $confirm === 'yes') {
            sql_query("
                DELETE FROM `faq`
                WHERE `id` = " . $id . "
                LIMIT 1
            ") or sqlerr(__FILE__, __LINE__);

            header('Location: ' . $admin_file . '.php?op=FaqAdmin');
            exit;
        }

        print '<h1 align="center">Требуется подтверждение</h1>';
        print '<table border="1" cellspacing="0" cellpadding="5" align="center" width="95%">';
        print '<tr><td align="center">';
        print 'Нажмите <a href="' . $adminUrl . '?op=FaqAction&amp;action=delete&amp;id=' . $id . '&amp;confirm=yes">сюда</a>, чтобы подтвердить удаление.';
        print '</td></tr>';
        print '</table>';

        return;
    }

    /*
     * Форма добавления элемента.
     */
    if ($action === 'additem' && isset($_GET['inid'])) {
        $inid = (int)$_GET['inid'];

        print '<h2>Добавить элемент</h2>';
        print '<form method="post" action="' . $adminUrl . '?op=FaqAction&amp;action=addnewitem">';
        print '<table border="1" cellspacing="0" cellpadding="5" align="center" width="100%">';

        print '<tr><td>Вопрос:</td><td><input type="text" name="question" value=""></td></tr>';
        print '<tr><td style="vertical-align: top;">Ответ:</td><td><textarea rows="15" cols="80" name="answer"></textarea></td></tr>';

        print '<tr><td>Статус:</td><td>';
        print '<select name="flag" style="width: 110px;">';
        print '<option value="0" style="color: #FF0000;">Скрыто</option>';
        print '<option value="1" style="color: #000000;" selected="selected">Обычный</option>';
        print '<option value="2" style="color: #0000FF;">Обновлено</option>';
        print '<option value="3" style="color: #008000;">Новое</option>';
        print '</select>';
        print '</td></tr>';

        print '<tr><td>Категория:</td><td>';
        print '<select style="width: 300px;" name="categ">';

        $res = sql_query("
            SELECT `id`, `question`
            FROM `faq`
            WHERE `type` = 'categ'
            ORDER BY `order` ASC
        ") or sqlerr(__FILE__, __LINE__);

        while ($arr = mysqli_fetch_assoc($res)) {
            $catId = (int)$arr['id'];
            $selected = ($catId === $inid) ? ' selected="selected"' : '';

            print '<option value="' . $catId . '"' . $selected . '>' . faq_h($arr['question']) . '</option>';
        }

        print '</select>';
        print '</td></tr>';

        print '<tr><td colspan="2" align="center"><input type="submit" class="buttonS" name="edit" value="Добавить"></td></tr>';

        print '</table>';
        print '</form>';

        return;
    }

    /*
     * Форма добавления секции.
     */
    if ($action === 'addsection') {
        print '<h2>Добавить секцию</h2>';
        print '<form method="post" action="' . $adminUrl . '?op=FaqAction&amp;action=addnewsect">';
        print '<table border="1" cellspacing="0" cellpadding="5" align="center" width="100%">';

        print '<tr><td>Название:</td><td><input style="width: 600px;" type="text" name="title" value=""></td></tr>';

        print '<tr><td>Статус:</td><td>';
        print '<select name="flag" style="width: 110px;">';
        print '<option value="0" style="color: #FF0000;">Скрыто</option>';
        print '<option value="1" style="color: #000000;" selected="selected">Обычный</option>';
        print '</select>';
        print '</td></tr>';

        print '<tr><td colspan="2" align="center"><input type="submit" name="edit" value="Добавить" class="buttonS"></td></tr>';

        print '</table>';
        print '</form>';

        return;
    }

    /*
     * Добавление нового элемента.
     */
    if (
        $action === 'addnewitem'
        && isset($_POST['question'], $_POST['answer'], $_POST['flag'], $_POST['categ'])
    ) {
        $question = trim((string)$_POST['question']);
        $answer = (string)$_POST['answer'];
        $flag = (int)$_POST['flag'];
        $categ = (int)$_POST['categ'];

        if ($question !== '' && $categ > 0) {
            $order = 1;

            $res = sql_query("
                SELECT MAX(`order`) AS max_order
                FROM `faq`
                WHERE `type` = 'item'
                  AND `categ` = " . $categ . "
            ") or sqlerr(__FILE__, __LINE__);

            if ($arr = mysqli_fetch_assoc($res)) {
                $order = (int)$arr['max_order'] + 1;
            }

            sql_query("
                INSERT INTO `faq`
                    (`type`, `question`, `answer`, `flag`, `categ`, `order`)
                VALUES
                    ('item', " . sqlesc($question) . ", " . sqlesc($answer) . ", " . $flag . ", " . $categ . ", " . $order . ")
            ") or sqlerr(__FILE__, __LINE__);
        }

        header('Location: ' . $admin_file . '.php?op=FaqAdmin');
        exit;
    }

    /*
     * Добавление новой секции.
     */
    if (
        $action === 'addnewsect'
        && isset($_POST['title'], $_POST['flag'])
    ) {
        $title = trim((string)$_POST['title']);
        $flag = (int)$_POST['flag'];

        if ($title !== '') {
            $order = 1;

            $res = sql_query("
                SELECT MAX(`order`) AS max_order
                FROM `faq`
                WHERE `type` = 'categ'
            ") or sqlerr(__FILE__, __LINE__);

            if ($arr = mysqli_fetch_assoc($res)) {
                $order = (int)$arr['max_order'] + 1;
            }

            sql_query("
                INSERT INTO `faq`
                    (`type`, `question`, `answer`, `flag`, `categ`, `order`)
                VALUES
                    ('categ', " . sqlesc($title) . ", '', " . $flag . ", 0, " . $order . ")
            ") or sqlerr(__FILE__, __LINE__);
        }

        header('Location: ' . $admin_file . '.php?op=FaqAdmin');
        exit;
    }

    header('Location: ' . $admin_file . '.php?op=FaqAdmin');
    exit;
}

switch ($op ?? '') {
    case 'FaqAdmin':
        FaqAdmin();
        break;

    case 'FaqAction':
        FaqAction();
        break;
}