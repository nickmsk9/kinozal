<?php


# IMPORTANT: Do not edit below unless you know what you are doing!
if (!defined('IN_TRACKER'))
	die('Прямой вызов запрещён.');

function get_user_class_color($class, $username) {
	$class = (int)$class;
	if (!is_valid_user_class($class)) {
		return (string)$username;
	}

	return '<span class="u' . $class . '" title="' . htmlspecialchars_uni(get_user_class_name($class)) . '">' . $username . '</span>';
}

function display_date_time($timestamp = 0, $tzoffset = 0) {
	return date("Y-m-d H:i:s", $timestamp + ($tzoffset * 60));
}

function cut_text($txt, $car) {
	while (strlen($txt) > $car) {
		return substr($txt, 0, $car) . "...";
	}
	return $txt;
}

function textbbcode($form, $name, $content = "") {
	?>
	<script type="text/javascript" language="JavaScript">
	function RowsTextarea(n, w) {
		var inrows = document.getElementById(n);
		if (w < 1) {
			var rows = -5;
		} else {
			var rows = +5;
		}
		var outrows = inrows.rows + rows;
		if (outrows >= 5 && outrows < 50) {
			inrows.rows = outrows;
		}
		return false;
	}

	var SelField = document.<?php echo $form;?>.<?php echo $name;?>;
	var TxtFeld = document.<?php echo $form;?>.<?php echo $name;?>;

	var clientPC = navigator.userAgent.toLowerCase(); // Get client info
	var clientVer = parseInt(navigator.appVersion); // Get browser version

	var is_ie = ((clientPC.indexOf("msie") != -1) && (clientPC.indexOf("opera") == -1));
	var is_nav = ((clientPC.indexOf('mozilla') != -1) && (clientPC.indexOf('spoofer') == -1)
		&& (clientPC.indexOf('compatible') == -1) && (clientPC.indexOf('opera') == -1)
		&& (clientPC.indexOf('webtv') == -1) && (clientPC.indexOf('hotjava') == -1));

	var is_moz = 0;

	var is_win = ((clientPC.indexOf("win") != -1) || (clientPC.indexOf("16bit") != -1));
	var is_mac = (clientPC.indexOf("mac") != -1);

	function StoreCaret(text) {
		if (document.selection) {
			text.caretPos = document.selection.createRange().duplicate();
		} else if (document.getSelection) {
			text.caretPos = document.getSelection();
		}
	}
	function FieldName(text, which) {
		if (document.selection) {
			text.caretPos = document.selection.createRange().duplicate();
		} else if (document.getSelection) {
			text.caretPos = document.getSelection();
		}
		if (which != "") {
			var Field = eval("document.<?php echo $form;?>." + which);
			SelField = Field;
			TxtFeld = Field;
		}
	}
	function AddSmile(SmileCode) {
		var SmileCode;
		var newPost;
		var oldPost = SelField.value;
		newPost = oldPost + SmileCode;
		SelField.value = newPost;
		SelField.focus();
		return;
	}
	function AddSelectedText(Open, Close) {
		if (SelField.createTextRange && SelField.caretPos && Close == '\n') {
			var caretPos = SelField.caretPos;
			caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? Open + Close + ' ' : Open + Close;
			SelField.focus();
		} else if (SelField.caretPos) {
			SelField.caretPos.text = Open + SelField.caretPos.text + Close;
		} else {
			SelField.value += Open + Close;
			SelField.focus();
		}
	}
	function jqwrapText(element, openTag, closeTag) {
		// This function is not working properly with IE, thus making workaround without JQ
		if ((clientVer >= 4) && is_ie && is_win) {
			AddSelectedText(openTag, closeTag);
		} else {
		    var textArea = $(element);
		    var len = textArea.val().length;
		    var sel = textArea.getSelection();
		    var start = sel.start;
		    var end = sel.end;
		    var selectedText = textArea.val().substring(start, end);
		    var replacement = openTag + selectedText + closeTag;
		    textArea.val(textArea.val().substring(0, start) + replacement + textArea.val().substring(end, len));
		}
	}

	function InsertCode(code, info, type, error) {
		if (code == 'name') {
			AddSelectedText('[b]' + info + '[/b]', '\n');
		} else if (code == 'url' || code == 'mail') {
			if (code == 'url') var url = prompt(info, 'http://');
			if (code == 'mail') var url = prompt(info, '');
			if (!url) return alert(error);
			jqwrapText(TxtFeld, '[' + code + '=' + url + ']', '[/' + code + ']');
		} else if (code == 'color' || code == 'family' || code == 'size') {
			jqwrapText(TxtFeld, '[' + code + '=' + info + ']', '[/' + code + ']');
		} else if (code == 'li' || code == 'hr') {
			jqwrapText(TxtFeld, '[' + code + ']', '');
		} else {
			jqwrapText(TxtFeld, '[' + code + ']', '[/' + code + ']');
		}
	}

	function mozWrap(txtarea, open, close) {
		alert('mozWrap function is deprecated!');
		var selLength = txtarea.textLength;
		var selStart = txtarea.selectionStart;
		var selEnd = txtarea.selectionEnd;
		if (selEnd == 1 || selEnd == 2)
			selEnd = selLength;

		var s1 = (txtarea.value).substring(0, selStart);
		var s2 = (txtarea.value).substring(selStart, selEnd);
		var s3 = (txtarea.value).substring(selEnd, selLength);
		var sT = txtarea.scrollTop, sL = txtarea.scrollLeft;
		txtarea.value = s1 + open + s2 + close + s3;
		txtarea.focus();
		txtarea.scrollTop = sT;
		txtarea.scrollLeft = sL;
		return;
	}

	language = 1;
	richtung = 1;
	var DOM = document.getElementById ? 1 : 0,
		opera = window.opera && DOM ? 1 : 0,
		IE = !opera && document.all ? 1 : 0,
		NN6 = DOM && !IE && !opera ? 1 : 0;
	var ablauf = new Date();
	var jahr = ablauf.getTime() + (365 * 24 * 60 * 60 * 1000);
	ablauf.setTime(jahr);
	var richtung = 1;
	var isChat = false;
	NoHtml = true;
	NoScript = true;
	NoStyle = true;
	NoBBCode = true;
	NoBefehl = false;

	function setZustand() {
		transHtmlPause = false;
		transScriptPause = false;
		transStylePause = false;
		transBefehlPause = false;
		transBBPause = false;
	}
	setZustand();
	function keks(Name, Wert) {
		document.cookie = Name + "=" + Wert + "; expires=" + ablauf.toGMTString();
	}
	function changeNoTranslit(Nr) {
		if (document.trans.No_translit_HTML.checked)NoHtml = true; else {
			NoHtml = false
		}
		if (document.trans.No_translit_BBCode.checked)NoBBCode = true; else {
			NoBBCode = false
		}
		keks("NoHtml", NoHtml);
		keks("NoScript", NoScript);
		keks("NoStyle", NoStyle);
		keks("NoBBCode", NoBBCode);
	}
	function changeRichtung(r) {
		richtung = r;
		keks("TransRichtung", richtung);
		setFocus()
	}
	function changelanguage() {
		if (language == 1) {
			language = 0;
		}
		else {
			language = 1;
		}
		keks("autoTrans", language);
		setFocus();
		setZustand();
	}
	function setFocus() {
		TxtFeld.focus();
	}
	function repl(t, a, b) {
		var w = t, i = 0, n = 0;
		while ((i = w.indexOf(a, n)) >= 0) {
			t = t.substring(0, i) + b + t.substring(i + a.length, t.length);
			w = w.substring(0, i) + b + w.substring(i + a.length, w.length);
			n = i + b.length;
			if (n >= w.length) {
				break;
			}
		}
		return t;
	}
	var rus_lr2 = ('Е-е-О-о-Ё-Ё-Ё-Ё-Ж-Ж-Ч-Ч-Ш-Ш-Щ-Щ-Ъ-Ь-Э-Э-Ю-Ю-Я-Я-Я-Я-ё-ё-ж-ч-ш-щ-э-ю-я-я').split('-');
	var lat_lr2 = ('/E-/e-/O-/o-ЫO-Ыo-ЙO-Йo-ЗH-Зh-ЦH-Цh-СH-Сh-ШH-Шh-ъ' + String.fromCharCode(35) + '-ь' + String.fromCharCode(39) + '-ЙE-Йe-ЙU-Йu-ЙA-Йa-ЫA-Ыa-ыo-йo-зh-цh-сh-шh-йe-йu-йa-ыa').split('-');
	var rus_lr1 = ('А-Б-В-Г-Д-Е-З-И-Й-К-Л-М-Н-О-П-Р-С-Т-У-Ф-Х-Х-Ц-Щ-Ы-Я-а-б-в-г-д-е-з-и-й-к-л-м-н-о-п-р-с-т-у-ф-х-х-ц-щ-ъ-ы-ь-я').split('-');
	var lat_lr1 = ('A-B-V-G-D-E-Z-I-J-K-L-M-N-O-P-R-S-T-U-F-H-X-C-W-Y-Q-a-b-v-g-d-e-z-i-j-k-l-m-n-o-p-r-s-t-u-f-h-x-c-w-' + String.fromCharCode(35) + '-y-' + String.fromCharCode(39) + '-q').split('-');
	var rus_rl = ('А-Б-В-Г-Д-Е-Ё-Ж-З-И-Й-К-Л-М-Н-О-П-Р-С-Т-У-Ф-Х-Ц-Ч-Ш-Щ-Ъ-Ы-Ь-Э-Ю-Я-а-б-в-г-д-е-ё-ж-з-и-й-к-л-м-н-о-п-р-с-т-у-ф-х-ц-ч-ш-щ-ъ-ы-ь-э-ю-я').split('-');
	var lat_rl = ('A-B-V-G-D-E-JO-ZH-Z-I-J-K-L-M-N-O-P-R-S-T-U-F-H-C-CH-SH-SHH-' + String.fromCharCode(35) + String.fromCharCode(35) + '-Y-' + String.fromCharCode(39) + String.fromCharCode(39) + '-JE-JU-JA-a-b-v-g-d-e-jo-zh-z-i-j-k-l-m-n-o-p-r-s-t-u-f-h-c-ch-sh-shh-' + String.fromCharCode(35) + '-y-' + String.fromCharCode(39) + '-je-ju-ja').split('-');
	var transAN = true;
	function transliteText(txt) {
		vorTxt = txt.length > 1 ? txt.substr(txt.length - 2, 1) : "";
		buchstabe = txt.substr(txt.length - 1, 1);
		txt = txt.substr(0, txt.length - 2);
		return txt + translitBuchstabeCyr(vorTxt, buchstabe);
	}
	function translitBuchstabeCyr(vorTxt, txt) {
		var zweiBuchstaben = vorTxt + txt;
		var code = txt.charCodeAt(0);

		if (txt == "<")transHtmlPause = true; else if (txt == ">")transHtmlPause = false;
		if (txt == "<script")transScriptPause = true; else if (txt == "<" + "/script>")transScriptPause = false;
		if (txt == "<style")transStylePause = true; else if (txt == "<" + "/style>")transStylePause = false;
		if (txt == "[")transBBPause = true; else if (txt == "]")transBBPause = false;
		if (txt == "/")transBefehlPause = true; else if (txt == " ")transBefehlPause = false;

		if (
			(transHtmlPause == true && NoHtml == true) ||
				(transScriptPause == true && NoScript == true) ||
				(transStylePause == true && NoStyle == true) ||
				(transBBPause == true && NoBBCode == true) ||
				(transBefehlPause == true && NoBefehl == true) || !(((code >= 65) && (code <= 123)) || (code == 35) || (code == 39))) return zweiBuchstaben;

		for (x = 0; x < lat_lr2.length; x++) {
			if (lat_lr2[x] == zweiBuchstaben) return rus_lr2[x];
		}
		for (x = 0; x < lat_lr1.length; x++) {
			if (lat_lr1[x] == txt) return vorTxt + rus_lr1[x];
		}
		return zweiBuchstaben;
	}
	function translitBuchstabeLat(buchstabe) {
		for (x = 0; x < rus_rl.length; x++) {
			if (rus_rl[x] == buchstabe)
				return lat_rl[x];
		}
		return buchstabe;
	}
	function translateAlltoLatin() {
		if (!IE) {
			var txt = TxtFeld.value;
			var txtnew = "";
			var symb = "";
			for (y = 0; y < txt.length; y++) {
				symb = translitBuchstabeLat(txt.substr(y, 1));
				txtnew += symb;
			}
			TxtFeld.value = txtnew;
			setFocus()
		} else {
			var is_selection_flag = 1;
			var userselection = document.selection.createRange();
			var txt = userselection.text;

			if (userselection == null || userselection.text == null || userselection.parentElement == null || userselection.parentElement().type != "textarea") {
				is_selection_flag = 0;
				txt = TxtFeld.value;
			}
			txtnew = "";
			var symb = "";
			for (y = 0; y < txt.length; y++) {
				symb = translitBuchstabeLat(txt.substr(y, 1));
				txtnew += symb;
			}
			if (is_selection_flag) {
				userselection.text = txtnew;
				userselection.collapse();
				userselection.select();
			} else {
				TxtFeld.value = txtnew;
				setFocus()
			}
		}
		return;
	}
	function TransliteFeld(object, evnt) {
		if (language == 1 || opera) return;
		if (NN6) {
			var code = void 0;
			var code = evnt.charCode;
			var textareafontsize = 14;
			var textreafontwidth = 7;
			if (code == 13) {
				return;
			}
			if (code && (!(evnt.ctrlKey || evnt.altKey))) {
				pXpix = object.scrollTop;
				pYpix = object.scrollLeft;
				evnt.preventDefault();
				txt = String.fromCharCode(code);
				pretxt = object.value.substring(0, object.selectionStart);
				result = transliteText(pretxt + txt);
				object.value = result + object.value.substring(object.selectionEnd);
				object.setSelectionRange(result.length, result.length);
				object.scrollTop = 100000;
				object.scrollLeft = 0;

				cXpix = (result.split("\n").length) * (textareafontsize + 3);
				cYpix = (result.length - result.lastIndexOf("\n") - 1) * (textreafontwidth + 1);
				taXpix = (object.rows + 1) * (textareafontsize + 3);
				taYpix = object.clientWidth;

				if ((cXpix > pXpix) && (cXpix < (pXpix + taXpix))) object.scrollTop = pXpix;
				if (cXpix <= pXpix) object.scrollTop = cXpix - (textareafontsize + 3);
				if (cXpix >= (pXpix + taXpix)) object.scrollTop = cXpix - taXpix;

				if ((cYpix >= pYpix) && (cYpix < (pYpix + taYpix))) object.scrollLeft = pYpix;
				if (cYpix < pYpix) object.scrollLeft = cYpix - (textreafontwidth + 1);
				if (cYpix >= (pYpix + taYpix)) object.scrollLeft = cYpix - taYpix + 1;
			}
			return true;
		} else if (IE) {
			if (isChat) {
				var code = frames['input'].event.keyCode;
				if (code == 13) {
					return;
				}
				txt = String.fromCharCode(code);
				cursor_pos_selection = frames['input'].document.selection.createRange();
				cursor_pos_selection.text = "";
				cursor_pos_selection.moveStart("character", -1);
				vorTxt = cursor_pos_selection.text;
				if (vorTxt.length > 1) {
					vorTxt = "";
				}
				frames['input'].event.keyCode = 0;
				if (richtung == 2) {
					result = vorTxt + translitBuchstabeLat(txt)
				} else {
					result = translitBuchstabeCyr(vorTxt, txt)
				}
				if (vorTxt != "") {
					cursor_pos_selection.select();
					cursor_pos_selection.collapse();
				}
				with (frames['input'].document.selection.createRange()) {
					text = result;
					collapse();
					select()
				}
			} else {
				var code = event.keyCode;
				if (code == 13) {
					return;
				}
				txt = String.fromCharCode(code);
				cursor_pos_selection = document.selection.createRange();
				cursor_pos_selection.text = "";
				cursor_pos_selection.moveStart("character", -1);
				vorTxt = cursor_pos_selection.text;
				if (vorTxt.length > 1) {
					vorTxt = "";
				}
				event.keyCode = 0;
				if (richtung == 2) {
					result = vorTxt + translitBuchstabeLat(txt)
				} else {
					result = translitBuchstabeCyr(vorTxt, txt)
				}
				if (vorTxt != "") {
					cursor_pos_selection.select();
					cursor_pos_selection.collapse();
				}
				with (document.selection.createRange()) {
					text = result;
					collapse();
					select()
				}
			}
			return;
		}
	}
	function translateAlltoCyrillic() {
		if (!IE) {
			txt = TxtFeld.value;
			var txtnew = translitBuchstabeCyr("", txt.substr(0, 1));
			var symb = "";
			for (kk = 1; kk < txt.length; kk++) {
				symb = translitBuchstabeCyr(txtnew.substr(txtnew.length - 1, 1), txt.substr(kk, 1));
				txtnew = txtnew.substr(0, txtnew.length - 1) + symb;
			}
			TxtFeld.value = txtnew;
			setFocus()
		} else {
			var is_selection_flag = 1;
			var userselection = document.selection.createRange();
			var txt = userselection.text;
			if (userselection == null || userselection.text == null || userselection.parentElement == null || userselection.parentElement().type != "textarea") {
				is_selection_flag = 0;
				txt = TxtFeld.value;
			}
			var txtnew = translitBuchstabeCyr("", txt.substr(0, 1));
			var symb = "";
			for (kk = 1; kk < txt.length; kk++) {
				symb = translitBuchstabeCyr(txtnew.substr(txtnew.length - 1, 1), txt.substr(kk, 1));
				txtnew = txtnew.substr(0, txtnew.length - 1) + symb;
			}
			if (is_selection_flag) {
				userselection.text = txtnew;
				userselection.collapse();
				userselection.select();
			} else {
				TxtFeld.value = txtnew;
				setFocus()
			}
		}
		return;
	}
	</script>
	<textarea class="editorinput" id="area" name="<?php echo $name; ?>" cols="65" rows="10" style="width:400px"
			  OnKeyPress="TransliteFeld(this, event)" OnSelect="FieldName(this, this.name)"
			  OnClick="FieldName(this, this.name)"
			  OnKeyUp="FieldName(this, this.name)"><?php echo $content; ?></textarea>
	<div class="editor" style="background-image: url(editor/bg.gif); background-repeat: repeat-x;">
		<div class="editorbutton" OnClick="RowsTextarea('area',1)"><img title="Увеличить окно" src="editor/plus.gif">
		</div>
		<div class="editorbutton" OnClick="RowsTextarea('area',0)"><img title="Уменьшить окно" src="editor/minus.gif">
		</div>
		<div class="editorbutton" OnClick="InsertCode('b')"><img title="Жирный текст" src="editor/bold.gif"></div>
		<div class="editorbutton" OnClick="InsertCode('i')"><img title="Наклонный текст" src="editor/italic.gif"></div>
		<div class="editorbutton" OnClick="InsertCode('u')"><img title="Подчеркнутый текст" src="editor/underline.gif">
		</div>
		<div class="editorbutton" OnClick="InsertCode('s')"><img title="Перечеркнутый текст" src="editor/striket.gif">
		</div>
		<div class="editorbutton" OnClick="InsertCode('li')"><img title="Маркированный список" src="editor/li.gif">
		</div>
		<div class="editorbutton" OnClick="InsertCode('hr')"><img title="Разделительная линия" src="editor/hr.gif">
		</div>
		<div class="editorbutton" OnClick="InsertCode('left')"><img title="Выравнивание по левому краю"
																	src="editor/left.gif"></div>
		<div class="editorbutton" OnClick="InsertCode('center')"><img title="Выравнивание по центру"
																	  src="editor/center.gif"></div>
		<div class="editorbutton" OnClick="InsertCode('right')"><img title="Выравнивание по правому краю"
																	 src="editor/right.gif"></div>
		<div class="editorbutton" OnClick="InsertCode('justify')"><img title="Выравнивание по ширине"
																	   src="editor/justify.gif"></div>
		<div class="editorbutton" OnClick="InsertCode('code')"><img title="Код" src="editor/code.gif"></div>
		<div class="editorbutton" OnClick="InsertCode('php')"><img title="PHP-Код" src="editor/php.gif"></div>
		<div class="editorbutton" OnClick="InsertCode('hide')"><img title="Скрытый текст" src="editor/hide.gif"></div>
		<div class="editorbutton"
			 OnClick="InsertCode('url','Введите полный адрес','Введите описание','Вы не указали адрес!')"><img
				title="Вставить ссылку" src="editor/url.gif"></div>
		<div class="editorbutton"
			 OnClick="InsertCode('mail','Введите полный адрес','Введите описание','Вы не указали адрес!')"><img
				title="Вставить E-Mail" src="editor/mail.gif"></div>
		<div class="editorbutton" OnClick="InsertCode('img')"><img title="Вставить картинку" src="editor/img.gif"></div>
	</div>
	<div class="editor" style="background-image: url(editor/bg.gif); background-repeat: repeat-x;">
		<div class="editorbutton" OnClick="InsertCode('quote')"><img title="Цитировать" src="editor/quote.gif"></div>
		<div class="editorbutton" OnClick="translateAlltoCyrillic()"><img title="Перевод текста с латиницы в кириллицу"
																		  src="editor/rus.gif"></div>
		<div class="editorbutton" OnClick="translateAlltoLatin()"><img title="Перевод текста с кириллицы в латиницу"
																	   src="editor/eng.gif"></div>
		<div class="editorbutton" OnClick="changelanguage()"><img title="Автоматический перевод текста"
																  src="editor/auto.gif"></div>
		<!--<div class="editorbutton"><select class="editorinput" tabindex="1" style="font-size:10px;" name="family" onChange="InsertCode('family',this.options[this.selectedIndex].value)"><option style="font-family:Verdana;" value="Verdana">Verdana</option><option style="font-family:Arial;" value="Arial">Arial</option><option style="font-family:'Courier New';" value="Courier New">Courier New</option><option style="font-family:Tahoma;" value="Tahoma">Tahoma</option><option style="font-family:Helvetica;" value="Helvetica">Helvetica</option></select></div>-->
		<div class="editorbutton"><select class="editorinput" tabindex="1" style="font-size:10px;" name="family"
										  onChange="InsertCode('family',this.options[this.selectedIndex].value)"
										  onFocus="this.value='Шрифт'">
				<option style="font-weight:bold;" value="Шрифт">Шрифт</option>
				<option style="font-family:Verdana;" value="Verdana">Verdana</option>
				<option style="font-family:Arial;" value="Arial">Arial</option>
				<option style="font-family:'Courier New';" value="Courier New">Courier New</option>
				<option style="font-family:Tahoma;" value="Tahoma">Tahoma</option>
				<option style="font-family:Helvetica;" value="Helvetica">Helvetica</option>
			</select></div>
		<!--<div class="editorbutton"><select class="editorinput" tabindex="1" style="font-size:10px;" name="color" onChange="InsertCode('color',this.options[this.selectedIndex].value)"><option style="color:black;" value="black">Цвет шрифта</option><option style="color:silver;" value="silver">Цвет шрифта</option><option style="color:gray;" value="gray">Цвет шрифта</option><option style="color:white;" value="white">Цвет шрифта</option><option style="color:maroon;" value="maroon">Цвет шрифта</option><option style="color:red;" value="red">Цвет шрифта</option><option style="color:purple;" value="purple">Цвет шрифта</option><option style="color:fuchsia;" value="fuchsia">Цвет шрифта</option><option style="color:green;" value="green">Цвет шрифта</option><option style="color:lime;" value="lime">Цвет шрифта</option><option style="color:olive;" value="olive">Цвет шрифта</option><option style="color:yellow;" value="yellow">Цвет шрифта</option><option style="color:navy;" value="navy">Цвет шрифта</option><option style="color:blue;" value="blue">Цвет шрифта</option><option style="color:teal;" value="teal">Цвет шрифта</option><option style="color:aqua;" value="aqua">Цвет шрифта</option></select></div>-->
		<div class="editorbutton"><select class="editorinput" tabindex="1" style="font-size:10px;" name="color"
										  onChange="InsertCode('color',this.options[this.selectedIndex].value)"
										  onFocus="this.value='Цвет'">
				<option style="font-weight:bold;" value="Цвет">Цвет</option>
				<option style="color:black;" value="black">Black</option>
				<option style="color:silver;" value="silver">Silver</option>
				<option style="color:gray;" value="gray">Gray</option>
				<option style="color:white;" value="white">White</option>
				<option style="color:maroon;" value="maroon">Maroon</option>
				<option style="color:red;" value="red">Red</option>
				<option style="color:purple;" value="purple">Purple</option>
				<option style="color:fuchsia;" value="fuchsia">Fuchsia</option>
				<option style="color:green;" value="green">Green</option>
				<option style="color:lime;" value="lime">Lime</option>
				<option style="color:olive;" value="olive">Olive</option>
				<option style="color:yellow;" value="yellow">Yellow</option>
				<option style="color:navy;" value="navy">Navy</option>
				<option style="color:blue;" value="blue">Blue</option>
				<option style="color:teal;" value="teal">Teal</option>
				<option style="color:aqua;" value="aqua">Aqua</option>
			</select></div>
		<!--<div class="editorbutton"><select class="editorinput" tabindex="1" style="font-size:10px;" name="size" onChange="InsertCode('size',this.options[this.selectedIndex].value)"><option value="8">Размер 8</option><option value="10">Размер 10</option><option value="12">Размер 12</option><option value="14">Размер 14</option><option value="18">Размер 18</option><option value="24">Размер 24</option></select></div>-->
		<div class="editorbutton"><select class="editorinput" tabindex="1" style="font-size:10px;" name="size"
										  onChange="InsertCode('size',this.options[this.selectedIndex].value)"
										  onFocus="this.value='Размер'">
				<option style="font-weight:bold;" value="Размер">Размер</option>
				<option value="8">Размер 8</option>
				<option value="10">Размер 10</option>
				<option value="12">Размер 12</option>
				<option value="14">Размер 14</option>
				<option value="18">Размер 18</option>
				<option value="24">Размер 24</option>
			</select></div>
	</div>
<?php
}

