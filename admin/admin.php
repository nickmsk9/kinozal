<?php

loggedinorreturn();

if (get_user_class() < UC_ADMINISTRATOR) {
    stderr($tracker_lang['error'], 'Что вы тут забыли?');
}

require_once 'admin/core.php';

$op = (string)($_GET['op'] ?? $_POST['op'] ?? 'Main');
$counter = 0;

function BuildMenu($url, $title, $image = '')
{
    global $counter;

    $urlSafe   = htmlspecialchars((string)$url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $titleSafe = htmlspecialchars((string)$title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $image     = trim((string)$image);
    $iconKey   = strtolower(preg_replace('/[^a-z0-9_]+/i', '', preg_replace('/\.(gif|png|jpg|jpeg|svg)$/i', '', $image)));

    if ($iconKey === '') {
        $iconKey = strtolower((string)(parse_url((string)$url, PHP_URL_QUERY) ?: 'system'));
        $iconKey = preg_replace('/^op=|admin$/i', '', $iconKey);
        $iconKey = strtolower(preg_replace('/[^a-z0-9_]+/i', '', $iconKey));
    }

    $icons = array(
        'block' => array('#5a71b0', '<rect x="7" y="8" width="18" height="6" rx="1.5"/><rect x="7" y="18" width="8" height="6" rx="1.5"/><rect x="18" y="18" width="7" height="6" rx="1.5"/>'),
        'blocksadmin' => array('#5a71b0', '<rect x="7" y="8" width="18" height="6" rx="1.5"/><rect x="7" y="18" width="8" height="6" rx="1.5"/><rect x="18" y="18" width="7" height="6" rx="1.5"/>'),
        'db' => array('#4a7f52', '<ellipse cx="16" cy="9" rx="8" ry="3"/><path d="M8 9v10c0 1.7 3.6 3 8 3s8-1.3 8-3V9"/><path d="M8 14c0 1.7 3.6 3 8 3s8-1.3 8-3"/>'),
        'statusdb' => array('#4a7f52', '<ellipse cx="16" cy="9" rx="8" ry="3"/><path d="M8 9v10c0 1.7 3.6 3 8 3s8-1.3 8-3V9"/><path d="M8 14c0 1.7 3.6 3 8 3s8-1.3 8-3"/>'),
        'faq' => array('#9a6b2f', '<circle cx="16" cy="16" r="10"/><path d="M13 13a3 3 0 1 1 5 2.2c-1.3.8-2 1.5-2 3"/><circle cx="16" cy="23" r="1"/>'),
        'password' => array('#7a5aa8', '<rect x="8" y="14" width="16" height="10" rx="2"/><path d="M11 14v-3a5 5 0 0 1 10 0v3"/><circle cx="16" cy="19" r="1.5"/>'),
        'show' => array('#b57936', '<path d="M6 17s4-7 10-7 10 7 10 7-4 7-10 7S6 17 6 17z"/><circle cx="16" cy="17" r="3"/>'),
        'stylesheet' => array('#5870ad', '<path d="M10 6h9l5 5v15H10z"/><path d="M19 6v6h5"/><path d="M13 15h8M13 19h8M13 23h5"/>'),
        'system' => array('#777777', '<circle cx="16" cy="16" r="4"/><path d="M16 5v4M16 23v4M5 16h4M23 16h4M8.2 8.2l2.8 2.8M21 21l2.8 2.8M23.8 8.2 21 11M11 21l-2.8 2.8"/>'),
        'cupsadmin' => array('#c58a20', '<path d="M10 7h12v4a6 6 0 0 1-12 0z"/><path d="M10 9H7c0 4 2 6 5 6M22 9h3c0 4-2 6-5 6M16 17v5M12 25h8"/>'),
        'cups' => array('#c58a20', '<path d="M10 7h12v4a6 6 0 0 1-12 0z"/><path d="M10 9H7c0 4 2 6 5 6M22 9h3c0 4-2 6-5 6M16 17v5M12 25h8"/>'),
        'grouppageadmin' => array('#4f8a83', '<circle cx="11" cy="12" r="3"/><circle cx="21" cy="12" r="3"/><path d="M6 24c.7-4 3-6 5-6s4.3 2 5 6M16 24c.7-3.5 2.7-5.5 5-5.5 2 0 4.1 1.7 5 5.5"/>'),
        'group' => array('#4f8a83', '<circle cx="11" cy="12" r="3"/><circle cx="21" cy="12" r="3"/><path d="M6 24c.7-4 3-6 5-6s4.3 2 5 6M16 24c.7-3.5 2.7-5.5 5-5.5 2 0 4.1 1.7 5 5.5"/>'),
        'multitrackeradmin' => array('#6b7fb5', '<circle cx="8" cy="16" r="3"/><circle cx="24" cy="9" r="3"/><circle cx="24" cy="23" r="3"/><path d="M11 15l10-5M11 17l10 5"/>'),
        'multitracker' => array('#6b7fb5', '<circle cx="8" cy="16" r="3"/><circle cx="24" cy="9" r="3"/><circle cx="24" cy="23" r="3"/><path d="M11 15l10-5M11 17l10 5"/>'),
        'payadmin' => array('#3b8b63', '<circle cx="16" cy="16" r="9"/><path d="M16 10v12M12 13c0-2 8-2 8 0s-8 1-8 4 8 2 8 0"/>'),
        'pay' => array('#3b8b63', '<circle cx="16" cy="16" r="9"/><path d="M16 10v12M12 13c0-2 8-2 8 0s-8 1-8 4 8 2 8 0"/>'),
        'personsadmin' => array('#9a5c7a', '<circle cx="16" cy="11" r="4"/><path d="M8 25c1-5 4-8 8-8s7 3 8 8"/>'),
        'persons' => array('#9a5c7a', '<circle cx="16" cy="11" r="4"/><path d="M8 25c1-5 4-8 8-8s7 3 8 8"/>'),
        'radioadmin' => array('#b45d4d', '<rect x="7" y="12" width="18" height="11" rx="2"/><path d="M10 12l10-6"/><circle cx="13" cy="17.5" r="2"/><path d="M18 16h5M18 20h4"/>'),
        'radio' => array('#b45d4d', '<rect x="7" y="12" width="18" height="11" rx="2"/><path d="M10 12l10-6"/><circle cx="13" cy="17.5" r="2"/><path d="M18 16h5M18 20h4"/>'),
        'reputationadmin' => array('#ba6f2a', '<path d="M16 6l3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1z"/>'),
        'reputation' => array('#ba6f2a', '<path d="M16 6l3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1z"/>'),
        'userstatusesadmin' => array('#5b7f9a', '<path d="M9 8h14M9 16h14M9 24h14"/><circle cx="6" cy="8" r="1.5"/><circle cx="6" cy="16" r="1.5"/><circle cx="6" cy="24" r="1.5"/>'),
        'statuses' => array('#5b7f9a', '<path d="M9 8h14M9 16h14M9 24h14"/><circle cx="6" cy="8" r="1.5"/><circle cx="6" cy="16" r="1.5"/><circle cx="6" cy="24" r="1.5"/>'),
        'uarchadmin' => array('#8b7f3f', '<rect x="8" y="8" width="16" height="18" rx="2"/><path d="M12 12h8M12 16h8M12 20h5"/>'),
        'uarch' => array('#8b7f3f', '<rect x="8" y="8" width="16" height="18" rx="2"/><path d="M12 12h8M12 16h8M12 20h5"/>'),
        'sitesettingsadmin' => array('#517a9f', '<circle cx="16" cy="16" r="4"/><path d="M16 5v3M16 24v3M5 16h3M24 16h3M8.5 8.5l2 2M21.5 21.5l2 2M23.5 8.5l-2 2M10.5 21.5l-2 2"/>'),
        'site' => array('#517a9f', '<circle cx="16" cy="16" r="4"/><path d="M16 5v3M16 24v3M5 16h3M24 16h3M8.5 8.5l2 2M21.5 21.5l2 2M23.5 8.5l-2 2M10.5 21.5l-2 2"/>'),
    );

    $icon = $icons[$iconKey] ?? $icons['system'];
    $imageHtml = '<span class="admin-menu-icon" style="display:inline-block;width:32px;height:32px;margin-bottom:4px;">'
        . '<svg width="32" height="32" viewBox="0 0 32 32" role="img" aria-hidden="true" style="display:block;">'
        . '<rect x="1" y="1" width="30" height="30" rx="6" fill="#fbfbfb" stroke="#f1d29c" stroke-width="2"/>'
        . '<g fill="none" stroke="' . $icon[0] . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $icon[1] . '</g>'
        . '</svg></span><br />';

    echo '
        <td class="center top w15p">
            <a class="sbab" href="' . $urlSafe . '" title="' . $titleSafe . '">
                ' . $imageHtml . '
                <b>' . $titleSafe . '</b>
            </a>
        </td>
    ';

    $counter++;

    if ($counter >= 6) {
        echo '</tr><tr>';
        $counter = 0;
    }
}

switch ($op) {
    case 'Main':
        echo '
            <div class="mn_wrap">
                <table class="tables2 w100p">
                    <tr>
                        <td class="colhead center" colspan="6">Панель администратора</td>
                    </tr>
                    <tr>
        ';

        $linksDir = 'admin/links';

        if (is_dir($linksDir)) {
            $files = scandir($linksDir);

            if ($files !== false) {
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }

                    if (preg_match('/\.php$/i', $file)) {
                        require_once $linksDir . '/' . $file;
                    }
                }
            }
        }

        echo '
                    </tr>
                    <tr>
                        <td class="colhead center" colspan="6">&nbsp;</td>
                    </tr>
                </table>
            </div>
        ';
        break;

    default:
        $modulesDir = 'admin/modules';

        if (is_dir($modulesDir)) {
            $files = scandir($modulesDir);

            if ($files !== false) {
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }

                    if (preg_match('/\.php$/i', $file)) {
                        require_once $modulesDir . '/' . $file;
                    }
                }
            }
        }
        break;
}

?>
