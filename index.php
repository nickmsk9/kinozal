<?php

/*
// +--------------------------------------------------------------------------+
// | Project:    TBDevYSE - TBDev Yuna Scatari Edition                        |
// +--------------------------------------------------------------------------+
// | This file is part of TBDevYSE. TBDevYSE is based on TBDev,               |
// | originally by RedBeard of TorrentBits, extensively modified by           |
// | Gartenzwerg.                                                             |
// |                                                                          |
// | TBDevYSE is free software; you can redistribute it and/or modify         |
// | it under the terms of the GNU General Public License as published by     |
// | the Free Software Foundation; either version 2 of the License, or        |
// | (at your option) any later version.                                      |
// |                                                                          |
// | TBDevYSE is distributed in the hope that it will be useful,              |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
// | GNU General Public License for more details.                             |
// |                                                                          |
// | You should have received a copy of the GNU General Public License        |
// | along with TBDevYSE; if not, write to the Free Software Foundation,      |
// | Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA            |
// +--------------------------------------------------------------------------+
// |                                               Do not remove above lines! |
// +--------------------------------------------------------------------------+
*/

require_once("include/bittorrent.php");
dbconn(true);

stdhead($tracker_lang['homepage']);

?>

<div align="center"><font class="small"><img src="./themes/<?=$ss_uri;?>/images/ru.gif" width="20" height="15"></font></div>
<p align="justify"><font class="small" style="font-weight: normal;">РџСЂРµРґСѓРїСЂРµР¶РґРµРЅРёРµ! РРЅС„РѕСЂРјР°С†РёСЏ, СЂР°СЃРїРѕР»РѕР¶РµРЅРЅР°СЏ РЅР° РґР°РЅРЅРѕРј СЃРµСЂРІРµСЂРµ, РїСЂРµРґРЅР°Р·РЅР°С‡РµРЅР° РёСЃРєР»СЋС‡РёС‚РµР»СЊРЅРѕ РґР»СЏ С‡Р°СЃС‚РЅРѕРіРѕ РёСЃРїРѕР»СЊР·РѕРІР°РЅРёСЏ РІ РѕР±СЂР°Р·РѕРІР°С‚РµР»СЊРЅС‹С… С†РµР»СЏС… Рё РЅРµ РјРѕР¶РµС‚ Р±С‹С‚СЊ Р·Р°РіСЂСѓР¶РµРЅР°/РїРµСЂРµРЅРµСЃРµРЅР° РЅР° РґСЂСѓРіРѕР№ РєРѕРјРїСЊСЋС‚РµСЂ. РќРё РІР»Р°РґРµР»РµС† СЃР°Р№С‚Р°, РЅРё С…РѕСЃС‚РёРЅРі-РїСЂРѕРІР°Р№РґРµСЂ, РЅРё Р»СЋР±С‹Рµ РґСЂСѓРіРёРµ С„РёР·РёС‡РµСЃРєРёРµ РёР»Рё СЋСЂРёРґРёС‡РµСЃРєРёРµ Р»РёС†Р° РЅРµ РјРѕРіСѓС‚ РЅРµСЃС‚Рё РЅРёРєР°РєРѕР№ РѕС‚РІРµСЃС‚РІРµРЅРЅРѕСЃС‚Рё Р·Р° Р»СЋР±РѕРµ РёСЃРїРѕР»СЊР·РѕРІР°РЅРёРµ РјР°С‚РµСЂРёР°Р»РѕРІ РґР°РЅРЅРѕРіРѕ СЃР°Р№С‚Р°. Р’С…РѕРґСЏ РЅР° СЃР°Р№С‚, Р’С‹, РєР°Рє РїРѕР»СЊР·РѕРІР°С‚РµР»СЊ, С‚РµРј СЃР°РјС‹Рј РїРѕРґС‚РІРµСЂР¶РґР°РµС‚Рµ РїРѕР»РЅРѕРµ Рё Р±РµР·РѕРіРѕРІРѕСЂРѕС‡РЅРѕРµ СЃРѕРіР»Р°СЃРёРµ СЃРѕ РІСЃРµРјРё СѓСЃР»РѕРІРёСЏРјРё РёСЃРїРѕР»СЊР·РѕРІР°РЅРёСЏ. РђРІС‚РѕСЂС‹ РїСЂРѕРµРєС‚Р° РѕС‚РЅРѕСЃСЏС‚СЃСЏ РѕСЃРѕР±Рѕ РЅРµРіР°С‚РёРІРЅРѕ Рє РЅРµР»РµРіР°Р»СЊРЅРѕРјСѓ РёСЃРїРѕР»СЊР·РѕРІР°РЅРёСЋ РёРЅС„РѕСЂРјР°С†РёРё, РїРѕР»СѓС‡РµРЅРЅРѕР№ РЅР° СЃР°Р№С‚Рµ.</font></p>
<div align="center"><font class="small"><img src="./themes/<?=$ss_uri;?>/images/en.gif" width="20" height="15"></font></div>
<p align="justify"><font class="small" style="font-weight: normal;">No files you see here are hosted on the server. Links available are provided by site users and administation is not responsible for them. It is strictly prohibited to upload any copyrighted material without explicit permission from copyright holders. If you find that some content is abusing you feel free to contact administation.</font></p>

<?php
stdfoot();
?>