function get_row_count($table, $suffix = "")
{
    global $mysqli;

    $table = trim((string)$table);
    $suffix = trim((string)$suffix);

    if ($table === '') {
        return 0;
    }

    if ($suffix !== '') {
        $suffix = ' ' . $suffix;
    }

    $query = "SELECT COUNT(*) FROM {$table}{$suffix}";

    $res = sql_query($query);

    if (!$res) {
        die('MySQL error in get_row_count(): ' . mysqli_error($GLOBALS['___mysqli_ston']));
    }

    $row = mysqli_fetch_row($res);

    if (!$row) {
        die('MySQL fetch error in get_row_count(): ' . mysqli_error($GLOBALS['___mysqli_ston']));
    }

    return (int)$row[0];
}


function stdmsg($heading = '', $text = '', $div = 'success', $htmlstrip = false) {
	if ($htmlstrip) {
		$heading = htmlspecialchars_uni(trim($heading));
		$text = htmlspecialchars_uni(trim($text));
	}
	print("<table class=\"main\" width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tr><td class=\"embedded\">\n");
	print("<div class=\"$div\">" . ($heading ? "<b>$heading</b><br />" : "") . "$text</div></td></tr></table>\n");
}

function stderr($heading = '', $text = '') {
	stdhead();
	stdmsg($heading, $text, 'error');
	stdfoot();
	die;
}

