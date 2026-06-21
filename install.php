<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (function_exists('mysqli_report')) {
	mysqli_report(MYSQLI_REPORT_OFF);
}

function install_h($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function install_post($key, $default = '')
{
	return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}

function install_sql_files()
{
	$files = glob(__DIR__ . '/database/*.sql');

	if (!is_array($files)) {
		return array();
	}

	sort($files, SORT_NATURAL | SORT_FLAG_CASE);

	$result = array();
	foreach ($files as $file) {
		$result[basename($file)] = $file;
	}

	return $result;
}

function install_split_sql($sql)
{
	$sql = preg_replace('/^\xEF\xBB\xBF/', '', (string)$sql);
	$statements = array();
	$buffer = '';
	$length = strlen($sql);
	$quote = null;
	$escape = false;

	for ($i = 0; $i < $length; $i++) {
		$char = $sql[$i];
		$next = ($i + 1 < $length) ? $sql[$i + 1] : '';

		if ($quote === null && $char === '-' && $next === '-') {
			while ($i < $length && $sql[$i] !== "\n") {
				$i++;
			}
			$buffer .= "\n";
			continue;
		}

		if ($quote === null && $char === '#') {
			while ($i < $length && $sql[$i] !== "\n") {
				$i++;
			}
			$buffer .= "\n";
			continue;
		}

		if ($quote === null && $char === '/' && $next === '*') {
			$i += 2;
			while ($i + 1 < $length && !($sql[$i] === '*' && $sql[$i + 1] === '/')) {
				$i++;
			}
			$i++;
			continue;
		}

		if ($quote !== null) {
			$buffer .= $char;

			if ($escape) {
				$escape = false;
				continue;
			}

			if ($char === '\\') {
				$escape = true;
				continue;
			}

			if ($char === $quote) {
				$quote = null;
			}

			continue;
		}

		if ($char === "'" || $char === '"') {
			$quote = $char;
			$buffer .= $char;
			continue;
		}

		if ($char === ';') {
			$statement = trim($buffer);
			if ($statement !== '') {
				$statements[] = $statement;
			}
			$buffer = '';
			continue;
		}

		$buffer .= $char;
	}

	$statement = trim($buffer);
	if ($statement !== '') {
		$statements[] = $statement;
	}

	return $statements;
}

function install_php_file($path, $content)
{
	if (file_put_contents($path, $content, LOCK_EX) === false) {
		throw new RuntimeException('Не удалось записать файл: ' . basename($path));
	}
}

function install_random_string($length)
{
	return substr(bin2hex(random_bytes(max(1, (int)ceil($length / 2)))), 0, $length);
}

function install_create_local_files($mysqlHost, $mysqlPort, $mysqlUser, $mysqlPass, $mysqlDb, $overwrite)
{
	return true;

	$secretsPath = __DIR__ . '/include/secrets.php';
	$configPath = __DIR__ . '/include/config.php';

	if (!$overwrite && (is_file($secretsPath) || is_file($configPath))) {
		throw new RuntimeException('Локальные файлы настроек уже существуют. Включите перезапись, если хотите заменить их.');
	}

	$host = ($mysqlPort > 0 && $mysqlPort !== 3306) ? $mysqlHost . ':' . $mysqlPort : $mysqlHost;
	$secrets = "<?php\n\n"
		. '$mysql_host = ' . var_export($host, true) . ";\n"
		. '$mysql_user = ' . var_export($mysqlUser, true) . ";\n"
		. '$mysql_pass = ' . var_export($mysqlPass, true) . ";\n"
		. '$mysql_db = ' . var_export($mysqlDb, true) . ";\n"
		. '$mysql_charset = \"utf8mb4\";' . "\n";

	$config = "<?php\n\n"
		. '$SITE_ONLINE = true;' . "\n"
		. '$deny_signup = 0;' . "\n"
		. '$use_email_act = 0;' . "\n"
		. '$_COOKIE_SALT = ' . var_export(install_random_string(32), true) . ";\n"
		. "if (!defined('COOKIE_SALT')) {\n"
		. "    define('COOKIE_SALT', \$_COOKIE_SALT);\n"
		. "}\n"
		. "define('KZ_AUTO_MIGRATIONS', false);\n";

	install_php_file($secretsPath, $secrets);
	install_php_file($configPath, $config);
}

function install_import_database(mysqli $db, $sqlFile)
{
	$sql = file_get_contents($sqlFile);
	if ($sql === false) {
		throw new RuntimeException('Не удалось прочитать SQL-файл.');
	}

	$count = 0;
	foreach (install_split_sql($sql) as $statement) {
		if (!$db->query($statement)) {
			throw new RuntimeException('Ошибка импорта SQL: ' . $db->error);
		}
		$count++;
	}

	return $count;
}

function install_create_admin(mysqli $db, $username, $password, $email)
{
	if ($username === '' || $password === '' || $email === '') {
		return false;
	}

	if (strlen($password) < 6) {
		throw new RuntimeException('Пароль администратора должен быть не короче 6 символов.');
	}

	$usernameEsc = $db->real_escape_string($username);
	$res = $db->query("SELECT id FROM users WHERE username = '$usernameEsc' LIMIT 1");
	if (!$res) {
		throw new RuntimeException('Не удалось проверить администратора: ' . $db->error);
	}

	if ($res->fetch_assoc()) {
		return false;
	}

	$secret = install_random_string(20);
	$passhash = password_hash($password, PASSWORD_DEFAULT);
	if (!is_string($passhash) || $passhash === '') {
		throw new RuntimeException('Не удалось создать хэш пароля администратора.');
	}
	$now = date('Y-m-d H:i:s');

	$sql = "
		INSERT INTO users
			(added, last_access, secret, username, passhash, status, email, class, enabled)
		VALUES
			('" . $db->real_escape_string($now) . "',
			 '" . $db->real_escape_string($now) . "',
			 '" . $db->real_escape_string($secret) . "',
			 '" . $db->real_escape_string($username) . "',
			 '" . $db->real_escape_string($passhash) . "',
			 'confirmed',
			 '" . $db->real_escape_string($email) . "',
			 9,
			 'yes')
	";

	if (!$db->query($sql)) {
		throw new RuntimeException('Не удалось создать администратора: ' . $db->error);
	}

	return true;
}

$sqlFiles = install_sql_files();
$messages = array();
$errors = array();

$mysqlHost = install_post('mysql_host', 'localhost');
$mysqlPort = (int)install_post('mysql_port', '3306');
$mysqlUser = install_post('mysql_user', 'root');
$mysqlPass = isset($_POST['mysql_pass']) ? (string)$_POST['mysql_pass'] : '';
$mysqlDb = install_post('mysql_db', 'kinozal');
$sqlName = install_post('sql_file', isset($sqlFiles['database.sql']) ? 'database.sql' : (string)key($sqlFiles));
$adminUser = install_post('admin_user', 'admin');
$adminPass = isset($_POST['admin_pass']) ? (string)$_POST['admin_pass'] : '';
$adminEmail = install_post('admin_email', 'admin@example.com');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	try {
		if ($mysqlHost === '' || $mysqlUser === '' || $mysqlDb === '') {
			throw new RuntimeException('Заполните хост, пользователя MySQL и название базы.');
		}

		if (!isset($sqlFiles[$sqlName])) {
			throw new RuntimeException('Выбранный SQL-файл не найден в папке database.');
		}

		$socket = null;
		$db = @new mysqli($mysqlHost, $mysqlUser, $mysqlPass, '', $mysqlPort, $socket);
		if ($db->connect_errno) {
			throw new RuntimeException('Не удалось подключиться к MySQL: ' . $db->connect_error);
		}

		$db->set_charset('utf8mb4');

		if (!empty($_POST['create_database'])) {
			$dbNameEsc = str_replace('`', '``', $mysqlDb);
			if (!$db->query("CREATE DATABASE IF NOT EXISTS `$dbNameEsc` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
				throw new RuntimeException('Не удалось создать базу: ' . $db->error);
			}
			$messages[] = 'База данных проверена/создана.';
		}

		if (!$db->select_db($mysqlDb)) {
			throw new RuntimeException('Не удалось выбрать базу данных: ' . $db->error);
		}

		if (!empty($_POST['import_database'])) {
			$count = install_import_database($db, $sqlFiles[$sqlName]);
			$messages[] = 'SQL импортирован. Выполнено запросов: ' . $count . '.';
		}

		install_create_local_files($mysqlHost, $mysqlPort, $mysqlUser, $mysqlPass, $mysqlDb, !empty($_POST['overwrite_config']));
		$messages[] = 'Настройки берутся из include/secrets.php и include/config.php. Локальные include/*.local.php больше не используются.';

		if ($adminPass !== '') {
			$created = install_create_admin($db, $adminUser, $adminPass, $adminEmail);
			$messages[] = $created ? 'Администратор создан.' : 'Администратор с таким логином уже существует.';
		}

		$db->close();
		$messages[] = 'Установка завершена. После проверки удалите install.php с сервера.';
	} catch (Throwable $e) {
		$errors[] = $e->getMessage();
	}
}

?><!doctype html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Установка трекера</title>
	<style>
		body { margin: 0; font: 14px Arial, sans-serif; color: #202020; background: #f4f6f8; }
		main { max-width: 760px; margin: 32px auto; padding: 0 16px; }
		form, .box { background: #fff; border: 1px solid #d8dde3; border-radius: 6px; padding: 18px; }
		h1 { margin: 0 0 16px; font-size: 24px; }
		h2 { margin: 22px 0 10px; font-size: 17px; }
		label { display: block; margin: 10px 0 5px; font-weight: bold; }
		input, select { width: 100%; box-sizing: border-box; padding: 8px; border: 1px solid #b9c2cc; border-radius: 4px; }
		.row { display: grid; grid-template-columns: 1fr 120px; gap: 12px; }
		.check { display: flex; gap: 8px; align-items: center; margin: 12px 0; font-weight: normal; }
		.check input { width: auto; }
		button { margin-top: 12px; padding: 9px 16px; border: 0; border-radius: 4px; background: #2f6fed; color: #fff; font-weight: bold; cursor: pointer; }
		.ok { background: #e7f6ea; border-color: #9ad0a5; }
		.err { background: #fdeaea; border-color: #e4a0a0; }
		.small { color: #606a75; font-size: 12px; }
		ul { margin: 8px 0 0 20px; padding: 0; }
	</style>
</head>
<body>
<main>
	<h1>Установка трекера</h1>

	<?php if ($messages): ?>
		<div class="box ok">
			<ul>
				<?php foreach ($messages as $message): ?>
					<li><?= install_h($message) ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ($errors): ?>
		<div class="box err">
			<ul>
				<?php foreach ($errors as $error): ?>
					<li><?= install_h($error) ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<form method="post">
		<h2>MySQL / phpMyAdmin</h2>
		<div class="row">
			<div>
				<label for="mysql_host">Хост</label>
				<input id="mysql_host" name="mysql_host" value="<?= install_h($mysqlHost) ?>" required>
			</div>
			<div>
				<label for="mysql_port">Порт</label>
				<input id="mysql_port" name="mysql_port" value="<?= install_h((string)$mysqlPort) ?>" inputmode="numeric">
			</div>
		</div>

		<label for="mysql_user">Пользователь</label>
		<input id="mysql_user" name="mysql_user" value="<?= install_h($mysqlUser) ?>" required>

		<label for="mysql_pass">Пароль</label>
		<input id="mysql_pass" name="mysql_pass" type="password" value="<?= install_h($mysqlPass) ?>">

		<label for="mysql_db">База данных</label>
		<input id="mysql_db" name="mysql_db" value="<?= install_h($mysqlDb) ?>" required>

		<label class="check">
			<input type="checkbox" name="create_database" value="1" checked>
			Создать базу, если её ещё нет
		</label>

		<h2>Импорт базы</h2>
		<label for="sql_file">SQL-файл из папки database</label>
		<select id="sql_file" name="sql_file">
			<?php foreach ($sqlFiles as $name => $path): ?>
				<option value="<?= install_h($name) ?>"<?= $name === $sqlName ? ' selected' : '' ?>><?= install_h($name) ?></option>
			<?php endforeach; ?>
		</select>
		<p class="small">Сейчас в папке database должен быть один первичный дамп: database.sql.</p>

		<label class="check">
			<input type="checkbox" name="import_database" value="1" checked>
			Импортировать выбранный SQL-файл
		</label>

		<label class="check">
			<input type="checkbox" name="overwrite_config" value="1" checked>
			Перезаписать локальные файлы настроек
		</label>

		<h2>Первый администратор</h2>
		<label for="admin_user">Логин</label>
		<input id="admin_user" name="admin_user" value="<?= install_h($adminUser) ?>">

		<label for="admin_email">E-mail</label>
		<input id="admin_email" name="admin_email" type="email" value="<?= install_h($adminEmail) ?>">

		<label for="admin_pass">Пароль</label>
		<input id="admin_pass" name="admin_pass" type="password" value="<?= install_h($adminPass) ?>">
		<p class="small">Оставьте пароль пустым, если администратора создавать не нужно.</p>

		<button type="submit">Установить</button>
	</form>
</main>
</body>
</html>
