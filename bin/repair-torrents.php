<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$args = array_slice($argv, 1);
$delete_orphans = in_array('--delete-orphan-files', $args, true);
if (in_array('--help', $args, true) || in_array('-h', $args, true)) {
    fwrite(STDOUT, "Usage: php bin/repair-torrents.php [--delete-orphan-files]\n");
    fwrite(STDOUT, "Checks DB/filesystem consistency for torrents/*.torrent.\n");
    exit(0);
}

require_once dirname(__DIR__) . '/include/bittorrent.php';
require_once ROOT_PATH . 'include/BDecode.php';
require_once ROOT_PATH . 'include/BEncode.php';
require_once ROOT_PATH . 'include/upload.php';

try {
    dbconn(false, true);
} catch (Throwable $e) {
    fwrite(STDERR, "db connection failed: " . $e->getMessage() . "\n");
    exit(2);
}

function repair_torrent_hash_string($value)
{
    $value = (string)$value;
    if (preg_match('/^[0-9a-f]{40}$/i', $value)) {
        return strtolower($value);
    }
    return strtolower(bin2hex($value));
}

function repair_torrent_file_hash($path)
{
    $dict = bdecode((string)file_get_contents($path));
    if (!is_array($dict) || empty($dict['info']) || !is_array($dict['info'])) {
        return false;
    }

    return strtolower(sha1(BEncode($dict['info'])));
}

$rows = array();
$res = sql_query("SELECT id, name, filename, info_hash FROM torrents ORDER BY id ASC") or sqlerr(__FILE__, __LINE__);
while ($row = mysqli_fetch_assoc($res)) {
    $rows[(int)$row['id']] = $row;
}

$dir = rtrim((string)$torrent_dir, '/\\');
$files = array();
foreach (glob($dir . '/*.torrent') ?: array() as $path) {
    if (preg_match('/^(\d+)\.torrent$/', basename($path), $m)) {
        $files[(int)$m[1]] = $path;
    }
}

$missing_files = array();
$invalid_files = array();
$hash_mismatches = array();
$checked_files = 0;

foreach ($rows as $id => $row) {
    $path = $files[$id] ?? upload_torrent_file_path($id);
    if (!is_file($path)) {
        $missing_files[] = array($id, $row['name'], $row['filename']);
        continue;
    }

    $checked_files++;
    $file_hash = repair_torrent_file_hash($path);
    if ($file_hash === false) {
        $invalid_files[] = array($id, $row['name'], $path);
        continue;
    }

    $db_hash = repair_torrent_hash_string($row['info_hash']);
    if ($db_hash !== '' && $db_hash !== $file_hash) {
        $hash_mismatches[] = array($id, $row['name'], $db_hash, $file_hash);
    }
}

$orphan_files = array();
foreach ($files as $id => $path) {
    if (!isset($rows[$id])) {
        $orphan_files[] = array($id, $path);
    }
}

fwrite(STDOUT, "torrent repair check\n");
fwrite(STDOUT, "db rows: " . count($rows) . "\n");
fwrite(STDOUT, "files: " . count($files) . "\n");
fwrite(STDOUT, "checked file hashes: $checked_files\n");
fwrite(STDOUT, "missing files: " . count($missing_files) . "\n");
fwrite(STDOUT, "orphan files: " . count($orphan_files) . "\n");
fwrite(STDOUT, "invalid files: " . count($invalid_files) . "\n");
fwrite(STDOUT, "hash mismatches: " . count($hash_mismatches) . "\n");

foreach ($missing_files as $item) {
    fwrite(STDOUT, "missing-file id={$item[0]} db_filename=\"{$item[2]}\" name=\"{$item[1]}\"\n");
}
foreach ($orphan_files as $item) {
    fwrite(STDOUT, "orphan-file id={$item[0]} path=\"{$item[1]}\"\n");
    if ($delete_orphans && is_file($item[1])) {
        fwrite(STDOUT, @unlink($item[1]) ? "deleted-orphan id={$item[0]}\n" : "delete-failed id={$item[0]}\n");
    }
}
foreach ($invalid_files as $item) {
    fwrite(STDOUT, "invalid-file id={$item[0]} path=\"{$item[2]}\" name=\"{$item[1]}\"\n");
}
foreach ($hash_mismatches as $item) {
    fwrite(STDOUT, "hash-mismatch id={$item[0]} db={$item[2]} file={$item[3]} name=\"{$item[1]}\"\n");
}

$has_errors = $missing_files || $orphan_files || $invalid_files || $hash_mismatches;
exit($has_errors ? 1 : 0);
