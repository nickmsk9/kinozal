<?php

if (!defined('BLOCK_FILE')) {
    header('Location: ../index.php');
    exit;
}

global $tracker_lang;

$content = '';

$is_admin = get_user_class() >= UC_ADMINISTRATOR;
$returnto = urlencode($_SERVER['REQUEST_URI'] ?? $_SERVER['PHP_SELF'] ?? 'index.php');

$news_title = $tracker_lang['news'] ?? 'Новости';
$create_title = $tracker_lang['create'] ?? 'создать';
$no_news = $tracker_lang['no_news'] ?? 'Новостей нет';

$blocktitle = $news_title;

if ($is_admin) {
    $blocktitle .= " <span class=\"small\">- [<a class=\"altlink\" href=\"news.php\"><b>" . $create_title . "</b></a>]</span>";
}

$resource = sql_query("
    SELECT id, added, subject, body
    FROM news
    WHERE added > DATE_SUB(NOW(), INTERVAL 45 DAY)
    ORDER BY added DESC
    LIMIT 10
") or sqlerr(__FILE__, __LINE__);

$content .= "<script type=\"text/javascript\" src=\"js/show_hide.js\"></script>\n";

$content .= "<div class=\"mn2_content\" style=\"width:100%;\">";
$content .= "<div class=\"bx1\" style=\"width:100%; box-sizing:border-box;\">";

if (mysqli_num_rows($resource) > 0) {
    $content .= "<ul class=\"men\">\n";

    $i = 0;

    while ($array = mysqli_fetch_assoc($resource)) {
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
            $content .= "[<a class=\"altlink\" href=\"news.php?action=edit&amp;newsid=" . $news_id . "&amp;returnto=" . $returnto . "\"><b>E</b></a>]";
            $content .= " ";
            $content .= "[<a class=\"altlink\" href=\"news.php?action=delete&amp;newsid=" . $news_id . "&amp;returnto=" . $returnto . "\"><b>D</b></a>]";
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