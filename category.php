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

require_once __DIR__ . '/include/bittorrent.php';

dbconn(false);
loggedinorreturn();

if (get_user_class() < UC_SYSOP) {
    stderr($tracker_lang['error'], $tracker_lang['access_denied']);
}

function category_h($value): string
{
    return htmlspecialchars_uni((string)$value);
}

function category_redirect(): void
{
    header('Location: /category.php');
    exit;
}

function category_image_name($value): string
{
    $value = trim((string)$value);

    if ($value === '') {
        return '';
    }

    if ($value !== basename($value) || !preg_match('/^[a-zA-Z0-9._-]+$/', $value)) {
        stderr('Ошибка', 'Укажите только имя файла картинки, например 8.gif.');
    }

    return $value;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = trim((string)($_POST['action'] ?? 'save'));
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete') {
        if (!is_valid_id($id)) {
            stderr('Ошибка', 'Некорректный идентификатор категории.');
        }

        $countRes = sql_query("
            SELECT COUNT(*) AS torrent_count
            FROM torrents
            WHERE category = $id
        ") or sqlerr(__FILE__, __LINE__);
        $countRow = mysqli_fetch_assoc($countRes);
        $torrentCount = (int)($countRow['torrent_count'] ?? 0);

        if ($torrentCount > 0) {
            stderr(
                'Ошибка',
                'Категорию нельзя удалить: к ней привязано раздач — ' . $torrentCount . '.'
            );
        }

        sql_query("DELETE FROM categories WHERE id = $id LIMIT 1") or sqlerr(__FILE__, __LINE__);
        category_redirect();
    }

    if ($action !== 'save') {
        stderr('Ошибка', 'Неизвестное действие.');
    }

    $name = trim((string)($_POST['name'] ?? ''));
    $image = category_image_name($_POST['image'] ?? '');
    $sort = max(0, (int)($_POST['sort'] ?? 0));

    if ($name === '') {
        stderr('Ошибка', 'Введите название категории.');
    }

    if (mb_strlen($name, 'UTF-8') > 80) {
        stderr('Ошибка', 'Название категории не должно превышать 80 символов.');
    }

    if ($image !== '' && !is_file(__DIR__ . '/pic/cat/' . $image)) {
        stderr('Ошибка', 'Файл pic/cat/' . category_h($image) . ' не найден.');
    }

    if (is_valid_id($id)) {
        sql_query("
            UPDATE categories
            SET name = " . sqlesc($name) . ",
                image = " . sqlesc($image) . ",
                sort = $sort
            WHERE id = $id
            LIMIT 1
        ") or sqlerr(__FILE__, __LINE__);
    } else {
        sql_query("
            INSERT INTO categories (sort, name, image)
            VALUES ($sort, " . sqlesc($name) . ", " . sqlesc($image) . ")
        ") or sqlerr(__FILE__, __LINE__);
    }

    category_redirect();
}

$editId = (int)($_GET['edit'] ?? 0);
$editCategory = array(
    'id' => 0,
    'name' => '',
    'image' => '',
    'sort' => 0,
);

if (is_valid_id($editId)) {
    $editRes = sql_query("
        SELECT id, name, image, sort
        FROM categories
        WHERE id = $editId
        LIMIT 1
    ") or sqlerr(__FILE__, __LINE__);
    $editRow = mysqli_fetch_assoc($editRes);

    if (!$editRow) {
        stderr('Ошибка', 'Категория не найдена.');
    }

    $editCategory = $editRow;
}

$res = sql_query("
    SELECT
        c.id,
        c.name,
        c.image,
        c.sort,
        COUNT(t.id) AS torrent_count
    FROM categories AS c
    LEFT JOIN torrents AS t ON t.category = c.id
    GROUP BY c.id, c.name, c.image, c.sort
    ORDER BY c.sort ASC, c.name ASC
") or sqlerr(__FILE__, __LINE__);

$categories = array();
while ($row = mysqli_fetch_assoc($res)) {
    $categories[] = $row;
}

stdhead('Категории');
?>

<div class="mn_wrap" id="category-form">
    <div class="tp1_title">
        <b><?=$editId > 0 ? 'Редактировать категорию' : 'Добавить категорию';?></b>
    </div>
    <div class="tp1_body">
        <form method="post" action="/category.php">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?=(int)$editCategory['id'];?>">

            <table class="tables1 w100p">
                <tr>
                    <td class="rowhead w150"><label for="category-name">Название</label></td>
                    <td>
                        <input
                            type="text"
                            id="category-name"
                            name="name"
                            class="w300"
                            maxlength="80"
                            value="<?=category_h($editCategory['name']);?>"
                            required
                            autofocus
                        >
                    </td>
                </tr>
                <tr>
                    <td class="rowhead"><label for="category-image">Картинка</label></td>
                    <td>
                        <input
                            type="text"
                            id="category-image"
                            name="image"
                            class="w300"
                            maxlength="255"
                            value="<?=category_h($editCategory['image']);?>"
                            placeholder="Например: 8.gif"
                        >
                        <span class="small">Файл из папки pic/cat</span>
                    </td>
                </tr>
                <tr>
                    <td class="rowhead"><label for="category-sort">Порядок</label></td>
                    <td>
                        <input
                            type="number"
                            id="category-sort"
                            name="sort"
                            class="w90"
                            min="0"
                            value="<?=(int)$editCategory['sort'];?>"
                        >
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="right">
                        <?php if ($editId > 0) { ?>
                            <a href="/category.php" class="buttonS">Отмена</a>
                        <?php } ?>
                        <input type="submit" class="buttonS" value=" <?=$editId > 0 ? 'Сохранить' : 'Добавить';?> ">
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>

<div class="mn_wrap">
    <div class="tp1_title">
        <b>Категории раздач</b>
        <span class="floatright">Всего: <?=count($categories);?></span>
    </div>
    <div class="tp1_body">
        <table class="brd w100p">
            <tr>
                <th class="center">Порядок</th>
                <th class="center">Иконка</th>
                <th>Название</th>
                <th class="center">Раздачи</th>
                <th class="center">Управление</th>
            </tr>

            <?php foreach ($categories as $category) {
                $id = (int)$category['id'];
                $torrentCount = (int)$category['torrent_count'];
                $image = trim((string)$category['image']);
                $imageExists = $image !== '' && is_file(__DIR__ . '/pic/cat/' . $image);
            ?>
                <tr class="bov">
                    <td class="center"><b><?=(int)$category['sort'];?></b></td>
                    <td class="center">
                        <?php if ($imageExists) { ?>
                            <img src="/pic/cat/<?=category_h($image);?>" alt="<?=category_h($category['name']);?>">
                        <?php } elseif ($image !== '') { ?>
                            <span class="red small">Файл не найден</span>
                        <?php } else { ?>
                            <span class="small">Нет</span>
                        <?php } ?>
                    </td>
                    <td>
                        <b><?=category_h($category['name']);?></b>
                        <div class="small">ID: <?=$id;?><?= $image !== '' ? ' · ' . category_h($image) : '';?></div>
                    </td>
                    <td class="center">
                        <a href="/browse.php?cat=<?=$id;?>" class="sbab"><?=$torrentCount;?></a>
                    </td>
                    <td class="center nw">
                        <a href="/category.php?edit=<?=$id;?>#category-form" class="buttonS">Изменить</a>
                        <form method="post" action="/category.php" style="display:inline;" onsubmit="return confirm('Удалить эту категорию?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?=$id;?>">
                            <input type="submit" class="buttonS" value="Удалить" <?=$torrentCount > 0 ? 'disabled title="Сначала перенесите раздачи"' : '';?>>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

<?php
stdfoot();
?>
