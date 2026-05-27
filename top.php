<?php

require_once("include/bittorrent.php");

dbconn(false);
parked();
loggedinorreturn();

stdhead("Топ раздач");

echo <<<HTML
<div class="content">
<div class="bx2"><div style="padding:0 5px 7px 0;"><h1><span class=bulet></span><a href="/top.php" class=sbab title="Топ раздач">Топ раздач - Рейтинг лучших раздач трекера</a></h1></div><div class="mn1_menu"><form method='get' action='/top.php' name='br_top' id='br_top'>
<ul class='men'>
<li class='img'><a href='/top.php'><img src='/pic/bn/p_top.jpg' height=75 class='block w200'></a></li>
<li class='tp'>Выбор топа по жанрам</li>
<li class=img><span class=w100p><input type=text name=j value="" class=w100p>
</span></li>
<li class='tp'>Выбор топа по категориям</li>
<li class=img><select class='w100p styled' name=t size=15>
<option value=0 selected>Избранные раздачи</option>
<option value=1 >Избранные фильмы</option>
<option value=101 >|- Комедии</option>
<option value=102 >|- Фантастика, фэнтези</option>
<option value=103 >|- Ужас, мистика</option>
<option value=104 >|- Боевик, военный</option>
<option value=105 >|- Триллер, детектив</option>
<option value=106 >|- Драма, мелодрама</option>
<option value=107 >|- Наше кино</option>
<option value=108 >|- Детский, семейный</option>
<option value=110 >|- Приключения</option>
<option value=111 >|- Исторический</option>
<option value=112 >|- Документальный</option>
<option value=113 >|- Классика, театр, опера, балет</option>
<option value=115 >|- Концерты</option>
<option value=116 >|- Спорт</option>
<option value=2 >Избранные мультфильмы</option>
<option value=21 >|- Русские</option>
<option value=22 >|- Буржуйские</option>
<option value=23 >|- Аниме</option>
<option value=3 >Избранные сериалы</option>
<option value=31 >|- Русские</option>
<option value=32 >|- Буржуйские</option>
<option value=4 >Топ Музыки</option>
<option value=41 >|- Русская</option>
<option value=42 >|- Буржуйская</option>
<option value=44 >|- Сборники</option>
<option value=43 >|- Классическая</option>
<option value=5 >Библиотека</option>
<option value=6 >Избранные аудиокниги</option>
<option value=7 >Избранные игры</option>
<option value=8 >Избранные программы</option>
</select></li>
<li class=img><dl><dt>Год выпуска</dt><dd><span class=sw100><select class='w100 styled' name='d'>
<option value=0 selected>все года</option>
<option value=14 >2024-2026</option>
<option value=13 >2021-2023</option>
<option value=11 >2018-2020</option>
<option value=10 >2015-2017</option>
<option value=1 >2012-2014</option>
<option value=2 >2009-2011</option>
<option value=3 >2006-2008</option>
<option value=4 >2001-2005</option>
<option value=5 >1996-2000</option>
<option value=6 >1992-1995</option>
<option value=7 >1982-1991</option>
<option value=8 >1972-1981</option>
<option value=9 >1951-1971</option>
</select></span></dd></dl>
<dl><dt>Страна</dt><dd><span class=sw100><select class='w100 styled' name='k'>
<option value=0 selected>все страны</option>
<option value=1 >Россия</option>
<option value=2 >США</option>
<option value=3 >СССР</option>
<option value=4 >Франция</option>
<option value=5 >Германия</option>
<option value=6 >Италия</option>
<option value=7 >Великобритания</option>
</select></span></dd></dl>
<dl><dt>Формат</dt><dd><span class=sw100><select class='w100 styled' name='f'>
<option value=0 selected>все форматы</option>
<option value=2 >HD</option>
<option value=5 >4К</option>
<option value=4 >3D</option>
<option value=3 >LossLess</option>
</select></span></dd></dl>
<dl><dt>Залит</dt><dd><span class=sw100><select class='w100 styled' name='w'>
<option value=0 selected>за все время</option>
<option value=1 >за неделю</option>
<option value=2 >за месяц</option>
<option value=3 >за 3 месяца</option>
</select></span></dd></dl>
<dl><dt>Сортировать</dt><dd><span class=sw100><select class='w100 styled' name='s'>
<option value=0 selected>по сидам</option>
<option value=1 >по пирам</option>
<option value=2 >по комм.</option>
</select></span></dd></dl></li>
<li class=img><input type=submit value='Перестроить топ' class='buttonS w200'></li>
<li class='tp'>Подборки для Вас</li>
<li class='justify lnks_tobrs'>Новогодний, Netflix, фильмы о спорте, лучший фильм Оскар, флора и фауна, мореокеан, ВОВ, Walt Disney Pictures, HBO, Marvel, Pixar, дорама, экранизация, студия Мельница, Ленфильм, Мосфильм, Союзмультфильм</li>
<li class='tp'>Информация</li>
<li class='justify'><p>Топ раздач - Автоматически обновляемый рейтинг лучших раздач. Надеемся, Вам будет интересна подборка популярных раздач.</p></li>
</ul>
</form>
<script type="text/javascript">
$(".lnks_tobrs").each(function(index) {
	var str2_array = [];
	var str_array = $(this).html().split(",");
	for(i=0; i < str_array.length; i++) {
		str_array[i] = str_array[i].trim();
		var xm = str_array[i].split(" ");
		if(xm.length > 0) {
	  		str_array[i] = str_array[i].replace(/"/ig, '').trim();
			str2_array[i] = '<a href="/top.php?j='+str_array[i]+'" class="sba">'+str_array[i]+'</a>';
		} else str2_array[i] = str_array[i];
	}
	$(this).html(str2_array.join(", "));
});
</script>
</div><div class='mn1_content'><div class=pad0x0x5x0><ul class=lis><li class=mn><a href="/top.php?t=0&d=0&f=0&c=0&k=0&j=&s=0" title="Топ раздач">Топ раздач</a></li><li><a href="/top.php?t=0&d=0&f=0&c=0&k=0&j=&s=0&w=1" title="Топ раздач недели">Топ раздач недели</a></li><li><a href="/top.php?t=0&d=0&f=0&c=0&k=0&j=&s=0&w=2" title="Топ раздач месяца">Топ раздач месяца</a></li><li><a href="/top.php?t=0&d=0&f=0&c=0&k=0&j=&s=0&w=6" title="Топ раздач полгода">Топ раздач полгода</a></li></ul></div><div class='bx1 stable'><a href='/details.php?id=2140375' title='Извозчик (1 сезон: 1-16 серии из 16) / 2024 / РУ / WEB-DLRip'><img src='/i/poster/7/5/2140375.jpg' alt=''></a>
<a href='/details.php?id=2139365' title='Проект «Конец света» / Project Hail Mary (IMAX) / 2026 / ДБ, СТ / WEB-DLRip'><img src='/i/poster/3/0/2135330.jpg' alt=''></a>
<a href='/details.php?id=2134297' title='Отпечатки (1 сезон: 1-8 серии из 8) / 2025 / РУ / WEB-DLRip'><img src='/i/poster/6/8/2134268.jpg' alt=''></a>
<a href='/details.php?id=2138246' title='Псы и волки (1 сезон: 1-10 серии из 10) / 2026 / РУ / WEB-DLRip'><img src='/i/poster/4/2/2138242.jpg' alt=''></a>
<a href='/details.php?id=2128508' title='Казнить нельзя помиловать / Mercy / 2026 / ДБ / WEB-DLRip'><img src='/i/poster/3/3/2128433.jpg' alt=''></a>
<a href='/details.php?id=2125738' title='Волчок  / 2025 / РУ / WEB-DLRip'><img src='/i/poster/2/7/2125727.jpg' alt=''></a>
<a href='/details.php?id=2124059' title='Лакомый кусок / The Rip / 2026 / ДБ, СТ / WEB-DLRip'><img src='/i/poster/5/9/2124059.jpg' alt=''></a>
<a href='/details.php?id=2137761' title='Ночной (1 сезон: 1-12 серии из 12) / 2023 / РУ / WEB-DLRip'><img src='/i/poster/6/3/2137763.jpg' alt=''></a>
<a href='/details.php?id=2134180' title='Аватар: Пламя и пепел / Avatar: Fire and Ash / 2025 / ПМ / WEB-DLRip'><img src='/i/poster/6/8/2134068.jpg' alt=''></a>
<a href='/details.php?id=2134372' title='Ограбление в Лос-Анджелесе / Crime 101 / 2026 / ДБ, СТ / WEB-DLRip'><img src='/i/poster/0/9/2134309.jpg' alt=''></a>
<a href='/details.php?id=2134435' title='Грачи (1 сезон: 1-8 серии из 8) / 2026 / РУ / WEB-DLRip'><img src='/i/poster/3/5/2134435.jpg' alt=''></a>
<a href='/details.php?id=2130786' title='Военная машина / War Machine / 2026 / ДБ, СТ / WEB-DLRip'><img src='/i/poster/6/1/2130761.jpg' alt=''></a>
<a href='/details.php?id=2126536' title='Горничная / The Housemaid / 2025 / ЛМ / WEB-DLRip'><img src='/i/poster/7/0/2126470.jpg' alt=''></a>
<a href='/details.php?id=2136843' title='Высшая мера (2 сезон: 1-12 серии из 12) / 2025 / РУ / WEB-DLRip'><img src='/i/poster/4/0/2136840.jpg' alt=''></a>
<a href='/details.php?id=2138128' title='Приговор (1 сезон: 1-10 серии из 10) / 2026 / РУ / WEB-DLRip'><img src='/i/poster/2/0/2138120.jpg' alt=''></a>
<a href='/details.php?id=2138008' title='Новая тёща / 2026 / РУ / WEB-DLRip'><img src='/i/poster/0/6/2138006.jpg' alt=''></a>
<a href='/details.php?id=2135266' title='СМЕРШ 4: Москва 1944 (4 сезон: 1-8 серии из 8) / 2026 / РУ / WEB-DLRip'><img src='/i/poster/6/6/2135266.jpg' alt=''></a>
<a href='/details.php?id=2117465' title='Хищник: Планета смерти / Predator: Badlands / 2025 / ДБ / WEB-DLRip'><img src='/i/poster/6/5/2117465.jpg' alt=''></a>
<a href='/details.php?id=2130593' title='Молодой Шерлок (1 сезон: 1-8 серии из 8) / Young Sherlock / 2026 / ДБ (Dragon Money Studio) / WEB-DLRip'><img src='/i/poster/4/0/2130640.jpg' alt=''></a>
<a href='/details.php?id=2129512' title='Убежище / Shelter / 2026 / ПМ, СТ / WEB-DLRip'><img src='/i/poster/9/8/2129498.jpg' alt=''></a>
<a href='/details.php?id=2133269' title='На помощь! / Send Help / 2026 / ДБ, СТ / WEB-DLRip'><img src='/i/poster/3/1/2133231.jpg' alt=''></a>
<a href='/details.php?id=2119683' title='Достать ножи: Воскрешение покойника / Wake Up Dead Man: A Knives Out Mystery / 2025 / ДБ, СТ / WEB-DLRip'><img src='/i/poster/6/4/2119564.jpg' alt=''></a>
<a href='/details.php?id=2139421' title='Партизаны (1 сезон: 1-10 серии из 10) / 2025 / РУ / WEB-DLRip'><img src='/i/poster/2/1/2139421.jpg' alt=''></a>
<a href='/details.php?id=2136827' title='Наследник / How to Make a Killing / 2026 / ДБ, СТ / WEB-DLRip'><img src='/i/poster/4/0/2134440.jpg' alt=''></a>
<a href='/details.php?id=2124868' title='Художник (2 сезон: 1-12 серии из 12) / 2025 / РУ / WEB-DLRip'><img src='/i/poster/6/0/2124860.jpg' alt=''></a>
<a href='/details.php?id=2103635' title='F1 / F1: The Movie / 2025 / ДБ, СТ / WEB-DLRip'><img src='/i/poster/9/8/2103398.jpg' alt=''></a>
<a href='/details.php?id=2109507' title='Бар «Один звонок» (1 сезон: 1-7 серии из 7) / 2025 / РУ / WEB-DLRip'><img src='/i/poster/0/5/2109405.jpg' alt=''></a>
<a href='/details.php?id=2131945' title='Князь Андрей (1 сезон: 1-8 серии из 8) / 2026 / РУ / WEB-DLRip'><img src='/i/poster/3/7/2131937.jpg' alt=''></a>
<a href='/details.php?id=2126650' title='Встать на ноги  (1 сезон: 1-8 серии из 8) / 2025 / РУ, СТ / WEB-DLRip'><img src='/i/poster/5/0/2126650.jpg' alt=''></a>
<a href='/details.php?id=2107768' title='Код 3 / Code 3 / 2025 / ДБ / WEB-DLRip'><img src='/i/poster/8/7/2107687.jpg' alt=''></a>
<a href='/details.php?id=2131516' title='Удачи, веселья, не сдохни / Good Luck, Have Fun, Don&#039;t Die / 2025 / ПМ, СТ / WEB-DLRip'><img src='/i/poster/1/3/2131513.jpg' alt=''></a>
<a href='/details.php?id=2140512' title='Мумия / The Mummy (Lee Cronin&#039;s The Mummy) / 2026 / ПМ / WEB-DLRip'><img src='/i/poster/1/2/2140512.jpg' alt=''></a>
<a href='/details.php?id=2054139' title='Дикий робот / The Wild Robot / 2024 / ПМ, СТ / WEB-DLRip'><img src='/i/poster/3/9/2054139.jpg' alt=''></a>
<a href='/details.php?id=2103004' title='Миссия невыполнима: Финальная расплата / Mission: Impossible - The Final Reckoning (IMAX) / 2025 / ДБ, СТ / WEB-DLRip'><img src='/i/poster/2/6/2102926.jpg' alt=''></a>
<a href='/details.php?id=2139071' title='Есть только МиГ / 2025 / РУ / WEB-DLRip'><img src='/i/poster/4/5/2139045.jpg' alt=''></a>
<a href='/details.php?id=2120012' title='Август / 2025 / РУ, СТ / WEB-DLRip'><img src='/i/poster/0/8/2120008.jpg' alt=''></a>
<a href='/details.php?id=2140660' title='Бороуз (Городок) (1 сезон: 1-8 серии из 8) / The Boroughs / 2026 / ДБ (Videofilm Int.), СТ / WEB-DLRip'><img src='/i/poster/3/4/2140634.jpg' alt=''></a>
<a href='/details.php?id=2125825' title='Опасный дуэт / The Wrecking Crew / 2026 / ДБ, СТ / WEB-DLRip'><img src='/i/poster/2/5/2125825.jpg' alt=''></a>
<a href='/details.php?id=1026989' title='Камасутра (Позиции Цветущего сада) / Kama Sutra - positions of a blossoming garden / 2005 / ПО / DVDRip'><img src='/i/poster/8/9/1026989.jpg' alt=''></a>
<a href='/details.php?id=2080359' title='Поток / Straume (Flow) / 2024 / БП, СТ / BDRip'><img src='/i/poster/5/9/2080359.jpg' alt=''></a>
<a href='/details.php?id=2138221' title='Королёк моей любви / 2026 / РУ / WEB-DLRip'><img src='/i/poster/7/1/2138171.jpg' alt=''></a>
<a href='/details.php?id=2094356' title='Новичок / The Amateur / 2025 / ДБ, СТ / WEB-DLRip'><img src='/i/poster/9/6/2094296.jpg' alt=''></a>
<a href='/details.php?id=2137546' title='Вершина (На вершине) / Apex / 2026 / ЛМ, СТ / WEB-DLRip'><img src='/i/poster/2/3/2137323.jpg' alt=''></a>
<a href='/details.php?id=2138561' title='Кукушонок (1 сезон: 1-12 серии из 12) / 2025 / РУ / WEB-DLRip'><img src='/i/poster/6/1/2138561.jpg' alt=''></a>
<a href='/details.php?id=2133571' title='Буратино / 2025 / РУ / WEB-DLRip'><img src='/i/poster/6/0/2133560.jpg' alt=''></a>
<a href='/details.php?id=2140439' title='Берлин (Бумажный дом: Дама с горностаем) (2 сезон: 1-8 серии из 8) / Berl&#237;n y la dama del armi&#241;o / 2026 / ДБ, СТ / WEB-DLRip'><img src='https://s1.hostingkartinok.com/uploads/images/2026/05/62e63d31952e24411e668e33eeecff6e.jpg' alt=''></a>
<a href='/details.php?id=2126087' title='Savage - Eternity / Disco / 2026 / MP3'><img src='/i/poster/8/7/2126087.jpg' alt=''></a>
<a href='/details.php?id=2114850' title='Франкенштейн / Frankenstein / 2025 / ДБ, СТ / WEB-DLRip'><img src='/i/poster/1/9/2114819.jpg' alt=''></a>
<a href='/details.php?id=2042711' title='Высшая мера (1 сезон: 1-12 серии из 12) / 2022 / РУ, СТ / WEB-DLRip'><img src='/i/poster/1/1/2042711.jpg' alt=''></a>
<a href='/details.php?id=2115859' title='Битва за битвой / One Battle After Another / 2025 / ДБ, СТ / WEB-DLRip'><img src='/i/poster/0/4/2115804.jpg' alt=''></a>
</div><div class="paginator"><ul><li class="current"><a href="?t=0&d=0&f=0&c=0&k=0&j=&s=0&w=0&page=0">1</a></li><li><a href="?t=0&d=0&f=0&c=0&k=0&j=&s=0&w=0&page=1">2</a></li><li><a href="?t=0&d=0&f=0&c=0&k=0&j=&s=0&w=0&page=2">3</a></li><li><a href="?t=0&d=0&f=0&c=0&k=0&j=&s=0&w=0&page=3">4</a></li><li><a href="?t=0&d=0&f=0&c=0&k=0&j=&s=0&w=0&page=4">5</a></li><li class="dots">...</li><li><a href="?t=0&d=0&f=0&c=0&k=0&j=&s=0&w=0&page=19">20</a></li><li><a rel="next" href="?t=0&d=0&f=0&c=0&k=0&j=&s=0&w=0&page=1">Вперед</a></li></ul></div></div><div class='clr'></div></div>
<div class='bx2_0'><ul class='men'><li class='tp2 center'>Кто ОнЛайн здесь, на этой странице [ <a class=sba href='/pay.php'>помочь проекту</a> ]</li><li><div class='pad5x5'>
<script type="text/javascript">
data="^18026096|nicksvd|2||||^20693621|Colinz|1||||^19650781|rizly2006|2||||^2644871|mausi1974|2|1|||^20553826|ant1967|2||||^800128|julija2222|2|1|||^20568611|seregachek|2||||^20145465|allini01|1|1|||^3696594|snooker7|2||||^19339219|pantil|1||||^20601914|yak170|1||||^1932188|zavadsk|2||||^21062700|rumpelll|1||||^18009893|johnytech|1||||^4166989|vbrekf|2||||^3145804|Boroda218|2||||^19847518|makuha73|2||||^5122531|valtosar|2||||^1556023|Das2|2||||^20853636|tigra0303|1||||^21174434|MaSh|0||||^20900240|rustamka3891|1||||^19200813|rolandas01|2||||^2923608|bastu|2|1|||^6765483|Бусинка2001|2||||^2684194|Oleg1968|2||||^20701437|sasha2877|1||||^21184527|abellellll|0|1|||^17090083|Smith1311|2||||^21255795|Chiters0|0||||^2852504|shtirliz051|2||||^1414339|lokomotivs|2||||^20735777|Bratec100k|2||||^18585062|DiKir007|2||||^3793333|dnk1965|2||||^19796167|Velanat|2||||^5461537|Delfino77777|2||||^21240991|verbec|1||||^17426898|Вандакуров|2||||^17722809|vitalka29|2||||^20436665|alex590659|2||||^7279531|сплюшка|2||||^6477984|kazantipan|2||||^16810790|maslakof|2||||^18613457|mofs71|2||||^7512647|Александр727|2||||^18266098|Tomy193aka45|2||||^20732265|GRxOM|1||||^5287991|toljan1903|2||||^17442907|ld140868|1|1|||^6222801|janiso21|1||||^21243552|fju269|1||||^16989919|Тобысь|2||||^20243297|geny1977|1|1|||^20297213|Rockets12|2||||^";
 draw_usersarray();
</script></div></li></ul></div>
<div id="movie_video"></div>
</div><div class="clr"></div>
</div>
HTML;

stdfoot();

?>
