<?php

if (!defined('BLOCK_FILE')) {
    header('Location: ../index.php');
    exit;
}

global $content;

$blocktitle = 'Переходящие кубки';
$content = '';

kz_cups_update_auto(false);

$cups = kz_cups_current();
$content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0">';

foreach ($cups as $cup) {
    $num = (int)$cup['sort'];
    $icon = '<i class="i1 ' . kz_cups_h($cup['icon']) . '"></i>';
    $cup_title = kz_cups_h($cup['title']);
    $holder = '<span style="color:#777;">—</span>';

    if (!empty($cup['userid']) && !empty($cup['username'])) {
        $userid = (int)$cup['userid'];
        $username = kz_cups_h($cup['username']);
        $class = isset($cup['class']) ? (int)$cup['class'] : UC_USER;
        $flag = '';

        if (!empty($cup['flagpic'])) {
            $flagpic = kz_cups_h($cup['flagpic']);
            $flag = '<img src="pic/flag/' . $flagpic . '" alt="" style="vertical-align:middle; margin-right:2px;">';
        }

        $icons = function_exists('get_user_icons') ? get_user_icons($cup) : '';
        $holder = $flag
            . '<a href="userdetails.php?id=' . $userid . '" title="' . $cup_title . '">'
            . get_user_class_color($class, $username)
            . '</a>'
            . $icons
            . ' ' . $icon;
    }

    $content .= '<tr>'
        . '<td class="embedded" width="16" align="right">' . $num . '.</td>'
        . '<td class="embedded">' . $holder . '</td>'
        . '</tr>';
}

$content .= '</table>';

?>