function newerr($heading = '', $text = '', $head = true, $foot = true, $die = true, $div = 'error', $htmlstrip = true) {
    // Приведение типов (сохраняем поведение оригинала)
    $head = (bool)$head;
    $foot = (bool)$foot;
    $die = (bool)$die;
    $htmlstrip = (bool)$htmlstrip;

    // Вывод шапки сайта, если требуется
    if ($head && function_exists('stdhead')) {
        stdhead($heading);
    } elseif ($head) {
        // Если функции stdhead нет, выводим минимальный заголовок HTML
        echo '<!DOCTYPE html><html><head><title>' . htmlspecialchars($heading) . '</title></head><body>';
    }

    // Вывод сообщения через newmsg (ожидается, что функция определена)
    if (function_exists('newmsg')) {
        newmsg($heading, $text, $div, $htmlstrip);
    } else {
        // Fallback, если newmsg отсутствует
        $safeText = $htmlstrip ? htmlspecialchars($text) : $text;
        echo "<div class='$div'><strong>$heading</strong><br>$safeText</div>";
    }

    // Вывод подвала сайта
    if ($foot && function_exists('stdfoot')) {
        stdfoot();
    } elseif ($foot) {
        echo '</body></html>';
    }

    // Завершение скрипта
    if ($die) {
        exit;
    }
}

