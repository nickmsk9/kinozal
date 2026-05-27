<?php

if (!defined('BLOCK_FILE')) {
    header('Location: ../index.php');
    exit;
}

global $content;

$blocktitle = 'День рождения';
$content = '';

$today = date('m-d');

$res = sql_query("
    SELECT id, username, class
    FROM users
    WHERE status = 'confirmed'
      AND enabled = 'yes'
      AND birthday IS NOT NULL
      AND DATE_FORMAT(birthday, '%m-%d') = " . sqlesc($today) . "
    ORDER BY class DESC, username ASC
") or sqlerr(__FILE__, __LINE__);

$users = array();
while ($row = mysqli_fetch_assoc($res)) {
    $users[] = '<a href="/userdetails.php?id=' . (int)$row['id'] . '" class="u' . (int)$row['class'] . '">'
        . htmlspecialchars_uni($row['username'])
        . '</a>';
}

if ($users) {
    $content .= '<li class="justify small">' . implode(', ', $users) . '</li>';
} else {
    $content .= '<li class="justify small">именинников сегодня нет</li>';
}

?>
