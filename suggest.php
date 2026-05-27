<?php
require("include/bittorrent.php");
dbconn(false);
loggedinorreturn();

header("Content-Type: text/html; charset=" . $tracker_lang['language_charset']);

$query = $_GET['q'] ?? '';
if (strlen($query) > 3) {
    // Получаем соединение с БД, предполагая, что dbconn() создает глобальную переменную $___mysqli_ston
    // Если используется другой идентификатор, замените на актуальный для вашего движка
    $mysqli = $GLOBALS['___mysqli_ston'] ?? null;
    if (!$mysqli) {
        // fallback: попытаемся использовать стандартную переменную из bittorrent.php (например, $db)
        global $db; // некоторые сборки
        $mysqli = $db ?? null;
    }
    if (!$mysqli) {
        die('Ошибка соединения с базой данных');
    }

    // Преобразуем строку запроса для двух вариантов поиска
    $term1 = '%' . str_replace(' ', '.', $query) . '%'; // пробелы -> точки
    $term2 = '%' . str_replace('.', ' ', $query) . '%'; // точки -> пробелы

    $sql = "SELECT name FROM torrents WHERE name LIKE ? OR name LIKE ? ORDER BY id DESC LIMIT 10";
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('ss', $term1, $term2);
        $stmt->execute();
        $result = $stmt->get_result();

        $names = [];
        while ($row = $result->fetch_assoc()) {
            $name = trim(str_replace("\t", '', $row['name']));
            $names[] = $name;
        }
        echo implode("\r\n", $names);
        $stmt->close();
    }
}
?>