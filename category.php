<?php

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

ob_start();

require_once __DIR__ . '/include/bittorrent.php';

dbconn(false);
loggedinorreturn();

if (get_user_class() < UC_SYSOP) {
    die($tracker_lang['access_denied'] ?? 'Доступ запрещён');
}

if (!function_exists('category_h')) {
    function category_h($value): string
    {
        if (function_exists('htmlspecialchars_uni')) {
            return htmlspecialchars_uni((string)$value);
        }

        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

$self = category_h($_SERVER['PHP_SELF'] ?? 'category.php');

stdhead('Категории');

print '<h1 align="center">Категории</h1>';
print '<br>';

/*
 * Удаление категории.
 */
$sure = $_GET['sure'] ?? '';
$delid = isset($_GET['delid']) ? (int)$_GET['delid'] : 0;

if ($sure === 'yes' && is_valid_id($delid)) {
    sql_query("
        DELETE FROM `categories`
        WHERE `id` = " . $delid . "
        LIMIT 1
    ") or sqlerr(__FILE__, __LINE__);

    print '<table border="1" cellspacing="0" cellpadding="5" align="center" width="650">';
    print '<tr><td class="colhead" align="center">Удаление категории</td></tr>';
    print '<tr><td class="text" align="center">';
    print 'Категория успешно удалена. [ <a href="category.php">Назад</a> ]';
    print '</td></tr>';
    print '</table>';

    stdfoot();
    exit;
}

if (is_valid_id($delid)) {
    $name = category_h($_GET['cat'] ?? '');

    print '<table border="1" cellspacing="0" cellpadding="5" align="center" width="70%">';
    print '<tr><td class="colhead" align="center">Подтверждение удаления</td></tr>';
    print '<tr><td class="text" align="center">';
    print 'Вы действительно хотите удалить категорию <b>' . $name . '</b>? ';
    print '[ <strong><a href="' . $self . '?delid=' . $delid . '&amp;cat=' . urlencode((string)($_GET['cat'] ?? '')) . '&amp;sure=yes">Да</a></strong> / ';
    print '<strong><a href="' . $self . '">Нет</a></strong> ]';
    print '</td></tr>';
    print '</table>';

    stdfoot();
    exit;
}

/*
 * Сохранение редактирования категории.
 */
$edited = isset($_GET['edited']) ? (int)$_GET['edited'] : 0;

if ($edited === 1) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $catName = trim((string)($_GET['cat_name'] ?? ''));
    $catImg = trim((string)($_GET['cat_img'] ?? ''));
    $catSort = isset($_GET['cat_sort']) ? (int)$_GET['cat_sort'] : 0;

    if (!is_valid_id($id)) {
        stderr('Ошибка', 'Некорректный ID категории.');
    }

    if ($catName === '') {
        stderr('Ошибка', 'Введите название категории.');
    }

    sql_query("
        UPDATE `categories`
        SET
            `name` = " . sqlesc($catName) . ",
            `image` = " . sqlesc($catImg) . ",
            `sort` = " . $catSort . "
        WHERE `id` = " . $id . "
        LIMIT 1
    ") or sqlerr(__FILE__, __LINE__);

    print '<table border="1" cellspacing="0" cellpadding="5" align="center" width="70%">';
    print '<tr><td class="colhead" align="center">Редактирование категории</td></tr>';
    print '<tr><td class="text" align="center">';
    print 'Категория успешно отредактирована. [ <a href="category.php">Назад</a> ]';
    print '</td></tr>';
    print '</table>';

    stdfoot();
    exit;
}

/*
 * Форма редактирования категории.
 */
$editid = isset($_GET['editid']) ? (int)$_GET['editid'] : 0;

if (is_valid_id($editid)) {
    $res = sql_query("
        SELECT `id`, `name`, `image`, `sort`
        FROM `categories`
        WHERE `id` = " . $editid . "
        LIMIT 1
    ") or sqlerr(__FILE__, __LINE__);

    $cat = mysqli_fetch_assoc($res);

    if (!$cat) {
        stderr('Ошибка', 'Категория не найдена.');
    }

    $id = (int)$cat['id'];
    $name = category_h($cat['name']);
    $img = category_h($cat['image']);
    $sort = (int)$cat['sort'];

    print '<form method="get" action="' . $self . '">';
    print '<input type="hidden" name="edited" value="1">';
    print '<input type="hidden" name="id" value="' . $id . '">';

    print '<table border="1" cellspacing="0" cellpadding="5" align="center" width="70%">';
    print '<tr><td class="colhead" colspan="2" align="center">Редактирование категории: ' . $name . '</td></tr>';

    print '<tr>';
    print '<td class="rowhead" width="180">Название</td>';
    print '<td class="text"><input type="text" size="50" name="cat_name" value="' . $name . '"></td>';
    print '</tr>';

    print '<tr>';
    print '<td class="rowhead">Картинка</td>';
    print '<td class="text"><input type="text" size="50" name="cat_img" value="' . $img . '"></td>';
    print '</tr>';

    print '<tr>';
    print '<td class="rowhead">Сортировка</td>';
    print '<td class="text"><input type="text" size="50" name="cat_sort" value="' . $sort . '"></td>';
    print '</tr>';

    print '<tr>';
    print '<td class="text" colspan="2" align="center">';
    print '<input type="submit" value="Редактировать" class="buttonS">';
    print ' ';
    print '<a href="category.php">Отмена</a>';
    print '</td>';
    print '</tr>';

    print '</table>';
    print '</form>';

    stdfoot();
    exit;
}

/*
 * Добавление новой категории.
 */
$success = false;
$add = $_GET['add'] ?? '';

if ($add === 'true') {
    $catName = trim((string)($_GET['cat_name'] ?? ''));
    $catImg = trim((string)($_GET['cat_img'] ?? ''));
    $catSort = isset($_GET['cat_sort']) ? (int)$_GET['cat_sort'] : 0;

    if ($catName === '') {
        stderr('Ошибка', 'Введите название категории.');
    }

    sql_query("
        INSERT INTO `categories`
            (`name`, `image`, `sort`)
        VALUES
            (" . sqlesc($catName) . ", " . sqlesc($catImg) . ", " . $catSort . ")
    ") or sqlerr(__FILE__, __LINE__);

    $success = true;
}

/*
 * Форма добавления категории.
 */
print '<form method="get" action="' . $self . '">';
print '<input type="hidden" name="add" value="true">';

print '<table border="1" cellspacing="0" cellpadding="5" align="center" width="70%">';
print '<tr><td class="colhead" colspan="2" align="center">Добавить новую категорию</td></tr>';

if ($success) {
    print '<tr><td class="text" colspan="2" align="center"><strong>Категория успешно добавлена.</strong></td></tr>';
}

print '<tr>';
print '<td class="rowhead" width="180">Название</td>';
print '<td class="text"><input type="text" size="50" name="cat_name"></td>';
print '</tr>';

print '<tr>';
print '<td class="rowhead">Картинка</td>';
print '<td class="text"><input type="text" size="50" name="cat_img"></td>';
print '</tr>';

print '<tr>';
print '<td class="rowhead">Сортировка</td>';
print '<td class="text"><input type="text" size="50" name="cat_sort"></td>';
print '</tr>';

print '<tr>';
print '<td class="text" colspan="2" align="center">';
print '<input type="submit" value="Создать категорию" class="buttonS">';
print '</td>';
print '</tr>';

print '</table>';
print '</form>';

print '<br>';

/*
 * Список существующих категорий.
 */
print '<table border="1" cellspacing="0" cellpadding="5" align="center" width="1450">';
print '<tr><td class="colhead" colspan="7" align="center">Существующие категории</td></tr>';

print '<tr>';
print '<td class="colhead" align="center">ID</td>';
print '<td class="colhead" align="center">Сортировка</td>';
print '<td class="colhead" align="left">Название</td>';
print '<td class="colhead" align="center">Картинка</td>';
print '<td class="colhead" align="center">Просмотр</td>';
print '<td class="colhead" align="center">Редактировать</td>';
print '<td class="colhead" align="center">Удалить</td>';
print '</tr>';

$query = "
    SELECT `id`, `name`, `image`, `sort`
    FROM `categories`
    ORDER BY `sort` ASC, `name` ASC
";

$sql = sql_query($query) or sqlerr(__FILE__, __LINE__);

while ($row = mysqli_fetch_assoc($sql)) {
    $id = (int)$row['id'];
    $sort = (int)$row['sort'];
    $nameRaw = (string)$row['name'];
    $imgRaw = (string)$row['image'];

    $name = category_h($nameRaw);
    $img = category_h($imgRaw);

    $catImgUrl = category_h($DEFAULTBASEURL . '/pic/cat/' . $imgRaw);

    print '<tr>';

    print '<td class="text" align="center"><strong>' . $id . '</strong></td>';
    print '<td class="text" align="center"><strong>' . $sort . '</strong></td>';
    print '<td class="text" align="left"><strong>' . $name . '</strong></td>';

    print '<td class="text" align="center">';
    if ($imgRaw !== '') {
        print '<img src="' . $catImgUrl . '" border="0" alt="' . $name . '">';
    } else {
        print '&nbsp;';
    }
    print '</td>';

    print '<td class="text" align="center">';
    print '<a href="browse.php?cat=' . $id . '">';
    print '<img src="' . category_h($DEFAULTBASEURL . '/pic/viewnfo.gif') . '" border="0" class="special" alt="Просмотр">';
    print '</a>';
    print '</td>';

    print '<td class="text" align="center">';
    print '<a href="category.php?editid=' . $id . '">';
    print '<img src="' . category_h($DEFAULTBASEURL . '/pic/multipage.gif') . '" border="0" class="special" alt="Редактировать">';
    print '</a>';
    print '</td>';

    print '<td class="text" align="center">';
    print '<a href="category.php?delid=' . $id . '&amp;cat=' . urlencode($nameRaw) . '">';
    print '<img src="' . category_h($DEFAULTBASEURL . '/pic/warned2.gif') . '" border="0" class="special" alt="Удалить">';
    print '</a>';
    print '</td>';

    print '</tr>';
}

print '</table>';

stdfoot();

?>
