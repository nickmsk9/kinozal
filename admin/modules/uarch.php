<?php

if (!defined('ADMIN_FILE')) {
	die('Illegal File Access');
}

require_once 'include/uarch.php';

function UarchAdmin()
{
	global $admin_file;

	uarch_ensure_schema();

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (isset($_POST['delete_id'])) {
			uarch_delete((int)$_POST['delete_id']);
		} elseif (isset($_POST['toggle_id'])) {
			uarch_set_active((int)$_POST['toggle_id'], (string)($_POST['active'] ?? 'no'));
		}

		header('Location: ' . $admin_file . '.php?op=UarchAdmin');
		exit;
	}

	$rows = uarch_smiles(false, 200);

	echo '<div class="mn_wrap">';
	echo '<table class="tables2 w100p">';
	echo '<tr><td class="colhead center" colspan="6">Архив улыбок</td></tr>';
	echo '<tr>';
	echo '<td class="colhead center">ID</td>';
	echo '<td class="colhead">Пользователь</td>';
	echo '<td class="colhead center">Улыбка</td>';
	echo '<td class="colhead center">Статус</td>';
	echo '<td class="colhead center">Дата</td>';
	echo '<td class="colhead center">Действие</td>';
	echo '</tr>';

	if (!$rows) {
		echo '<tr><td colspan="6" class="center">Улыбок пока нет.</td></tr>';
	}

	foreach ($rows as $row) {
		$id = (int)$row['id'];
		$userid = (int)$row['userid'];
		$username = (string)$row['display_username'];
		$userclass = (int)$row['display_class'];
		$active = (string)$row['active'];

		echo '<tr>';
		echo '<td class="center">' . $id . '</td>';
		echo '<td><a href="/userdetails.php?id=' . $userid . '" class="u' . $userclass . '">' . uarch_h($username) . '</a></td>';
		echo '<td class="center"><a href="' . uarch_h($row['image_url']) . '" target="_blank"><img src="' . uarch_h($row['image_url']) . '" alt="" style="max-width:120px; max-height:90px;"></a></td>';
		echo '<td class="center">' . ($active === 'yes' ? '<span class="green">Показана</span>' : '<span class="red">Скрыта</span>') . '</td>';
		echo '<td class="center">' . uarch_h($row['added']) . '</td>';
		echo '<td class="center">';
		echo '<form method="post" action="' . uarch_h($admin_file) . '.php?op=UarchAdmin" style="display:inline;">';
		echo '<input type="hidden" name="toggle_id" value="' . $id . '">';
		echo '<input type="hidden" name="active" value="' . ($active === 'yes' ? 'no' : 'yes') . '">';
		echo '<input type="submit" class="btn" value="' . ($active === 'yes' ? 'Скрыть' : 'Показать') . '">';
		echo '</form> ';
		echo '<form method="post" action="' . uarch_h($admin_file) . '.php?op=UarchAdmin" style="display:inline;" onsubmit="return confirm(\'Удалить улыбку?\');">';
		echo '<input type="hidden" name="delete_id" value="' . $id . '">';
		echo '<input type="submit" class="btn" value="Удалить">';
		echo '</form>';
		echo '</td>';
		echo '</tr>';
	}

	echo '</table>';
	echo '</div>';
}

switch ($op) {
	case 'UarchAdmin':
		UarchAdmin();
		break;
}

?>
