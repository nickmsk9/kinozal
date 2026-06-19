<?php


// IMPORTANT: Do not edit below unless you know what you are doing!
if (!defined("IN_TRACKER")) {
    die("Hacking attempt!");
}

// INCLUDE/REQUIRE BACK-END
require_once($rootpath . 'include/init.php');
require_once($rootpath . 'include/global.php');
require_once($rootpath . 'include/config.php');
require_once($rootpath . 'include/functions.php');
require_once($rootpath . 'include/site_settings.php');
require_once($rootpath . 'include/cups.php');
require_once($rootpath . 'include/user_statuses.php');
require_once($rootpath . 'include/reputation.php');
require_once($rootpath . 'include/pay.php');
require_once($rootpath . 'include/blocks.php');
require_once($rootpath . 'include/flags.php');
require_once($rootpath . 'include/secrets.php');
require_once($rootpath . 'include/cache.php');

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
define("DEBUG_MODE", 0); // SQL debug is shown automatically to UC_SYSOP users.

// MAGIC QUOTES REMOVED
// get_magic_quotes_gpc() удалён в PHP 8.
// Раньше код вручную добавлял addslashes() ко всем входящим данным,
// но это ломает данные и не является нормальной защитой от SQL-инъекций.
// Экранирование должно выполняться только в момент SQL-запроса.

?>
