<?

require_once("include/bittorrent.php");

dbconn();
loggedinorreturn();
tracker_auth_schema_upgrade();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
	http_response_code(405);
	header('Allow: POST');
	exit('Method Not Allowed');
}

tracker_require_form_token('POST');

function bark($msg) {
	stderr("Произошла ошибка", $msg);
}

function profile_check_password($password) {
	global $CURUSER;
	return tracker_password_verify($password, $CURUSER["secret"], $CURUSER["passhash"]);
}

function profile_require_password($password) {
	if ($password === '' || !profile_check_password($password)) {
		bark("Вы ввели неправильный пароль.");
	}
}

function profile_redirect($extra = '') {
	global $DEFAULTBASEURL;
	header("Location: $DEFAULTBASEURL/my.php?edited=1" . $extra);
	exit;
}

function profile_ensure_avatar_column() {
	$res = sql_query("SHOW COLUMNS FROM users LIKE 'avatar'") or sqlerr(__FILE__, __LINE__);
	$row = mysqli_fetch_assoc($res);
	$type = isset($row['Type']) ? strtolower((string)$row['Type']) : '';

	if (preg_match('/varchar\((\d+)\)/', $type, $m) && (int)$m[1] < 500) {
		sql_query("ALTER TABLE users MODIFY avatar varchar(500) NOT NULL default ''") or sqlerr(__FILE__, __LINE__);
	}
}

$act = isset($_GET["act"]) ? (int)$_GET["act"] : 1;
$updateset = array();

if ($act === 1) {
	profile_require_password((string)($_POST["psw"] ?? ""));

	$country = (int)($_POST["country"] ?? 0);
	$gender = (string)($_POST["gender"] ?? "1");
	$year = (int)($_POST["bday_year"] ?? 0);
	$month = (int)($_POST["bday_month"] ?? 0);
	$day = (int)($_POST["bday_day"] ?? 0);
	$city = trim((string)($_POST["sr_citys"] ?? ""));
	$favorite_movie = trim((string)($_POST["sr_film"] ?? ""));
	$favorite_persons = trim((string)($_POST["sr_persons"] ?? ""));

	if ($gender !== "1" && $gender !== "2") {
		$gender = "1";
	}
	if ($country > 0) {
		$updateset[] = "country = $country";
	}
	if (!checkdate($month, $day, $year)) {
		bark("Похоже, вы указали неверную дату рождения.");
	}
	if (mb_strlen($city, "UTF-8") > 100) {
		bark("Название города слишком длинное (макс. 100 символов).");
	}
	if (mb_strlen($favorite_movie, "UTF-8") > 255) {
		bark("Название любимого фильма слишком длинное (макс. 255 символов).");
	}
	if (mb_strlen($favorite_persons, "UTF-8") > 255) {
		bark("Поле любимых персон слишком длинное (макс. 255 символов).");
	}

	$birthday = sprintf("%04d-%02d-%02d", $year, $month, $day);
	$updateset[] = "gender = " . sqlesc($gender);
	$updateset[] = "birthday = " . sqlesc($birthday);
	$updateset[] = "city = " . sqlesc(htmlspecialchars_uni($city));
	$updateset[] = "favorite_movie = " . sqlesc(htmlspecialchars_uni($favorite_movie));
	$updateset[] = "favorite_persons = " . sqlesc(htmlspecialchars_uni($favorite_persons));
} elseif ($act === 2) {
	$avatar = trim((string)($_POST["avatar"] ?? ""));

	if ($avatar !== '') {
		if (mb_strlen($avatar, "UTF-8") > 500) {
			bark("Ошибка! Длина ссылки превышает 500 символов.");
		}
		if (!preg_match('#^https://.+\.(jpe?g|png|gif)(\?.*)?$#i', $avatar)) {
			bark("Ошибка! Укажите HTTPS ссылку на картинку JPG, JPEG, PNG или GIF.");
		}
	}

	profile_ensure_avatar_column();
	$updateset[] = "avatar = " . sqlesc($avatar);
} elseif ($act === 10) {
	profile_require_password((string)($_POST["psw"] ?? ""));
	tracker_revoke_user_passkeys((int)$CURUSER["id"]);
} elseif ($act === 11) {
	profile_require_password((string)($_POST["psw"] ?? ""));
	$parked = (($_POST["parked"] ?? "no") === "yes") ? "yes" : "no";
	$updateset[] = "parked = " . sqlesc($parked);
} elseif ($act === 12) {
	$oldpassword = (string)($_POST["pass"] ?? "");
	$newpassword = (string)($_POST["chpass"] ?? "");
	$passagain = (string)($_POST["passagain"] ?? "");

	profile_require_password($oldpassword);
	if (strlen($newpassword) < 6) {
		bark("Извините, пароль слишком короткий (минимум 6 символов).");
	}
	if (strlen($newpassword) > 40) {
		bark("Извините, пароль слишком длинный (максимум 40 символов).");
	}
	if ($newpassword !== $passagain) {
		bark("Пароли не совпадают. Попробуйте еще раз.");
	}

	$sec = mksecret();
	$passhash = tracker_password_hash($newpassword);
	$updateset[] = "secret = " . sqlesc($sec);
	$updateset[] = "passhash = " . sqlesc($passhash);
	logincookie($CURUSER["id"], $passhash);
} elseif ($act === 13) {
	$email = trim((string)($_POST["mail"] ?? ""));
	$emailagain = trim((string)($_POST["mailagain"] ?? ""));

	if ($email === '' || $email !== $emailagain) {
		bark("Почтовые адреса не совпадают.");
	}
	if (!validemail($email)) {
		bark("Это не похоже на настоящий E-Mail.");
	}
	if ($email === $CURUSER["email"]) {
		profile_redirect();
	}

	$r = sql_query("SELECT id FROM users WHERE email = " . sqlesc($email) . " AND id <> " . (int)$CURUSER["id"]) or sqlerr(__FILE__, __LINE__);
	if (mysqli_num_rows($r) > 0) {
		bark("Этот e-mail адрес уже используется одним из пользователей трекера.");
	}

	$sec = mksecret();
	$hash = md5($sec . $email . $sec);
	$updateset[] = "editsecret = " . sqlesc($sec);
	$updateset[] = "editsecret_expires = NULL";
	$thishost = $_SERVER["HTTP_HOST"];
	$obemail = urlencode($email);
	$body = "Для подтверждения смены почты перейдите по ссылке:\n\nhttp://$thishost/confirmemail.php?id=" . (int)$CURUSER["id"] . "&hash=$hash&email=$obemail\n";
	sent_mail($email, $GLOBALS["SITENAME"], $GLOBALS["SITEEMAIL"], "Изменение настроек профиля на $thishost", $body, false);

	sql_query("UPDATE users SET " . implode(", ", $updateset) . " WHERE id = " . (int)$CURUSER["id"]) or sqlerr(__FILE__, __LINE__);
	profile_redirect("&mailsent=1");
} else {
	bark("Неизвестное действие.");
}

if (!empty($updateset)) {
	sql_query("UPDATE users SET " . implode(", ", $updateset) . " WHERE id = " . (int)$CURUSER["id"]) or sqlerr(__FILE__, __LINE__);
}

profile_redirect();

?>