function sqlerr($file = '', $line = '') {
	global $queries;
	print("<table border=\"0\" bgcolor=\"blue\" align=\"left\" cellspacing=\"0\" cellpadding=\"10\" style=\"background: blue\">" .
		"<tr><td class=\"embedded\"><font color=\"white\"><h1>Ошибка в SQL</h1>\n" .
		"<b>Ответ от сервера MySQL: " . htmlspecialchars_uni(mysql_error()) . ($file != '' && $line != '' ? "<p>в $file, линия $line</p>" : "") . "<p>Запрос номер $queries.</p></b></font></td></tr></table>");
	die;
}

// Returns the current time in GMT in MySQL compatible format.
function get_date_time($timestamp = 0) {
	if ($timestamp)
		return date("Y-m-d H:i:s", $timestamp);
	else
		return date("Y-m-d H:i:s");
}

function encodehtml($s, $linebreaks = true) {
	$s = str_replace("<", "&lt;", str_replace("&", "&amp;", $s));
	if ($linebreaks)
		$s = nl2br($s);
	return $s;
}

function get_dt_num() {
	return date("YmdHis");
}

function format_urls($s) {
	return preg_replace(
		"/(\A|[^=\]'\"a-zA-Z0-9])((http|ftp|https|ftps|irc):\/\/[^()<>\s]+)/i",
		"\\1<a href=\"\\2\">\\2</a>", $s);
}


