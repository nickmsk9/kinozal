<?php

if (!defined('BLOCK_FILE')) {
    header('Location: ../index.php');
    exit;
}

global $content;

$blocktitle = 'Переходящие кубки';
$content = '';

$cups = kz_cups_current();
$rows = '';
$display_num = 0;

foreach ($cups as $cup) {
    if (empty($cup['userid']) || empty($cup['username'])) {
        continue;
    }

    $display_num++;

    $cup_title = kz_cups_h($cup['title']);
    $userid = (int)$cup['userid'];
    $username = kz_cups_h($cup['username']);
    $class = isset($cup['class']) ? (int)$cup['class'] : UC_USER;

    $flag = '';

    if (!empty($cup['flagpic'])) {
        $flagpic = kz_cups_h($cup['flagpic']);
        $flag = '<img src="/pic/flag/' . $flagpic . '" alt="" width="16" height="11" style="vertical-align:middle; margin-right:3px;">';
    }

    $cup_icon = '<i class="i1 ' . kz_cups_h($cup['icon']) . '"></i>';

    $icons = function_exists('get_user_icons') ? get_user_icons($cup) : '';

    $holder = $flag
        . '<a href="/userdetails.php?id=' . $userid . '" title="' . $cup_title . '">'
        . get_user_class_color($class, $username)
        . '</a>'
        . $icons
        . $cup_icon;

    $rows .= '<li>' . $display_num . '. ' . $holder . '</li>';
}

if ($rows !== '') {
    $content .= '<ul class="men">' . $rows . '</ul>';
} else {
    $content .= 'тут пока пусто';
}

?>