<?php

if (!defined('ADMIN_FILE')) {
    die('Illegal File Access');
}

BuildMenu("" . $admin_file . ".php?op=CupsAdmin", "Переходящие кубки", "cups.svg");

?>