//Finds last occurrence of needle in haystack
//in PHP5 use strripos() instead of this
function _strlastpos($haystack, $needle, $offset = 0) {
	$addLen = strlen($needle);
	$endPos = $offset - $addLen;
	while (true) {
		if (($newPos = strpos($haystack, $needle, $endPos + $addLen)) === false) break;
		$endPos = $newPos;
	}
	return ($endPos >= 0) ? $endPos : false;
}

function format_quotes($s) {
	while ($old_s != $s) {
		$old_s = $s;

		//find first occurrence of [/quote]
		$close = strpos($s, "[/quote]");
		if ($close === false)
			return $s;

		//find last [quote] before first [/quote]
		//note that there is no check for correct syntax
		$open = _strlastpos(substr($s, 0, $close), "[quote");
		if ($open === false)
			return $s;

		$quote = substr($s, $open, $close - $open + 8);

		//[quote]Text[/quote]
		$quote = preg_replace(
			"/\[quote\]\s*((\s|.)+?)\s*\[\/quote\]\s*/i",
			"<p class=sub><b>Quote:</b></p><table class=\"main\" border=\"1\" cellspacing=\"0\" cellpadding=\"10\"><tr><td style=\"border: 1px black dotted\">\\1</td></tr></table><br />",
			$quote);

		//[quote=Author]Text[/quote]
		$quote = preg_replace(
			"/\[quote=(.+?)\]\s*((\s|.)+?)\s*\[\/quote\]\s*/i",
			"<p class=sub><b>\\1 wrote:</b></p><table class=\"main\" border=\"1\" cellspacing=\"0\" cellpadding=\"10\"><tr><td style=\"border: 1px black dotted\">\\2</td></tr></table><br />",
			$quote);

		$s = substr($s, 0, $open) . $quote . substr($s, $close + 8);
	}

	return $s;
}

// Format quote
function encode_quote($text) {
	$start_html = "<div align=\"center\"><div style=\"width: 85%; overflow: auto\">"
		. "<table width=\"100%\" cellspacing=\"1\" cellpadding=\"3\" border=\"0\" align=\"center\" class=\"bgcolor4\">"
		. "<tr bgcolor=\"FFE5E0\"><td><font class=\"block-title\">Цитата</font></td></tr><tr class=\"bgcolor1\"><td>";
	$end_html = "</td></tr></table></div></div>";
	$text = preg_replace("#\[quote\](.*?)\[/quote\]#si", "" . $start_html . "\\1" . $end_html . "", $text);
	return $text;
}

// Format quote from
function encode_quote_from($text) {
	$start_html = "<div align=\"center\"><div style=\"width: 85%; overflow: auto\">"
		. "<table width=\"100%\" cellspacing=\"1\" cellpadding=\"3\" border=\"0\" align=\"center\" class=\"bgcolor4\">"
		. "<tr bgcolor=\"FFE5E0\"><td><font class=\"block-title\">\\1 писал</font></td></tr><tr class=\"bgcolor1\"><td>";
	$end_html = "</td></tr></table></div></div>";
	$text = preg_replace("#\[quote=(.+?)\](.*?)\[/quote\]#si", "" . $start_html . "\\2" . $end_html . "", $text);
	return $text;
}

// Format spoiler
function encode_spoiler($text) {
	$text = preg_replace_callback("#\[hide\](.*?)\[/hide\]#si", 'escape1', $text);
	return $text;
}

// Format spoiler from
function encode_spoiler_from($text) {
	$text = preg_replace_callback("#\[hide=(.+?)\](.*?)\[/hide\]#si", 'escape2', $text);
	return $text;
}

// Format spoiler
function escape1($matches) {
	return "<div class=\"spoiler-wrap\"><div class=\"spoiler-head folded clickable\">Скрытый текст</div><div class=\"spoiler-body\"><textarea>" . htmlspecialchars_uni($matches[1]) . "</textarea></div></div>";
}

// Format spoiler from
function escape2($matches) {
	return "<div class=\"spoiler-wrap\"><div class=\"spoiler-head folded clickable\">" . $matches[1] . "</div><div class=\"spoiler-body\"><textarea>" . htmlspecialchars_uni($matches[2]) . "</textarea></div></div>";
}

// Format code
function encode_code($text) {
	$start_html = "<div align=\"center\"><div style=\"width: 85%; overflow: auto\">"
		. "<table width=\"100%\" cellspacing=\"1\" cellpadding=\"3\" border=\"0\" align=\"center\" class=\"bgcolor4\">"
		. "<tr bgcolor=\"E5EFFF\"><td colspan=\"2\"><font class=\"block-title\">Код</font></td></tr>"
		. "<tr class=\"bgcolor1\"><td align=\"right\" class=\"code\" style=\"width: 5px; border-right: none\">{ZEILEN}</td><td class=\"code\">";
	$end_html = "</td></tr></table></div></div>";
	$match_count = preg_match_all("#\[code\](.*?)\[/code\]#si", $text, $matches);
	for ($mout = 0; $mout < $match_count; ++$mout) {
		$before_replace = $matches[1][$mout];
		$after_replace = $matches[1][$mout];
		$after_replace = trim($after_replace);
		$zeilen_array = explode("<br />", $after_replace);
		$j = 1;
		$zeilen = "";
		foreach ($zeilen_array as $str) {
			$zeilen .= "" . $j . "<br />";
			++$j;
		}
		$after_replace = str_replace("", "", $after_replace);
		$after_replace = str_replace("&amp;", "&", $after_replace);
		$after_replace = str_replace("", "&nbsp; ", $after_replace);
		$after_replace = str_replace("", " &nbsp;", $after_replace);
		$after_replace = str_replace("", "&nbsp; &nbsp;", $after_replace);
		$after_replace = preg_replace("/^ {1}/m", "&nbsp;", $after_replace);
		$str_to_match = "[code]" . $before_replace . "[/code]";
		$replace = str_replace("{ZEILEN}", $zeilen, $start_html);
		$replace .= $after_replace;
		$replace .= $end_html;
		$text = str_replace($str_to_match, $replace, $text);
	}

	$text = str_replace("[code]", $start_html, $text);
	$text = str_replace("[/code]", $end_html, $text);
	return $text;
}

