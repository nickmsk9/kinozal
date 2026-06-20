<?php

require_once __DIR__ . '/include/bittorrent.php';
require_once __DIR__ . '/include/groupex.php';

dbconn(false);
loggedinorreturn();
groups_ensure_schema();

$userid = (int)$CURUSER['id'];

$res = sql_query("
	SELECT g.*, gm.status AS member_status, gm.role AS member_role, gm.added_at AS member_added
	FROM groupex_members AS gm
	INNER JOIN groupex_groups AS g ON g.id = gm.group_id
	WHERE gm.userid = $userid
	  AND gm.status IN ('member', 'pending', 'invited')
	  AND g.visible = 'yes'
	ORDER BY FIELD(gm.status, 'member', 'invited', 'pending'), g.name
") or sqlerr(__FILE__, __LINE__);
$groups = array();
while ($group = mysqli_fetch_assoc($res)) {
	$groups[] = $group;
}
if ($groups) {
	groups_prefetch_bookmarks(array_column($groups, 'id'), $userid);
}

$hide_right_blocks = true;
stdhead('Мои группы');

?>
<div class="mn_wrap">
	<?php groups_profile_menu_html(); ?>
	<div class="mn1_content">
		<div class="bx1 u2">
			<a href="/userdetails.php?id=<?= (int)$CURUSER['id'] ?>" class="u<?= (int)$CURUSER['class'] ?>"><?= groups_h($CURUSER['username']) ?></a>
		</div>
		<div class="bx1 justify">
			<span class="u2">Мои группы</span>
			- Здесь представлены группы, в которых Вы состоите, получили приглашение или подали заявку на вступление. Весь список групп Вы можете посмотреть
			<a href="/groupexlist.php" class="sba">здесь</a>.
		</div>
		<div class="bx1 u2">
			<span class="bulet"></span>
			Список Ваших групп
		</div>
		<div class="bx2_0">
			<?php
			foreach ($groups as $group) {
				groups_group_card($group, 'mine');
			}
			if (!$groups) {
				echo '<div class="pad10x10 center">Вы пока не состоите ни в одной группе.</div>';
			}
			?>
		</div>
	</div>
	<div class="clear"></div>
</div>
<?php
stdfoot();

?>
