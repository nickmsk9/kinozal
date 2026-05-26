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

// IMPORTANT: Do not edit below unless you know what you are doing!
if (!defined("IN_TRACKER")) {
    die("Hacking attempt!");
}

// INCLUDE/REQUIRE BACK-END
require_once($rootpath . 'include/init.php');
require_once($rootpath . 'include/global.php');
require_once($rootpath . 'include/config.php');
require_once($rootpath . 'include/config.local.php');
require_once($rootpath . 'include/functions.php');
require_once($rootpath . 'include/blocks.php');
require_once($rootpath . 'include/secrets.php');
require_once($rootpath . 'include/secrets.local.php');

// INCLUDE SECURITY BACK-END
if ($ctracker) {
    require_once($rootpath . 'include/ctracker.php');
}

// LOAD GZIP/OUTPUT BUFFERING
if ($use_gzip) {
    gzip();
}

// IMPORTANT CONSTANTS
define("BETA", 0); // Set 0 to remove *BETA* notice.
define("BETA_NOTICE", "\n<br />Внимание! Версия не для промышленого использования!");
define("DEBUG_MODE", 0); // Shows the queries at the bottom of the page.

// BACKWARD CODE COMPATIBILITY
// Старые TBDev/Yuna-скрипты могут обращаться к $HTTP_*_VARS.
// В PHP 8 эти переменные давно не создаются автоматически, поэтому
// оставляем совместимость, но без magic_quotes, addslashes и each().
$HTTP_POST_VARS   = $_POST;
$HTTP_GET_VARS    = $_GET;
$HTTP_SERVER_VARS = $_SERVER;
$HTTP_COOKIE_VARS = $_COOKIE;
$HTTP_ENV_VARS    = $_ENV;
$HTTP_POST_FILES  = $_FILES;

// MAGIC QUOTES REMOVED
// get_magic_quotes_gpc() удалён в PHP 8.
// Раньше код вручную добавлял addslashes() ко всем входящим данным,
// но это ломает данные и не является нормальной защитой от SQL-инъекций.
// Экранирование должно выполняться только в момент SQL-запроса.

?>