function encode_php($text) {
	$start_html = "<div align=\"center\"><div style=\"width: 85%; overflow: auto\">"
		. "<table width=\"100%\" cellspacing=\"1\" cellpadding=\"3\" border=\"0\" align=\"center\" class=\"bgcolor4\">"
		. "<tr bgcolor=\"F3E8FF\"><td colspan=\"2\"><font class=\"block-title\">PHP - Код</font></td></tr>"
		. "<tr class=\"bgcolor1\"><td align=\"right\" class=\"code\" style=\"width: 5px; border-right: none\">{ZEILEN}</td><td>";
	$end_html = "</td></tr></table></div></div>";
	$match_count = preg_match_all("#\[php\](.*?)\[/php\]#si", $text, $matches);
	for ($mout = 0; $mout < $match_count; ++$mout) {
		$before_replace = $matches[1][$mout];
		$after_replace = $matches[1][$mout];
		$after_replace = trim($after_replace);
		$after_replace = str_replace("&lt;", "<", $after_replace);
		$after_replace = str_replace("&gt;", ">", $after_replace);
		$after_replace = str_replace("&quot;", '"', $after_replace);
		$after_replace = preg_replace("/<br.*/i", "", $after_replace);
		$after_replace = (substr($after_replace, 0,
								 5) != "<?php") ? "<?php\n" . $after_replace . "" : "" . $after_replace . "";
		$after_replace = (substr($after_replace, -2) != "?>") ? "" . $after_replace . "\n?>" : "" . $after_replace . "";
		ob_start();
		highlight_string($after_replace);
		$after_replace = ob_get_contents();
		ob_end_clean();
		$zeilen_array = explode("<br />", $after_replace);
		$j = 1;
		$zeilen = "";
		foreach ($zeilen_array as $str) {
			$zeilen .= "" . $j . "<br />";
			++$j;
		}
		$after_replace = str_replace("\n", "", $after_replace);
		$after_replace = str_replace("&amp;", "&", $after_replace);
		$after_replace = str_replace("  ", "&nbsp; ", $after_replace);
		$after_replace = str_replace("  ", " &nbsp;", $after_replace);
		$after_replace = str_replace("\t", "&nbsp; &nbsp;", $after_replace);
		$after_replace = preg_replace("/^ {1}/m", "&nbsp;", $after_replace);
		$str_to_match = "[php]" . $before_replace . "[/php]";
		$replace = str_replace("{ZEILEN}", $zeilen, $start_html);
		$replace .= $after_replace;
		$replace .= $end_html;
		$text = str_replace($str_to_match, $replace, $text);
	}
	$text = str_replace("[php]", $start_html, $text);
	$text = str_replace("[/php]", $end_html, $text);
	return $text;
}

function code_nobb($matches) {
	$code = $matches[1];
	$code = str_replace("[", "&#91;", $code);
	$code = str_replace("]", "&#93;", $code);
	return '[code]' . $code . '[/code]';
}

function format_comment($text, $strip_html = true) {
	global $smilies, $privatesmilies, $pic_base_url;
	$smiliese = $smilies;
	$s = $text;

	$s = str_replace(";)", ":wink:", $s);

	$s = preg_replace_callback("#\[code\](.*?)\[/code\]#si", "code_nobb", $s);

	if ($strip_html)
		$s = htmlspecialchars_uni($s);

	$bb[] = "#\[img\](?!javascript:)([^?](?:[^\[]+|\[(?!url))*?)\[/img\]#i";
	$html[] = "<img class=\"linked-image\" src=\"\\1\" border=\"0\" alt=\"\\1\" title=\"\\1\" />";
	$bb[] = "#\[img=([a-zA-Z]+)\](?!javascript:)([^?](?:[^\[]+|\[(?!url))*?)\[/img\]#is";
	$html[] = "<img class=\"linked-image\" src=\"\\2\" align=\"\\1\" border=\"0\" alt=\"\\2\" title=\"\\2\" />";
	$bb[] = "#\[img\ alt=([a-zA-Zа-яА-Я0-9\_\-\. ]+)\](?!javascript:)([^?](?:[^\[]+|\[(?!url))*?)\[/img\]#is";
	$html[] = "<img class=\"linked-image\" src=\"\\2\" align=\"\\1\" border=\"0\" alt=\"\\1\" title=\"\\1\" />";
	$bb[] = "#\[img=([a-zA-Z]+) alt=([a-zA-Zа-яА-Я0-9\_\-\. ]+)\](?!javascript:)([^?](?:[^\[]+|\[(?!url))*?)\[/img\]#is";
	$html[] = "<img class=\"linked-image\" src=\"\\3\" align=\"\\1\" border=\"0\" alt=\"\\2\" title=\"\\2\" />";
	$bb[] = "#\[kp=([0-9]+)\]#is";
	$html[] = "<a href=\"http://www.kinopoisk.ru/level/1/film/\\1/\" rel=\"nofollow\"><img src=\"http://www.kinopoisk.ru/rating/\\1.gif/\" alt=\"Кинопоиск\" title=\"Кинопоиск\" border=\"0\" /></a>";
	$bb[] = "#\[url\]([\w]+?://([\w\#$%&~/.\-;:=,?@\]+]+|\[(?!url=))*?)\[/url\]#is";
	$html[] = "<a href=\"\\1\" title=\"\\1\">\\1</a>";
	$bb[] = "#\[url\]((www|ftp)\.([\w\#$%&~/.\-;:=,?@\]+]+|\[(?!url=))*?)\[/url\]#is";
	$html[] = "<a href=\"http://\\1\" title=\"\\1\">\\1</a>";
	$bb[] = "#\[url=([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*?)\]([^?\n\r\t].*?)\[/url\]#is";
	$html[] = "<a href=\"\\1\" title=\"\\1\">\\2</a>";
	$bb[] = "#\[url=((www|ftp)\.[\w\#$%&~/.\-;:=,?@\[\]+]*?)\]([^?\n\r\t].*?)\[/url\]#is";
	$html[] = "<a href=\"http://\\1\" title=\"\\1\">\\3</a>";
	$bb[] = "/\[url=([^()<>\s]+?)\]((\s|.)+?)\[\/url\]/i";
	$html[] = "<a href=\"\\1\">\\2</a>";
	$bb[] = "/\[url\]([^()<>\s]+?)\[\/url\]/i";
	$html[] = "<a href=\"\\1\">\\1</a>";
	$bb[] = "#\[mail\](\S+?)\[/mail\]#i";
	$html[] = "<a href=\"mailto:\\1\">\\1</a>";
	$bb[] = "#\[mail\s*=\s*([\.\w\-]+\@[\.\w\-]+\.[\w\-]+)\s*\](.*?)\[\/mail\]#i";
	$html[] = "<a href=\"mailto:\\1\">\\2</a>";
	$bb[] = "#\[color=(\#[0-9A-F]{6}|[a-z]+)\](.*?)\[/color\]#si";
	$html[] = "<span style=\"color: \\1\">\\2</span>";
	$bb[] = "#\[(font|family)=([A-Za-z ]+)\](.*?)\[/\\1\]#si";
	$html[] = "<span style=\"font-family: \\2\">\\3</span>";
	$bb[] = "#\[size=([0-9]+)\](.*?)\[/size\]#si";
	$html[] = "<span style=\"font-size: \\1\">\\2</span>";
	$bb[] = "#\[(left|right|center|justify)\](.*?)\[/\\1\]#is";
	$html[] = "<div align=\"\\1\">\\2</div>";
	$bb[] = "#\[b\](.*?)\[/b\]#si";
	$html[] = "<b>\\1</b>";
	$bb[] = "#\[i\](.*?)\[/i\]#si";
	$html[] = "<i>\\1</i>";
	$bb[] = "#\[u\](.*?)\[/u\]#si";
	$html[] = "<u>\\1</u>";
	$bb[] = "#\[s\](.*?)\[/s\]#si";
	$html[] = "<s>\\1</s>";
	$bb[] = "#\[li\]#si";
	$html[] = "<li>";
	$bb[] = "#\[hr\]#si";
	$html[] = "<hr>";
	$bb[] = "#\[youtube=([[:alnum:]]+)\]#si";
	$html[] = '<iframe width="640" height="360" src="//www.youtube.com/embed/\\1?rel=0" frameborder="0" allowfullscreen></iframe>';

	$s = preg_replace($bb, $html, $s);

	// Linebreaks
	$s = nl2br($s);

	// URLs
	$s = format_urls($s);
	//$s = format_local_urls($s);

	// Maintain spacing
	//$s = str_replace("  ", " &nbsp;", $s);

	foreach ($smiliese as $code => $url)
		$s = str_replace($code,
						 "<img border=\"0\" src=\"$pic_base_url/smilies/$url\">", $s);

	foreach ($privatesmilies as $code => $url)
		$s = str_replace($code, "<img border=\"0\" src=\"$pic_base_url/smilies/$url\">", $s);

	while (preg_match("#\[quote\](.*?)\[/quote\]#si", $s)) {
		$s = encode_quote($s);
	}
	while (preg_match("#\[quote=(.+?)\](.*?)\[/quote\]#si", $s)) {
		$s = encode_quote_from($s);
	}
	while (preg_match("#\[hide\](.*?)\[/hide\]#si", $s)) {
		$s = encode_spoiler($s);
	}
	while (preg_match("#\[hide=(.+?)\](.*?)\[/hide\]#si", $s)) {
		$s = encode_spoiler_from($s);
	}
	if (preg_match("#\[code\](.*?)\[/code\]#si", $s)) $s = encode_code($s);
	if (preg_match("#\[php\](.*?)\[/php\]#si", $s)) $s = encode_php($s);

	return $s;
}

