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

    $imageHtml = '';

    if ($image !== '') {
        $imageSafe = htmlspecialchars($image, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $imageLink = 'admin/pic/' . $imageSafe;

        $imageHtml = '<img src="' . $imageLink . '" alt="' . $titleSafe . '" title="' . $titleSafe . '" /><br />';
    }

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