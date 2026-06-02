<?php

if (!defined('BLOCK_FILE')) {
    header('Location: ../index.php');
    exit;
}

global $tracker_lang;

$content = '';

$can_add_news = get_user_class() >= UC_MODERATOR;
$is_admin = get_user_class() >= UC_ADMINISTRATOR;
$returnto = urlencode($_SERVER['REQUEST_URI'] ?? $_SERVER['PHP_SELF'] ?? 'index.php');

$news_title = $tracker_lang['news'] ?? 'Новости';
$create_title = $tracker_lang['create'] ?? 'создать';
$no_news = $tracker_lang['no_news'] ?? 'Новостей нет';

$blocktitle = $news_title;

if ($can_add_news) {
    $blocktitle .= " <span class=\"small\">- [<a class=\"altlink\" href=\"news.php\"><b>Добавить новость</b></a>]</span>";
}

$rows = isset($GLOBALS['index_news']) && is_array($GLOBALS['index_news'])
    ? $GLOBALS['index_news']
    : null;

if ($rows === null) {
    $resource = sql_query("
        SELECT id, added, subject, body
        FROM news
        WHERE added > DATE_SUB(NOW(), INTERVAL 45 DAY)
        ORDER BY added DESC
        LIMIT 10
    ") or sqlerr(__FILE__, __LINE__);

    $rows = array();
    while ($array = mysqli_fetch_assoc($resource)) {
        $rows[] = $array;
    }
}

$content .= "<script type=\"text/javascript\" src=\"js/show_hide.js\"></script>\n";

$content .= "<div class=\"mn2_content\" style=\"width:100%;\">";
$content .= "<div class=\"bx1\" style=\"width:100%; box-sizing:border-box;\">";

if ($can_add_news) {
    $content .= "<ul class=\"men\"><li><span class=\"bulet\"></span><a class=\"sba\" href=\"news.php\"><b>Добавить новость</b></a></li></ul>";
}

if ($rows) {
    $content .= "<ul class=\"men\">\n";

    $i = 0;

    foreach ($rows as $array) {
        $news_id = (int)$array['id'];
        $subject = htmlspecialchars_uni($array['subject']);
        $date = date('d.m.Y', strtotime($array['added']));

        $is_first = ($i === 0);
        $display = $is_first ? 'block' : 'none';
        $icon = $is_first ? 'minus.gif' : 'plus.gif';
        $title = $is_first ? 'Скрыть' : 'Показать';
        $link_class = $is_first ? 'u9' : 'sbab';

        $content .= "<li>";
        $content .= "<span class=\"bulet\"></span>";

        $content .= "<a href=\"javascript:void(0);\" class=\"" . $link_class . "\" onclick=\"show_hide('s" . $news_id . "'); return false;\">";
        $content .= "<img border=\"0\" src=\"pic/" . $icon . "\" id=\"pics" . $news_id . "\" title=\"" . $title . "\" alt=\"\" /> ";
        $content .= $date . " - <b>" . $subject . "</b>";
        $content .= "</a>";

        if ($is_admin) {
            $content .= " <span class=\"small\">";
            $content .= "[<a class=\"altlink\" href=\"news.php?action=edit&amp;newsid=" . $news_id . "&amp;returnto=" . $returnto . "\"><b>Редактировать</b></a>]";
            $content .= " ";
            $content .= "[<a class=\"altlink\" href=\"news.php?action=delete&amp;newsid=" . $news_id . "&amp;returnto=" . $returnto . "\"><b>Удалить</b></a>]";
            $content .= "</span>";
        }

        $content .= "<div id=\"ss" . $news_id . "\" style=\"display: " . $display . "; padding: 5px 0 5px 18px;\">";
        $content .= format_comment($array['body']);
        $content .= "</div>";

        $content .= "</li>\n";

        $i++;
    }

    $content .= "</ul>\n";
} else {
    $content .= "<ul class=\"men\">";
    $content .= "<li><span class=\"bulet\"></span><span class=\"sbab\">" . $no_news . "</span></li>";
    $content .= "</ul>";
}

$content .= "</div>";
$content .= "</div>";

?>