function get_user_class(): int
{
    global $CURUSER;

    if (!is_array($CURUSER) || !isset($CURUSER['class'])) {
        return 0;
    }

    return (int) $CURUSER['class'];
}

function get_user_class_name($class) {
	global $tracker_lang;
	switch ((int)$class) {
		case UC_USER:
			return $tracker_lang['class_user'] ?? 'Зритель';

		case UC_POWER_USER:
			return $tracker_lang['class_power_user'] ?? 'Опытный Зритель';

		case UC_HONOR_USER:
			return $tracker_lang['class_honor_user'] ?? 'Заслуженный Зритель';

		case UC_VIP:
			return $tracker_lang['class_vip'] ?? 'ВИП';

		case UC_UPLOADER:
			return $tracker_lang['class_uploader'] ?? 'Кинооператор';

		case UC_SENIOR_UPLOADER:
			return $tracker_lang['class_senior_uploader'] ?? 'Главный Кинооператор';

		case UC_MANAGER:
			return $tracker_lang['class_manager'] ?? 'Менеджер';

		case UC_MODERATOR:
			return $tracker_lang['class_moderator'] ?? 'Редактор';

		case UC_ADMINISTRATOR:
			return $tracker_lang['class_administrator'] ?? 'Администратор';

		case UC_SYSOP:
			return $tracker_lang['class_sysop'] ?? 'Директор';
	}
	return "";
}

function is_valid_user_class($class) {
	return is_numeric($class) && floor($class) == $class && $class >= UC_USER && $class <= UC_SYSOP;
}

function kz_user_daily_torrent_limit($class)
{
	switch ((int)$class) {
		case UC_USER:
			return 3;
		case UC_POWER_USER:
			return 8;
		case UC_HONOR_USER:
			return 16;
		default:
			return 20;
	}
}

function kz_user_effective_torrent_limit($user)
{
	$limit = kz_user_daily_torrent_limit((int)($user['class'] ?? UC_USER));
	$downloaded = (float)($user['downloaded'] ?? 0);
	$uploaded = (float)($user['uploaded'] ?? 0);

	if ($downloaded >= 1073741824 && ($downloaded > 0 ? $uploaded / $downloaded : 1) < 0.7) {
		return 1;
	}

	return $limit;
}

function kz_torrent_downloads_ensure_schema()
{
	static $ready = false;

	if ($ready) {
		return;
	}

	sql_query("
		CREATE TABLE IF NOT EXISTS user_torrent_downloads (
			userid INT UNSIGNED NOT NULL,
			torrent INT UNSIGNED NOT NULL,
			download_date DATE NOT NULL,
			downloaded_at DATETIME NOT NULL,
			PRIMARY KEY (userid, torrent, download_date),
			KEY download_date (download_date),
			KEY torrent (torrent)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
	") or sqlerr(__FILE__, __LINE__);

	$ready = true;
}

function kz_torrent_downloads_today($userid)
{
	kz_torrent_downloads_ensure_schema();

	$userid = (int)$userid;
	$res = sql_query("
		SELECT COUNT(*) AS cnt
		FROM user_torrent_downloads
		WHERE userid = $userid
		  AND download_date = CURDATE()
	") or sqlerr(__FILE__, __LINE__);
	$row = mysqli_fetch_assoc($res);

	return $row ? (int)$row['cnt'] : 0;
}

function kz_torrent_download_seen_today($userid, $torrent)
{
	kz_torrent_downloads_ensure_schema();

	$userid = (int)$userid;
	$torrent = (int)$torrent;
	$res = sql_query("
		SELECT 1
		FROM user_torrent_downloads
		WHERE userid = $userid
		  AND torrent = $torrent
		  AND download_date = CURDATE()
		LIMIT 1
	") or sqlerr(__FILE__, __LINE__);

	return (bool)mysqli_fetch_assoc($res);
}

function kz_torrent_download_register($userid, $torrent)
{
	kz_torrent_downloads_ensure_schema();

	$userid = (int)$userid;
	$torrent = (int)$torrent;

	sql_query("
		INSERT INTO user_torrent_downloads (userid, torrent, download_date, downloaded_at)
		VALUES ($userid, $torrent, CURDATE(), NOW())
		ON DUPLICATE KEY UPDATE downloaded_at = VALUES(downloaded_at)
	") or sqlerr(__FILE__, __LINE__);
}

function int_check($value, $stdhead = false, $stdfoot = true, $die = true, $log = true) {
    global $CURUSER;

    // Рекурсивная обработка массива
    if (is_array($value)) {
        foreach ($value as $val) {
            int_check($val, $stdhead, $stdfoot, $die, $log);
        }
        return true;
    }

    // Проверка валидности ID через внешнюю функцию
    if (function_exists('is_valid_id') && is_valid_id($value)) {
        return true;
    }

    // ---- ID невалиден ----

    // Формируем сообщение для лога (на русском)
    $username = isset($CURUSER['username']) ? $CURUSER['username'] : 'неизвестно';
    $userid   = isset($CURUSER['id']) ? $CURUSER['id'] : 'неизвестно';
    $userip   = function_exists('getip') ? getip() : 'неизвестно';
    $msg = "Попытка использовать неверный ID: Пользователь: $username - ID: $userid - IP: $userip";

    // Вывод ошибки пользователю (на русском)
    if ($stdhead) {
        if (function_exists('stderr')) {
            stderr("ОШИБКА", "Неверный идентификатор! В целях безопасности это действие зарегистрировано.");
        } else {
            echo "<html><head><title>Ошибка</title></head><body>";
            echo "<h2>Ошибка</h2><p>Неверный идентификатор! В целях безопасности это действие зарегистрировано.</p>";
        }
    } else {
        echo "<h2>Ошибка</h2><table width='100%' border='1' cellspacing='0' cellpadding='10'><tr><td class='text'>";
        echo "Неверный идентификатор! В целях безопасности это действие зарегистрировано.";
        echo "</td></table>";
    }

    // Логирование
    if ($log && function_exists('write_log')) {
        write_log($msg);
    }

    // Подвал
    if ($stdfoot && function_exists('stdfoot')) {
        stdfoot();
    } elseif ($stdhead && !function_exists('stderr')) {
        echo "</body></html>";
    }

    // Завершение
    if ($die) {
        exit;
    }

    return false;
}

function is_valid_id($id) {
	return is_numeric($id) && ($id > 0) && (floor($id) == $id);
}

function sql_ts_to_ut($s) {
	return sql_timestamp_to_unix_timestamp($s);
}

function sql_timestamp_to_unix_timestamp($s) {
	return mktime(substr($s, 11, 2), substr($s, 14, 2), substr($s, 17, 2), substr($s, 5, 2), substr($s, 8, 2),
				  substr($s, 0, 4));
}

function get_ratio_color($ratio) {
	if ($ratio < 0.1) return "#ff0000";
	if ($ratio < 0.2) return "#ee0000";
	if ($ratio < 0.3) return "#dd0000";
	if ($ratio < 0.4) return "#cc0000";
	if ($ratio < 0.5) return "#bb0000";
	if ($ratio < 0.6) return "#aa0000";
	if ($ratio < 0.7) return "#990000";
	if ($ratio < 0.8) return "#880000";
	if ($ratio < 0.9) return "#770000";
	if ($ratio < 1) return "#660000";
	return "#000000";
}

function get_slr_color($ratio) {
	if ($ratio < 0.025) return "#ff0000";
	if ($ratio < 0.05) return "#ee0000";
	if ($ratio < 0.075) return "#dd0000";
	if ($ratio < 0.1) return "#cc0000";
	if ($ratio < 0.125) return "#bb0000";
	if ($ratio < 0.15) return "#aa0000";
	if ($ratio < 0.175) return "#990000";
	if ($ratio < 0.2) return "#880000";
	if ($ratio < 0.225) return "#770000";
	if ($ratio < 0.25) return "#660000";
	if ($ratio < 0.275) return "#550000";
	if ($ratio < 0.3) return "#440000";
	if ($ratio < 0.325) return "#330000";
	if ($ratio < 0.35) return "#220000";
	if ($ratio < 0.375) return "#110000";
	return "#000000";
}

function write_log($text, $color = "transparent", $type = "tracker") {
	$type = sqlesc($type);
	$color = sqlesc($color);
	$text = sqlesc($text);
	$added = sqlesc(get_date_time());
	sql_query("INSERT INTO sitelog (added, color, txt, type) VALUES($added, $color, $text, $type)");
}

function getWord($number, $suffix) {
	$keys = array(2, 0, 1, 1, 1, 2);
	$mod = $number % 100;
	$suffix_key = ($mod > 7 && $mod < 20) ? 2: $keys[min($mod % 10, 5)];
	return $suffix[$suffix_key];
}

function get_et($ts) {
	return get_elapsed_time_plural($ts);
}

function get_lt($ts) {
	return get_left_time_plural($ts);
}

function get_left_time_plural($time_end, $decimals = 0) {
	$divider['years']   = (60 * 60 * 24 * 365);
	$divider['months']  = (60 * 60 * 24 * 365 / 12);
	$divider['weeks']   = (60 * 60 * 24 * 7);
	$divider['days']    = (60 * 60 * 24);
	$divider['hours']   = (60 * 60);
	$divider['minutes'] = (60);

	$langs['years']		= array("год", "года", "лет");
	$langs['months']	= array("месяц", "месяца", "месяцев");
	$langs['weeks']		= array("неделю", "недели", "недель");
	$langs['days']		= array("сутки", "суток", "суток");
	$langs['hours']		= array("час", "часа", "часов");
	$langs['minutes']	= array("минуту", "минуты", "минут");

	foreach ($divider as $unit => $div) {
		${'left_time_'.$unit} = floor((($time_end - TIMENOW) / $div));
		if (${'left_time_'.$unit} >= 1)
			break;
	}
	$left_time = ${'left_time_'.$unit} . ' ' . getWord(${'left_time_'.$unit}, $langs[$unit]);

	return $left_time;
}

function get_elapsed_time_plural($time_start, $decimals = 0) {
	$divider['years']   = (60 * 60 * 24 * 365);
	$divider['months']  = (60 * 60 * 24 * 365 / 12);
	$divider['weeks']   = (60 * 60 * 24 * 7);
	$divider['days']    = (60 * 60 * 24);
	$divider['hours']   = (60 * 60);
	$divider['minutes'] = (60);

	$langs['years']		= array("год", "года", "лет");
	$langs['months']	= array("месяц", "месяца", "месяцев");
	$langs['weeks']		= array("неделю", "недели", "недель");
	$langs['days']		= array("день", "дня", "дней");
	$langs['hours']		= array("час", "часа", "часов");
	$langs['minutes']	= array("минуту", "минуты", "минут");

	foreach ($divider as $unit => $div) {
		${'elapsed_time_'.$unit} = floor(((TIMENOW - $time_start) / $div));
		if (${'elapsed_time_'.$unit} >= 1)
			break;
	}
	$elapsed_time = ${'elapsed_time_'.$unit} . ' ' . getWord(${'elapsed_time_'.$unit}, $langs[$unit]);

	return $elapsed_time;
}

function get_elapsed_time($ts) {
	$mins = floor((time() - $ts) / 60);
	$hours = floor($mins / 60);
	$mins -= $hours * 60;
	$days = floor($hours / 24);
	$hours -= $days * 24;
	$weeks = floor($days / 7);
	$days -= $weeks * 7;
	$t = "";
	if ($weeks > 0)
		return "$weeks недел" . ($weeks > 1 ? "и" : "я");
	if ($days > 0)
		return "$days д" . ($days > 1 ? "ней" : "ень");
	if ($hours > 0)
		return "$hours час" . ($hours > 1 ? "ов" : "");
	if ($mins > 0)
		return "$mins минут" . ($mins > 1 ? "" : "а");
	return "< 1 минуты";
}

?>
