//torrent.js,bb.engine,upload_framework,anytext,text,combo,bblist,bbtext,bbtabs,set

Torrent=function(){function h(d){d=d.target.files[0];var b=new FileReader;b.onload=function(c){c=new Uint8Array(c.target.result);var a=f(c);if(!a||!a.info)c=null;else{c=0;var b=a.info.files;if(b&&b instanceof Array)for(var a=0,d;d=b[a];++a)c+=d.length;else c=a.info.length;c=0==c?null:1024>c?c+" \u0431\u0430\u0439\u0442":1048576>c?(c/1024).toFixed()+" \u041a\u0411":1073741824>c?(c/1048576).toFixed()+" \u041c\u0411":(c/1073741824).toFixed(2)+" \u0413\u0411"}c&&g(c)};b.readAsArrayBuffer(d)}function f(d,
b,c){void 0===b&&(b=[0]);var a=String.fromCharCode(d[b[0]++]);if("d"==a){c={};for(a=String.fromCharCode(d[b[0]++]);"e"!=a;)b[0]--,a=f(d,b),c[a]="pieces"==a?f(d,b,!0):f(d,b),a=String.fromCharCode(d[b[0]++]);return c}if("l"==a){c=[];for(a=String.fromCharCode(d[b[0]++]);"e"!=a;)b[0]--,c.push(f(d,b)),a=String.fromCharCode(d[b[0]++]);return c}if("i"==a){for(var e="",a=String.fromCharCode(d[b[0]++]);"e"!=a;)e+=a,a=String.fromCharCode(d[b[0]++]);return Number(e)}if(/\d/.test(a)){for(e="";/\d/.test(a);)e+=
a,a=String.fromCharCode(d[b[0]++]);e=Number(e);a="";if(c)return b[0]+=e,"["+e+"]";for(;e--;)a+=escape(String.fromCharCode(d[b[0]++]));try{return decodeURIComponent(a)}catch(g){return unescape(a)+" (?!)"}}return null}var g;return{setListener:function(d,b){d.addEventListener("change",h,!1);g=b},supported:function(){return window.File&&window.FileReader&&window.FileList}}}();

function bbParse(a){var b=new BBTag;b.parse(null,a.split(""),0);return b}function BBTag(a,b){a&&(this.openTag=a.join(""),this.tag=arrayTrim(a).join("").toLowerCase());b&&(this.value=b.join(""));this.children=[]}
BBTag.prototype.parse=function(a,b,e){this.parent=a;for(var f=0,d=[],h=[],c=[];e<b.length;){var g=b[e];if("\r"!=g)switch(f){case 0:"["==g?(0!=c.length&&(this.pushChild(c),c=[]),f=1):"\n"==g?(0!=c.length&&(this.pushChild(c),c=[]),this.pushN()):this.pushChar(c,g);break;case 1:if("\n"==g)c.push("["),this.pushChars(c,d),d=[],f=0;else if("["==g)c.push("["),this.pushChars(c,d),d=[];else if("]"==g){if(0!=d.length){if(d=new BBTag(d,null),e=d.parse(this,b,e+1),this.closed)return e}else c.push("[","]");f=0;
d=[]}else"="==g?0!=d.length?f=3:(c.push("[","="),f=0):0==d.length&&"/"==g?f=2:this.pushChar(d,g);break;case 2:if("\n"==g)c.push("[","/"),this.pushChars(c,d),d=[],f=0;else if("["==g)c.push("[","/"),this.pushChars(c,d),d=[],f=1;else if("]"==g&&0!=d.length){if(f=this.tryClose(arrayTrim(d).join("").toLowerCase())){0!=c.length&&this.pushChild(c);for(a=this;a!=f;)a.closed=!0,a.parent.pushBad(a),a=a.parent;f.parent.children.push(f);f.closed=!0;f.closeTag=d.join("");return e}d.unshift("/");d.unshift("[");
d.push("]");this.pushChars(c,d);f=d.length=0}else this.pushChar(d,g);break;case 3:if("\n"==g)c.push("["),this.pushChars(c,d),d=[],c.push("="),this.pushChars(c,h),h=[],f=0;else if("["==g)c.push("["),this.pushChars(c,d),d=[],c.push("="),this.pushChars(c,h),h=[],f=1;else if("]"==g){d=new BBTag(d,h);e=d.parse(this,b,e+1);if(this.closed)return e;f=0;d=[];h=[]}else this.pushChar(h,g)}++e}if(this.closed)return e;1==f?(c.push("["),this.pushChars(c,d)):2==f?(c.push("[","/"),this.pushChars(c,d)):3==f&&(c.push("["),
this.pushChars(c,d),c.push("="),this.pushChars(c,h));0!=c.length&&this.pushChild(c);this.tag&&a.pushBad(this)};BBTag.prototype.tryClose=function(a){return this.tag==a?this:this.parent?this.parent.tryClose(a):!1};BBTag.prototype.pushBad=function(a){var b=["[",a.openTag];null!=a.value&&b.push("=",a.value);b.push("]");b=b.join("").split("");this.pushChild(b);for(b=0;b<a.children.length;++b){var e=a.children[b];e instanceof Array?this.pushChild(e):"\n"==e?this.pushN():this.children.push(e)}};
BBTag.prototype.pushChar=function(a,b){(!isSpace(b)||!(0!=a.length&&isSpace(a[a.length-1])))&&a.push(b)};BBTag.prototype.pushChars=function(a,b){a.push.apply(a,b)};BBTag.prototype.pushChild=function(a){var b=this.children.length;if(0!=b){b=this.children[b-1];if(b instanceof Array){this.pushChars(b,a);return}"\n"==b&&this.trimStart(a)}0!=a.length&&this.children.push(a)};
BBTag.prototype.pushN=function(){var a=this.children.length;if(0!=a){var b=this.children[a-1];b instanceof Array&&this.trimEnd(b);if(0==b.length){this.children[a-1]="\n";return}}this.children.push("\n")};BBTag.prototype.trimStart=function(a){0!=a.length&&isSpace(a[0])&&a.shift()};BBTag.prototype.trimEnd=function(a){0!=a.length&&isSpace(a[a.length-1])&&--a.length};BBTag.prototype.skipEmpty=function(a){for(;this.children.length>a&&"\n"==this.children[a];)this.children.splice(a,1)};
BBTag.prototype.toString=function(){var a=[];null!=this.tag&&(a.push("[",this.openTag),null!=this.value&&a.push("=",this.value),a.push("]"));a.push(this.childrenToString());null!=this.tag&&a.push("[/",this.closeTag,"]");return a.join("")};BBTag.prototype.childrenToString=function(){for(var a=[],b=0;b<this.children.length;b++){var e=this.children[b];e instanceof BBTag?a.push(e.toString()):e instanceof Array?a.push(e.join("")):a.push(e)}return a.join("")};
function arrayTrim(a){for(var b=0,e=a[b];isSpace(e);){++b;if(a.length==b)return[];e=a[b]}for(var f=a.length-1,e=a[f];isSpace(e);)e=a[--f];return a.slice(b,f+1)}function strTrim(a){return!a||!isSpace(a[0])&&!isSpace(a[a.length-1])?a:arrayTrim(a.split("")).join("")}function isSpace(a){return" "==a||"\t"==a||"\n"==a}function strEmpty(a){if(!a)return!0;for(var b=0;a.length>b;){if(!isSpace(a[b]))return!1;++b}return!0}
Array.prototype.indexOf||(Array.prototype.indexOf=function(a){for(var b=0,e=this.length;b<e;++b)if(this[b]===a)return b;return-1});

UF=function(){function b(a,b){var c=document.createElement(a);b&&(c.className=b);return c}return{el:function(a){return document.getElementById(a)},newEl:b,newDiv:function(a){return b("div",a)},newText:function(a){return document.createTextNode(a)}}}();

function AnyText(){this.title="AnyText"}
AnyText.prototype.parseAt=function(a,b){if(this.isParsed)return!1;var c=a.children[b];if(!(c instanceof BBTag)||"b"!=c.tag||1!=c.children.length)return!1;c=c.children[0];if(!(c instanceof Array))return!1;this.title=arrayTrim(c).join("");if(a.children.length>b+1){c=a.children[b+1];if(c instanceof Array)this.setText(c);else if(c instanceof BBTag)return!1;a.children.splice(b,2);a.skipEmpty(b);return this.isParsed=!0}return a.children.length==b+1?(a.children.splice(b,1),this.isParsed=!0):!1};
AnyText.prototype.parse=function(a){for(var b=0;b<a.children.length&&!this.parseAt(a,b);)++b};AnyText.prototype.setText=function(a){this.input=UF.newEl(100<a.length?"textarea":"input","up");this.input.value=arrayTrim(a).join("")};
AnyText.prototype.get=function(a){a||(a="div");var b=UF.newEl(a,"up_text_b");a="div"==a;var c=UF.newDiv();a&&(c.className="up_text_title");c.appendChild(UF.newText(this.title));b.appendChild(c);this.input&&(a=UF.newDiv(a?"up_text":"up_text_c"),a.appendChild(this.input),b.appendChild(a));return b};AnyText.prototype.isEmpty=function(){return this.input&&strEmpty(this.input.value)};AnyText.prototype.clear=function(){this.input&&(this.input.value="");this.isParsed=!1};
AnyText.prototype.toString=function(){return["[b]",this.title,"[/b] ",this.toItem()].join("")};AnyText.prototype.toItem=function(){return this.input?strTrim(this.input.value):""};

function Text(a,c){this.title=a;(this.opt=c)?(this.opt.type||(this.opt.type=0),this.opt.width||(this.opt.width="100%")):this.opt={type:0,width:"100%"};1==this.opt.type&&(this.opt.lines=8);this.opt.lines?(this.input=UF.newEl("textarea","up"),this.input.rows=this.opt.lines):this.input=UF.newEl("input","up");this.input.style.width=this.opt.width;this.opt.id&&(this.input.id=this.opt.id)}
Text.prototype.parseAt=function(a,c){if(this.isParsed)return!1;var b=a.children[c];if(!(b instanceof BBTag)||"b"!=b.tag||1!=b.children.length)return!1;b=b.children[0];if(!(b instanceof Array)||arrayTrim(b).join("").toLowerCase()!=this.title.toLowerCase())return!1;if(1==this.opt.type){for(var d=c+1,e=[];a.children.length>d;)b=a.children[d],b instanceof Array?(d==c+1&&BBTag.prototype.trimStart(b),e.push.apply(e,b)):b instanceof BBTag?e.push(b.toString()):e.push(b),++d;this.input.value=e.join("");a.children.splice(c,
d-c);return this.isParsed=!0}if(a.children.length>c+1){b=a.children[c+1];if(b instanceof Array)this.input&&this.setText(b);else if(b instanceof BBTag)return!1;a.children.splice(c,2);a.skipEmpty(c);return this.isParsed=!0}return a.children.length==c+1?(a.children.splice(c,1),this.isParsed=!0):!1};Text.prototype.parse=function(a){for(var c=0;c<a.children.length&&!this.parseAt(a,c);)++c};Text.prototype.setText=function(a){this.input.value=arrayTrim(a).join("")};
Text.prototype.get=function(a){a||(a="div");var c=UF.newEl(a,"up_text_b");a="div"==a;var b=UF.newDiv();a&&(b.className="up_text_title");b.appendChild(UF.newText(this.title));c.appendChild(b);a=UF.newDiv(a?"up_text":"up_text_c");2!=this.opt.type?a.appendChild(this.input):(a.className="",a.style.marginLeft="135px");this.opt.desc&&(b=UF.newDiv("n"),b.innerHTML=this.opt.desc,a.appendChild(b));c.appendChild(a);return c};Text.prototype.isEmpty=function(){return 2==this.opt.type?!1:strEmpty(this.input.value)};
Text.prototype.clear=function(){this.input&&(this.input.value="");this.isParsed=!1};Text.prototype.toString=function(){return(2==this.opt.type?["[b]",this.title,"[/b]"]:["[b]",this.title,"[/b] ",this.toItem()]).join("")};Text.prototype.toItem=function(){return strTrim(this.input.value)};

function Combo(a,b,c){this.title=a;this.items=b;(this.opt=c)?(this.opt.width||(this.opt.width="100%"),this.opt.listwidth||(this.opt.listwidth="100%"),this.opt.sep||(this.opt.sep=","),this.opt.join||(this.opt.join=", ")):this.opt={width:"100%",listwidth:"100%",sep:",",join:", "};this.body=UF.newDiv();a=UF.newDiv();this.opt.incont||(a.className="up_text_title");a.appendChild(UF.newText(this.title));this.body.appendChild(a);if(this.opt.multi)for(a=0;a<this.items.length;++a)this.items[a]={t:this.items[a],
ch:0};this.addLine()}
Combo.prototype.parseAt=function(a,b){if(this.isParsed)return!1;var c=a.children[b];if(!(c instanceof BBTag)||"b"!=c.tag||1!=c.children.length)return!1;c=c.children[0];if(!(c instanceof Array)||arrayTrim(c).join("").toLowerCase()!=this.title.toLowerCase())return!1;if(a.children.length>b+1){c=a.children[b+1];if(c instanceof Array)this.setText(c);else if(c instanceof BBTag)return!1;a.children.splice(b,2);a.skipEmpty(b);return this.isParsed=!0}return a.children.length==b+1?(a.children.splice(b,1),this.isParsed=
!0):!1};Combo.prototype.parse=function(a){for(var b=0;b<a.children.length&&!this.parseAt(a,b);)++b};
Combo.prototype.setText=function(a){a=arrayTrim(a).join("");if(this.opt.multi){a=a.split(this.opt.sep);for(var b=this.input.previousSibling.childNodes,c=0,d=a.length;c<d;++c){for(var e=(a[c]=strTrim(a[c])).toLowerCase(),f=-1,g=0,h=this.items.length;g<h;++g)if(this.items[g].t.toLowerCase()==e){f=g;break}-1!=f&&(b[f].firstChild.checked=!0,this.items[f].ch=!0)}this.input.value=a.join(this.opt.join)}else this.input.value=a};Combo.prototype.get=function(){return this.body};
Combo.prototype.addLine=function(){var a=UF.newDiv(this.opt.incont?"up_text_c":"up_text"),b=UF.newDiv("up_menuback");b.onmousedown=this.close;a.appendChild(b);b=UF.newDiv("up_combo_menu");b.style.width=this.opt.listwidth;this.opt.noscroll&&(b.style.maxHeight="100%");for(var c=0;c<this.items.length;++c){var d=UF.newDiv("bx1 up_combo_item");if(this.opt.multi){d.className+=" up_combo_mitem";var e=UF.newEl("input");e.type="checkbox";e.onclick=this.check;d.num=c;d.onmousedown=this.click;d.appendChild(e);
e=UF.newDiv("up_combo_item_t");e.appendChild(UF.newText(this.items[c].t));d.appendChild(e)}else d.onmousedown=this.select,d.appendChild(UF.newText(this.items[c]));b.appendChild(d)}a.appendChild(b);this.input=UF.newEl("input","up_combo");this.input.style.width=this.opt.width;this.input.onfocus=this.open;this.input.oninput=function(){this.previousSibling.previousSibling.onmousedown()};a.appendChild(this.input);this.opt.desc&&(b=UF.newDiv("n"),b.innerHTML=this.opt.desc,a.appendChild(b));this.body.appendChild(a);
this.opt.multi&&(a.control=this)};Combo.prototype.open=function(){this.className="up_combo up_combo_dd";this.previousSibling.style.display=this.previousSibling.previousSibling.style.display="block"};Combo.prototype.close=function(){this.style.display=this.nextSibling.style.display="none";this.nextSibling.nextSibling.className="up_combo"};Combo.prototype.select=function(){var a=this.parentNode.previousSibling;a.onmousedown();a=this.parentNode.nextSibling;a.value=this.innerHTML};
Combo.prototype.click=function(a){a=a||window.event;if((a.target||a.srcElement)!=this.firstChild)a=this.firstChild,a.checked=!a.checked,a.onclick()};Combo.prototype.check=function(){var a=this.parentNode,b=a.parentNode.parentNode.control;b.items[a.num].ch=this.checked;b.update()};Combo.prototype.update=function(){for(var a=[],b=0;b<this.items.length;++b)this.items[b].ch&&a.push(1==this.opt.nocap||0==a.length?this.items[b].t:this.items[b].t.toLowerCase());this.input.value=a.join(this.opt.join)};
Combo.prototype.isEmpty=function(){return strEmpty(this.input.value)};Combo.prototype.clear=function(){this.input.value="";this.isParsed=!1;if(this.opt.multi)for(var a=0;a<this.items.length;++a)this.items[a].ch=!1};Combo.prototype.toString=function(){return["[b]",this.title,"[/b] ",this.toItem()].join("")};Combo.prototype.toItem=function(){return strTrim(this.input.value)};

function BBList(a,b,d,c,e,f){this.title=a;this.tag=b;this.max=d;this.opt=f||{};this.opt.columns||(this.opt.columns=1);this.opt.add||(this.opt.add="\u044d\u043b\u0435\u043c\u0435\u043d\u0442");this.items=[];this.body=UF.newDiv(this.opt.nosh?"up_bblist_nosh":"up_bblist");a=UF.newDiv("mn up_bblist_t");this.opt.toggle&&(b=UF.newDiv("sbab up_toggle"),b.appendChild(UF.newText("\u0421\u043c\u0435\u043d\u0438\u0442\u044c \u0432\u0438\u0434")),b.owner=this,b.onclick=this.toggle,a.appendChild(b));a.appendChild(UF.newText(this.title));
this.body.appendChild(a);this.opt.desc&&(a=UF.newDiv("n"),a.innerHTML=this.opt.desc,this.body.appendChild(a));this.cvTitle=UF.newDiv("up_bblist_it");this.cvTitle.style.display="none";a=UF.newDiv("up_bblist_ival");a.appendChild(UF.newText(c));this.cvTitle.appendChild(a);this.body.appendChild(this.cvTitle);this.ctTitle=UF.newDiv("up_bblist_it");this.ctTitle.style.display="none";c=UF.newDiv("up_bblist_itxt");c.appendChild(UF.newText(e));this.ctTitle.appendChild(c);this.body.appendChild(this.ctTitle);
this.body.appendChild(UF.newDiv("up_clrl"));e=UF.newEl("table","up_bblist_cnt");this.container=UF.newEl("tbody");e.appendChild(this.container);this.body.appendChild(e);this.add=UF.newEl("span","sba");this.add.owner=this;this.add.onclick=function(){this.owner.addItem()};this.add.appendChild(UF.newText("\u0414\u043e\u0431\u0430\u0432\u0438\u0442\u044c "+this.opt.add));c=UF.newDiv("up_bblist_a");c.appendChild(this.add);this.body.appendChild(c);this.opt.toggle&&(this.text=UF.newDiv("up_bblist_inp"),a=
UF.newEl("textarea","up"),a.rows=5,this.text.appendChild(a),this.body.appendChild(this.text),this.toggleElements=[this.cvTitle,this.ctTitle,e,c],this.toggleMode=this.opt.toggleMode||0)}
BBList.prototype.parseAt=function(a,b,d){var c=a.children[b];return c instanceof BBTag&&c.tag==this.tag?(d=this.newItem(d),d.value.value=strTrim(c.value)||"",0<c.children.length&&(c.skipEmpty(0),c=c.children[0],c instanceof Array?d.text.value=arrayTrim(c).join(""):c instanceof BBTag&&(d.text.value=c.tag==this.opt.innerTag?c.childrenToString():c.toString())),a.children.splice(b,1),a.skipEmpty(b),!0):c instanceof BBTag&&c.tag==this.opt.innerTag?(d=this.newItem(d),c.skipEmpty(0),0<c.children.length&&
(d.text.value=c.childrenToString()),a.children.splice(b,1),a.skipEmpty(b),!0):!1};BBList.prototype.parse=function(a,b){for(var d=0;a.children.length>d;)this.parseAt(a,d,!b)||++d;!b&&this.toggleMode&&this.toggleToAdvanced()};BBList.prototype.parseAll=function(a){var b=a.childrenToString();this.parse(a,!0);0!=strTrim(a.childrenToString()).length?this.toggleToAdvanced(b):0!=this.items.length&&this.setTitlesDisplay(!0)};
BBList.prototype.setTitlesDisplay=function(a){this.ctTitle.style.display=this.cvTitle.style.display=a?"block":"none"};BBList.prototype.addItem=function(){this.items.length!=this.max&&this.newItem(!0).value.focus()};BBList.prototype.newItem=function(a){a&&0==this.items.length&&this.setTitlesDisplay(!0);a=this.getItem();this.items.push(a);this.addDOMItem(a);return a};BBList.prototype.getItem=function(){return{value:UF.newEl("input","up"),text:UF.newEl("input","up")}};
BBList.prototype.addDOMItem=function(a){var b=UF.newEl("tr"),d=UF.newEl("td","up_bblist_c0");b.appendChild(d);var c=UF.newEl("span","bulet");c.appendChild(UF.newText(" "));d.appendChild(c);d=UF.newEl("td","up_bblist_val");d.appendChild(a.value);b.appendChild(d);d=UF.newEl("td","up_bblist_txt");d.appendChild(a.text);b.appendChild(d);d=UF.newEl("td","up_bblist_c1");c=UF.newDiv("up_bblist_del");c.onclick=this.removeItem;c.owner=this;c.item=a;d.appendChild(c);b.appendChild(d);this.container.appendChild(b);
this.items.length==this.max&&(this.add.style.display="none")};BBList.prototype.get=function(){return this.body};BBList.prototype.toggle=function(){this.owner.toggleMode?this.owner.toggleToSimple():this.owner.toggleToAdvanced()};
BBList.prototype.toggleToSimple=function(){this.toggleMode=0;var a=bbParse(this.text.firstChild.value);this.parse(a,!0);a.skipEmpty(0);if((a=strTrim(a.toString()))&&!Upl.skip(a))this.innerClear(),this.toggleMode=1;else{this.text.style.display="none";this.text.firstChild.value="";for(a=0;a<this.toggleElements.length;++a)this.toggleElements[a].style.display="block";0==this.items.length&&this.setTitlesDisplay(!1)}};
BBList.prototype.toggleToAdvanced=function(a){for(var b=0;b<this.toggleElements.length;++b)this.toggleElements[b].style.display="none";b=this.text.firstChild;b.value=a||this.toString(!1);b.rows=Math.max(this.items.length,5);this.innerClear();this.text.style.display="block";this.toggleMode=1};
BBList.prototype.removeItem=function(){var a=this.owner,b=a.items;b.length==a.max&&(a.add.style.display="");for(var d=0;b[d]!=this.item;)++d;b.splice(d,1);0==a.items.length&&a.setTitlesDisplay(!1);a=this.parentNode.parentNode;a.parentNode.removeChild(a)};BBList.prototype.isEmpty=function(){return this.toggleMode?strEmpty(this.text.firstChild.value):0==this.items.length};
BBList.prototype.clear=function(){this.innerClear();if(this.toggleMode){this.text.style.display="none";this.text.firstChild.value="";for(var a=0;a<this.toggleElements.length;++a)this.toggleElements[a].style.display="block";this.toggleMode=this.opt.toggleMode||0}};BBList.prototype.innerClear=function(){this.items.length=0;this.setTitlesDisplay(!1);for(var a=this.container.childNodes.length;0!=a;)this.container.removeChild(this.container.childNodes[--a]);this.toggleMode||(this.add.style.display="")};
BBList.prototype.toString=function(a){void 0==a&&(a=this.toggleMode);if(a){a=this.text.firstChild.value;var b=bbParse(a);this.parse(b,!0);b.skipEmpty(0);if(0!=strTrim(b.toString()).length)return a}for(var b=[],d=[],c=0,e=0;e<this.items.length;e++){a=this.items[e];var f=strTrim(a.value.value);a=strTrim(a.text.value);if(f||a)innerItem=[],(f||!this.opt.innerTag)&&innerItem.push("[",this.tag,"=",f,"]"),this.opt.innerTag&&innerItem.push("[",this.opt.innerTag,"]"),innerItem.push(a),this.opt.innerTag&&innerItem.push("[/",
this.opt.innerTag,"]"),(f||!this.opt.innerTag)&&innerItem.push("[/",this.tag,"]"),d.push(innerItem.join("")),++c,c==this.opt.columns&&(c=0,b.push(d.join(" ")),d.length=0)}0<d.length&&b.push(d.join(" "));this.toggleMode&&this.innerClear();return b.join("\n")};

function BBText(b,a,d,e,c){this.tag=a;this.opt=c;this.opt||(this.opt={});this.opt.val||(this.opt.val="{v}");this.opt.regex||(this.opt.regex=/.*/);this.value=UF.newEl("input","up");this.text=UF.newEl("input","up");this.body=UF.newDiv("up_bbtext");a=UF.newDiv("mn up_bbtext_t");this.check=UF.newEl("input");this.check.type="checkbox";this.check.onclick=function(){for(var a=this.parentNode,b=this.checked?"":"none";a=a.nextSibling;)a.style.display=b};a.appendChild(this.check);c=UF.newDiv("up_bbtext_title_t");
c.appendChild(UF.newText(b));a.appendChild(c);this.body.appendChild(a);this.opt.desc&&(b=UF.newDiv("n"),b.innerHTML=this.opt.desc,b.style.display="none",this.body.appendChild(b));b=UF.newDiv("up_bblist_it");a=UF.newDiv("up_bbtext_ival");a.appendChild(UF.newText(d));b.appendChild(a);this.body.appendChild(b);d=UF.newDiv("up_bblist_it");a=UF.newDiv("up_bbtext_itxt");a.appendChild(UF.newText(e));d.appendChild(a);this.body.appendChild(d);e=UF.newDiv("up_bblist_it");a=UF.newDiv("up_bbtext_val");a.appendChild(this.value);
e.appendChild(a);c=UF.newDiv("up_bblist_it");a=UF.newDiv("up_bbtext_txt");a.appendChild(this.text);c.appendChild(a);this.body.appendChild(e);this.body.appendChild(c);this.body.appendChild(UF.newDiv("up_clrl"));c.style.display=e.style.display=d.style.display=b.style.display="none"}
BBText.prototype.parseAt=function(b,a){if(this.isParsed)return!1;var d=b.children[a];if(!(d instanceof BBTag)||d.tag!=this.tag)return!1;for(var e="",c=0;c<d.children.length;++c){var f=d.children[c];if(f instanceof BBTag)return!1;if(f instanceof Array)if(""==e)e=arrayTrim(f).join("");else return!1}this.value.value=this.opt.val.replace("{v}",strTrim(d.value));this.text.value=e;this.check.checked||(this.check.checked=!0,this.check.onclick());b.children.splice(a,1);b.skipEmpty(a);return this.isParsed=
!0};BBText.prototype.parse=function(b){for(var a=0;a<b.children.length&&!this.parseAt(b,a);)++a};BBText.prototype.get=function(){this.check.checked||(this.value.value=this.opt.defVal||"",this.text.value=this.opt.defTxt||"");return this.body};BBText.prototype.isEmpty=function(){return!this.body.firstChild.firstChild.checked||strEmpty(this.value.value)&&strEmpty(this.text.value)};
BBText.prototype.clear=function(){this.value.value=this.text.value="";this.check.checked&&(this.check.checked=!1,this.check.onclick());this.isParsed=!1};BBText.prototype.toString=function(){if(!this.check.checked)return"";var b=strTrim(this.value.value),a=this.opt.regex.exec(b);a&&(b=a[1]);return["[",this.tag,"=",b,"]",strTrim(this.text.value).replace(",","."),"[/",this.tag,"]"].join("")};

function BBTabs(a,b,c,d){this.opt=b||{};this.tag=this.opt.tag||"pagesd";this.max=this.opt.max||10;this.defTab=this.opt.defTab||"\u041f\u0440\u0438\u043c\u0435\u0447\u0430\u043d\u0438\u044f";this.types=c;this.fixed=d;this.items=[];this.body=UF.newDiv("up_bbtabs_body");b=UF.newDiv("mn up_bblist_t");b.appendChild(UF.newText(a));this.body.appendChild(b);this.opt.desc&&(a=UF.newDiv("n"),a.innerHTML=this.opt.desc,this.body.appendChild(a));this.tabs=UF.newEl("ul","up_bbtabs_tabs");this.tabs.owner=this;this.body.appendChild(this.tabs);
a=UF.newEl("li","up_bbtabs_add");b=UF.newDiv("up_bbtabs_plus");b.title="\u0414\u043e\u0431\u0430\u0432\u0438\u0442\u044c \u043d\u043e\u0432\u0443\u044e \u0432\u043a\u043b\u0430\u0434\u043a\u0443...";b.onmousedown=function(){this.parentNode.className="mn up_bbtabs_add";this.nextSibling.nextSibling.style.display=this.nextSibling.style.display="block"};a.appendChild(b);b=UF.newDiv("up_menuback");b.onmousedown=function(){this.parentNode.className="up_bbtabs_add";this.nextSibling.style.display=this.style.display=
"none"};a.appendChild(b);b=UF.newDiv("mn up_bbtabs_menu");if(this.types)for(c=0;c<this.types.length;++c)d=this.types[c],b.appendChild(this.getMenuItem(d));if(this.fixed)for(c=0;c<this.fixed.length;++c){d=this.fixed[c];var e={type:d};d.control?e.title={value:d.title}:(e.title=this.getItemTitle(d.title),e.text=this.getItemText(d.text));e=this.getTab(e,!0);this.tabs.appendChild(e);d=this.getMenuItem(d,!0);d.tab=e;b.appendChild(d)}b.appendChild(this.getMenuItem(null));a.appendChild(b);this.tabs.appendChild(a);
this.container=UF.newDiv("bx1 up_bbtabs_c");this.container.style.display="none";this.body.appendChild(this.container);this.body.appendChild(UF.newDiv("up_clrl"));this.tabs.before=this.tabs.firstChild}BBTabs.prototype.parseAt=function(a,b,c){var d=a.children[b];return d instanceof BBTag&&d.tag==this.tag?(this.parseTab(d,c),a.children.splice(b,1),a.skipEmpty(b),!0):!1};
BBTabs.prototype.parse=function(a){for(var b=0;a.children.length>b;)this.parseAt(a,b)||++b;a.skipEmpty(0);0!=a.children.length&&(b=new BBTag,b.tag=this.tag,b.value=this.defTab,b.children=a.children,a.children=[b],this.parseAt(a,0,!0));0!=this.items.length&&this.tabs.firstChild.sel()};
BBTabs.prototype.parseTab=function(a,b){a.skipEmpty(0);for(var c=a.children.length;0!=c&&"\n"==a.children[c-1];)--c;a.children.length=c;var d=this.search(a,this.types)||this.search(a,this.fixed),c=d?d.menuitem.tab?d.menuitem.tab.item:{type:d,title:d.control?{value:d.title}:this.getItemTitle(d.title)}:{};c.type&&(c.type.menuitem.style.display="none");!c.type||!c.type.control?(c.title||(c.title=this.getItemTitle(a.value)),c.text=this.getItemText(a.childrenToString())):(c.type.control.parseAll(a),c.content=
this.getItemContent(c));b?this.items.unshift(c):this.items.push(c);d&&d.menuitem.tab?d.menuitem.tab.style.display="block":b?this.tabs.insertBefore(this.getTab(c),this.tabs.firstChild):this.tabs.insertBefore(this.getTab(c),this.tabs.before);this.items.length==this.max&&(this.tabs.lastChild.style.display="none")};BBTabs.prototype.search=function(a,b){if(!b)return null;for(var c=a.value.toLowerCase(),d=0;d<b.length;++d)if(b[d].title.toLowerCase()==c)return b[d];return null};
BBTabs.prototype.getItemTitle=function(a){var b=UF.newEl("input","up");b.value=strTrim(a);b.maxLength=50;return b};BBTabs.prototype.getItemText=function(a){var b=UF.newDiv("up_bbtabs_ctxt"),c=UF.newEl("textarea","up_bbtabs_txt");c.rows=12;a&&(c.value=strTrim(a));b.appendChild(c);return b};BBTabs.prototype.getItemContent=function(a,b){var c=UF.newDiv("up_bbtabs_cc"),d=a.type.control;if(b&&a.type.text){var e=bbParse(a.type.text);d.parse(e)}c.appendChild(d.get());return c};BBTabs.prototype.get=function(){return this.body};
BBTabs.prototype.getMenuItem=function(a,b){var c=UF.newDiv(a?"mn2 up_bbtabs_mi t":"up_bbtabs_mi");c.owner=this;c.type=a;c.appendChild(UF.newText(a?a.title:"\u041d\u043e\u0432\u0430\u044f \u0432\u043a\u043b\u0430\u0434\u043a\u0430..."));c.onmousedown=b?this.addFixedTab:this.addTab;a&&(a.menuitem=c);return c};
BBTabs.prototype.switchTab=function(){var a=this.parentNode,b=a.parentNode,c=b.tab;if(c!=a){c&&c.setInactive();b.tab=a;a.setActive();c=b.owner.container;c.firstChild&&c.removeChild(c.firstChild);var d=a.item.text||a.item.content;d||(d=a.item.content=b.owner.getItemContent(a.item,!0));c.appendChild(d);"none"==c.style.display&&(c.style.display="block")}};
BBTabs.prototype.addTab=function(){this.parentNode.parentNode.childNodes[1].onmousedown();var a={};this.type?(a.type=this.type,this.type.control?(a.title={value:this.type.title},a.content=this.owner.getItemContent(a,!0)):(a.title=this.owner.getItemTitle(this.type.title),a.text=this.owner.getItemText(this.type.text))):(a.title=this.owner.getItemTitle(""),a.text=this.owner.getItemText(""));for(var b=this.owner.items,c=b.length;0!=c;){var d=b[c-1];if(d.type&&d.type.menuitem.tab)--c;else break}this.owner.items.splice(c,
0,a);a=this.owner.getTab(a);b=this.parentNode.parentNode.parentNode;b.insertBefore(a,b.before);a.sel();this.type&&(this.style.display="none");this.owner.items.length==this.owner.max&&(b.lastChild.style.display="none")};
BBTabs.prototype.addFixedTab=function(){this.parentNode.parentNode.childNodes[1].onmousedown();var a=this.tab.item;this.owner.items.push(a);a.content=null;this.tab.style.display="block";this.tab.sel();this.style.display="none";this.owner.items.length==this.owner.max&&(this.tab.parentNode.lastChild.style.display="none")};
BBTabs.prototype.deleteTab=function(){for(var a=this.parentNode.item,b=this.parentNode,c=b.parentNode,d=b.nextSibling;d&&!(d.item&&"none"!=d.style.display);)d=d.nextSibling;d||(d=b.previousSibling);for(;d&&!(d.item&&"none"!=d.style.display);)d=d.previousSibling;d?d.sel():(b.setInactive(),c.tab=null);b.move?c.removeChild(b):b.style.display="none";a.type&&(a.type.control&&a.type.control.clear(),a.type.menuitem.style.display="block");this.owner.items.splice(this.owner.items.indexOf(a),1);this.owner.items.length==
this.owner.max-1&&(c.lastChild.style.display="block");0==this.owner.items.length&&(this.owner.container.style.display="none");b.move&&(this.owner=null)};
BBTabs.prototype.getTab=function(a,b){var c=UF.newEl("li","up_bbtabs_tab");c.item=a;if(!b){var d=UF.newDiv("up_bbtabs_left");d.style.display="none";d.onclick=this.moveLeft;d.title="\u041f\u0435\u0440\u0435\u043c\u0435\u0441\u0442\u0438\u0442\u044c \u043b\u0435\u0432\u0435\u0435";c.appendChild(d)}d=UF.newDiv("up_bbtabs_tabtitle");this.setTabTitleText(d,a.title.value);d.onmousedown=this.switchTab;c.appendChild(d);d=UF.newDiv("up_bbtabs_del");d.title="\u0423\u0434\u0430\u043b\u0438\u0442\u044c \u0432\u043a\u043b\u0430\u0434\u043a\u0443";
d.onclick=this.deleteTab;d.owner=this;d.style.display="none";c.appendChild(d);b||(d=UF.newDiv("up_bbtabs_right"),d.style.display="none",d.onclick=this.moveRight,d.title="\u041f\u0435\u0440\u0435\u043c\u0435\u0441\u0442\u0438\u0442\u044c \u043f\u0440\u0430\u0432\u0435\u0435",c.appendChild(d),c.move=!0);b&&(c.style.display="none");c.sel=b?function(){this.firstChild.onmousedown()}:function(){this.childNodes[1].onmousedown()};c.setActive=this.setActive;c.setInactive=this.setInactive;return c};
BBTabs.prototype.moveLeft=function(){var a=this.parentNode,b=a.previousSibling;if(b){var c=a.parentNode;c.removeChild(a);c.insertBefore(a,b);b=c.owner.items;c=b.indexOf(a.item);b.splice(c,1);b.splice(c-1,0,a.item)}};BBTabs.prototype.moveRight=function(){var a=this.parentNode;if(a.nextSibling.move){var b=a.nextSibling.nextSibling,c=a.parentNode;c.removeChild(a);c.insertBefore(a,b);b=c.owner.items;c=b.indexOf(a.item);b.splice(c,1);b.splice(c+1,0,a.item)}};
BBTabs.prototype.setActive=function(){this.className="mn up_bbtabs_tab";var a=this.item.title,b=this.move?this.childNodes[1]:this.firstChild;"INPUT"==a.tagName&&(a.style.width=Math.max(b.clientWidth+10,80)+"px",b.removeChild(b.firstChild),b.style.padding="0px",b.appendChild(a));this.lastChild.style.display="block";this.move&&(this.firstChild.style.display=this.childNodes[2].style.display="block")};
BBTabs.prototype.setInactive=function(){this.className="up_bbtabs_tab";var a=this.move?this.childNodes[1]:this.firstChild;if("INPUT"==a.firstChild.tagName){var b=a.firstChild.value;a.removeChild(a.firstChild);this.parentNode.owner.setTabTitleText(a,b)}this.lastChild.style.display="none";this.move&&(this.firstChild.style.display=this.childNodes[2].style.display="none")};BBTabs.prototype.setTabTitleText=function(a,b){a.style.padding="2px";a.appendChild(UF.newText(b||"[\u0411\u0435\u0437 \u0437\u0430\u0433\u043e\u043b\u043e\u0432\u043a\u0430]"))};
BBTabs.prototype.isEmpty=function(){return 0==this.items.length};
BBTabs.prototype.clear=function(){this.clearTypes(this.types);for(this.fixed&&this.clearTypes(this.fixed);this.tabs.firstChild.move;)this.tabs.removeChild(this.tabs.firstChild);for(var a=this.tabs.firstChild;a.nextSibling;)a.style.display="none",a.setInactive(),a=a.nextSibling;this.items.length=0;for(var a=this.tabs.lastChild.lastChild.childNodes,b=0,c=a.length-1;b<c;++b){var d=a[b].style;"none"==d.display&&(d.display="block")}this.tabs.tab=null;this.container.hasChildNodes()&&this.container.removeChild(this.container.firstChild)};
BBTabs.prototype.clearTypes=function(a){for(var b=a.length;0!=b;){var c=a[--b].control;c&&c.clear()}};BBTabs.prototype.toString=function(){for(var a=[],b=0;b<this.items.length;++b){var c=this.items[b];(!c.type||!c.type.menuitem.tab)&&this.pushToOut(a,c)}if(this.fixed)for(b=0;b<this.fixed.length;++b)c=this.fixed[b].menuitem.tab,"none"!=c.style.display&&this.pushToOut(a,c.item);return a.join("\n")};
BBTabs.prototype.pushToOut=function(a,b){if(b.title.value){var c=this.itemToString(b),d=-1!=c.indexOf("\n"),e=["\n[",this.tag,"=",strTrim(b.title.value),"]"];d&&e.push("\n");e.push(c);d&&e.push("\n");e.push("[/",this.tag,"]");a.push(e.join(""))}};BBTabs.prototype.itemToString=function(a){return a.text?strTrim(a.text.firstChild.value):a.type.control.isEmpty()?"":a.type.control.toString()};

function Set(a){this.controls=a;this.items=[];this.body=UF.newDiv()}
Set.prototype.parse=function(a){for(var c=null,f=[],h=[],e=0;e<a.children.length;){for(var g=null,b=0;b<this.controls.length;++b){var d=this.controls[b];if(d.parseAt(a,e)){g=d;-1==f.indexOf(d)&&(h.push(this.items.length),f.push(d));break}}g||(c||(c=new AnyText),c.parseAt(a,e)&&(g=c,c=null));g?-1==this.items.indexOf(g)&&this.items.push(g):++e}for(e=a=b=0;e<this.controls.length;++e)d=this.controls[e],-1!=f.indexOf(d)?(this.items[h[b]+a]!=d&&(this.items[h[b]+a]=d),++b):(this.items.splice(0!=b?h[b-1]+
1+a:h[b]+a,0,d),++a)};Set.prototype.get=function(){for(var a=0;a<this.items.length;++a)this.body.appendChild(this.items[a].get());return this.body};Set.prototype.isEmpty=function(){return 0==this.items.length};Set.prototype.clear=function(){for(var a=this.items.length;0!=a;)this.body.lastChild&&this.body.removeChild(this.body.lastChild),this.items[--a].clear();this.items.length=0};Set.prototype.toString=function(){for(var a=[],c=0;c<this.items.length;++c){var f=this.items[c];f.isEmpty()||a.push(f.toString())}return a.join("\n")};

// templates.js
var form =
[
 { title: 'Видео',
   predesc: {
    simple: new Set([
     new Text('Название:', { desc: 'Для зарубежного видео, на русском языке' } ),
     new Text('Оригинальное название:', { desc: 'Название видео на языке оригинала' }),
     new Text('Год выпуска:'),
     new Text('Жанр:'),
     new Text('Выпущено:'),
     new Text('Режиссер:'),
     new Text('В ролях:', { desc: 'Список исполняющих роли через запятую', lines: 3 })
    ]),
    advanced: '[b]Название:[/b] Название фильма\n[b]Оригинальное название:[/b] Movie title\n[b]Год выпуска:[/b] 2009\n[b]Жанр:[/b] Комедия, приключения\n[b]Выпущено:[/b] Страна, киностудия\n[b]Режиссер:[/b] Имя Фамилия\n[b]В ролях:[/b] Имя Фамилия, Имя Фамилия'
   },
   desc: {
    simple: new Set([
	 new Text('О фильме:', { type: 1, desc: 'Рекомендуем писать собственное описание, а не копировать его из сети &ndash; это положительно скажется на количестве сидов и пиров и значительно увеличит срок жизни раздачи. Помощь по созданию описаний к раздачам <a href="//forum.kinozal.tv/forumdisplay.php?f=244" target="_blank">здесь</a>' })
	]),
	advanced: '[b]О фильме:[/b] Краткое описание фильма...'
   },
   tech: {
    simple: new Set([
	 new Combo('Качество:', ['BDRip','BDRip (AVC)','BDRip (720p)','BDRip (1080p)','BDRip (2160p)','Blu-Ray (1080p)','Blu-Ray (1080i)','Blu-Ray Remux (1080p)','Blu-Ray Remux (1080i)','Blu-Ray Remux (2160p)','DVB','DVD','DVD Remux','DVDRip','DVDRip (AVC)','HDRip','HDRip (AVC)','HDTV (720p)','HDTV (1080p)','HDTV (1080i)','HDTVRip','HDTVRip (AVC)','HDTVRip (720p)','HDTVRip (1080p)','Hybrid (2160p)','IPTV','IPTV (720p)','IPTV (1080p)','IPTV (1080i)','IPTVRip','SATRip','SATRip (AVC)','TVRip','UHDTV (2160p)','VHSRip','WEB-DL (720p)','WEB-DL (1080p)','WEB-DL (2160p)','WEB-DLRip','WEB-DLRip (AVC)','WEB-DLRip (720p)','WEB-DLRip (1080p)','WEBRip','WEBRip (AVC)','WEBRip (720p)','WEBRip (1080p)','WEBRip (2160p)'], { desc: 'Подробнее о качествe раздаваемого материала <a href="//forum.kinozal.tv/showthread.php?t=84803" target="_blank">здесь</a>', listwidth: '155px' } ),
     new Text('Видео:'),
     new Text('Аудио:'),
     new Text('Размер:', { desc: Torrent.supported() ? 'Устанавливается автоматически при выборе торрент-файла' : 'Размер раздаваемого материала в МБ или ГБ', id: 'size1' }),
     new Text('Продолжительность:', { desc: 'Точная продолжительность в формате ЧЧ:ММ:СС' }),
     new Combo('Перевод:', ['Дублированный','Профессиональный многоголосый','Профессиональный двухголосый','Профессиональный одноголосый','Авторский','Любительский многоголосый','Любительский двухголосый','Любительский одноголосый','Не требуется','Отсутствует'], { multi: 1, listwidth: '210px', desc: 'Для зарубежного видео. О видах перевода подробнее <a href="//forum.kinozal.tv/showpost.php?p=1802005&postcount=4" target="_blank">здесь</a>' }),
     new Combo('Язык:', ['Русский'], { listwidth: '210px', desc: 'Для отечественного видео' }),
     new Combo('Субтитры:', ['Русские','Английские','Испанские','Французские','Украинские','Немецкие','Португальские'], { multi: 1, listwidth: '170px', noscroll: 1, desc: 'Укажите субтитры, если имеются' })
	]),
	advanced: '[b]Качество:[/b] WEB-DL (1080p)\n[b]Видео:[/b] MPEG-4 AVC, 9131 Кбит/с, 1920x1080\n[b]Аудио:[/b] Русский (AC3, 6 ch, 384 Кбит/с)\n[b]Размер:[/b] 7.61 ГБ\n[b]Продолжительность:[/b] 01:39:43\n[b]Перевод:[/b] Дублированный'
   },
   notes: {
    simple: [
	 new BBList('Меню: поиск раздач', 'searchm', 10, 'Заголовок ссылки', 'Строка поиска', { desc: 'Добавьте ссылки на поиск других раздач, которые могут заинтересовать зрителей' }),
     new BBText('Меню: рейтинг IMDb', 'imdb', 'Ссылка на фильм', 'Цифры рейтинга', { desc: 'Укажите страницу фильма и его рейтинг на сайте <a href="https://www.imdb.com/" target="_blank">IMDb</a>, если есть', val: 'https://www.imdb.com/title/{v}/', regex: /imdb.com\/title\/(tt\d+)/i, defVal: 'https://www.imdb.com/title/tt00000/', defTxt: 'Цифры рейтинга' }),
     new BBText('Меню: рейтинг КиноПоиск', 'kinopoisk', 'Ссылка на фильм', 'Цифры рейтинга', { desc: 'Укажите страницу фильма и его рейтинг на сайте <a href="https://www.kinopoisk.ru/" target="_blank">КиноПоиск</a>, если есть', val: 'https://www.kinopoisk.ru/film/{v}/', regex: /kinopoisk.ru\/(?:film|series)\/(?:[a-z0-9\-]+\-)?(\d+)/i, defVal: 'https://www.kinopoisk.ru/film/00000/', defTxt: 'Цифры рейтинга' }),
     new BBList('Меню: ознакомление', 'linkm', 10, 'Заголовок ссылки', 'Адрес ссылки'),
     new BBTabs('Дополнительные вкладки', { desc: 'Вы можете указать дополнительную информацию о раздаваемом материале. Допустимо использовать не больше шести вкладок', max: 6 }, [
      { title: 'Содержание', text: '01. Перечень (серий|передач)' },
      { title: 'Треклист', text: '01. Перечень' },
      { title: 'Интересно', text: 'Интересные факты' },
      { title: 'Награды', text: 'Победитель, номинации' },
      { title: 'Релиз', text: 'Авторство релиза, перевода, озвучки, первоисточник' },
      { title: 'Обложки', text: '[url=https://полноразмерная_обложка][img]https://обложка_180х.jpg[/img][/url]',
        control: new BBList('Обложки', 'url', 6, 'Ссылка на полноразмерную обложку', 'Ссылка на превью шириной 180 пикс', { innerTag: 'img', columns: 3, add: 'обложку', toggle: true, nosh: 1 })
      }
     ],
     [	// фиксированные вкладки
     	{	title: 'Скриншоты', text: '[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]',
     		control: new BBList('Скриншоты', 'url', 6, 'Ссылка на полноразмерный скриншот', 'Ссылка на превью шириной 320 пикс', { innerTag: 'img', columns: 2, add: 'скриншот', toggle: true, nosh: 1, desc: 'Скриншотов должно быть четное количество, с превью шириной 320 пикселей. Разместите скриншоты на <a href="//forum.kinozal.tv/showthread.php?t=78697" target="_blank">хостингах изображений</a>. Для вставки готовых ссылок используйте кнопку «Сменить вид» справа' }) }
     ])
	],
	advanced: '[searchm=Все серии]Поисковое слово, название[/searchm]\n[searchm=Подобные раздачи]Поисковое слово, название[/searchm]\n[imdb=tt00000]Цифры рейтинга[/imdb]\n[kinopoisk=00000]Цифры рейтинга[/kinopoisk]\n[linkm=Трейлер]https://ссылка[/linkm]\n[linkm=Скачать семпл]https://ссылка[/linkm]\n\n[pagesd=Содержание]01. Перечень (серий|передач)[/pagesd]\n\n[pagesd=Интересно]Интересные факты[/pagesd]\n\n[pagesd=Релиз]Авторство релиза, перевода, озвучки, первоисточник[/pagesd]\n\n[pagesd=Скриншоты]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[/pagesd]'
   }
 },

 { title: 'Музыка',
   predesc: {
    simple: new Set([
     new Text('Исполнитель:'), new Text('Альбом:'), new Text('Год выпуска:'), new Text('Жанр:')
    ]),
    advanced: '[b]Исполнитель:[/b] Имя исполнителя, Artist name\n[b]Альбом:[/b] Название альбома, Album name\n[b]Год выпуска:[/b] 2009\n[b]Жанр:[/b] Поп, рок, блюз'
   },
   desc: {
    simple: new Set([
	 new Text('О музыке:', { type: 1, desc: 'Рекомендуем писать собственное описание, а не копировать его из сети &ndash; это положительно скажется на количестве сидов и пиров и значительно увеличит срок жизни раздачи. Помощь по созданию описаний к раздачам <a href="//forum.kinozal.tv/forumdisplay.php?f=244" target="_blank">здесь</a>' })
	]),
	advanced: '[b]О музыке:[/b] Краткое описание...'
   },
   tech: {
    simple: new Set([
	 new Text('Аудио:'),
     new Text('Размер:', { desc: Torrent.supported() ? 'Устанавливается автоматически при выборе торрент-файла' : 'Размер раздаваемого материала в МБ или ГБ', id: 'size2' }),
     new Text('Продолжительность:', { desc: 'Общая продолжительность в формате ЧЧ:ММ:СС' })
	]),
	advanced: '[b]Аудио:[/b] MP3, 192 Кбит/с\n[b]Размер:[/b] 600 МБ\n[b]Продолжительность:[/b] 01:15:14'
   },
   notes: {
    simple: [
	 new BBList('Меню: поиск раздач', 'searchm', 10, 'Заголовок ссылки', 'Строка поиска', { desc: 'Добавьте ссылки на поиск других раздач, которые могут заинтересовать зрителей' }),
     new BBList('Меню: ознакомление', 'linkm', 10, 'Заголовок ссылки', 'Адрес ссылки'),
     new BBTabs('Дополнительные вкладки',  { desc: 'Вы можете указать дополнительную информацию о раздаваемом материале. Допустимо использовать не больше шести вкладок', max: 6 },
     [
     	{ title: 'Треклист', text: '01. Перечень' },
     	{ title: 'Альбомы', text: 'Год - Перечень' },
     	{ title: 'Интересно', text: 'Интересные факты' },
     	{
     		title: 'Обложки', text: '[url=https://полноразмерная_обложка][img]https://обложка_180х.jpg[/img][/url]',
     		control: new BBList('Обложки', 'url', 6, 'Ссылка на полноразмерную обложку', 'Ссылка на превью шириной 180 пикс', { innerTag: 'img', columns: 3, add: 'обложку', toggle: true, nosh: 1 })
     	}
     ])
	],
	advanced: '[searchm=Подобные раздачи]Поисковое слово, название[/searchm]\n[linkm=Инфо]https://ссылка[/linkm]\n\n[pagesd=Треклист]01. Перечень[/pagesd]'
   }
 },

 { title: 'Игра',
   predesc: {
    simple: new Set([
     new Text('Название:'),
     new Text('Оригинальное название:'),
     new Text('Год выпуска:'),
     new Text('Жанр:'),
     new Text('Разработчик:'),
     new Text('Выпущено:'),
	 new Text('Версия:'),
     new Combo('Язык:', ['Русский','Английский','Немецкий','Французский','Испанский','Итальянский'], { multi: 1, listwidth: '130px' })
    ]),
    advanced: '[b]Название:[/b] Название\n[b]Оригинальное название:[/b] Name\n[b]Год выпуска:[/b] 2009\n[b]Жанр:[/b] Action, shooter, racing, strategy\n[b]Разработчик:[/b] Наименование компании\n[b]Выпущено:[/b] Наименование издательства\n[b]Версия:[/b] 1.0\n[b]Язык:[/b] Русский, английский'
   },
   desc: {
    simple: new Set([
	 new Text('Об игре:', { type: 1, desc: 'Рекомендуем писать собственное описание, а не копировать его из сети &ndash; это положительно скажется на количестве сидов и пиров и значительно увеличит срок жизни раздачи. Помощь по созданию описаний к раздачам <a href="//forum.kinozal.tv/forumdisplay.php?f=244" target="_blank">здесь</a>' })
	]),
	advanced: '[b]Об игре:[/b] Краткое описание игры...'
   },
   tech: {
    simple: new Set([
	 new Text('Минимальные системные требования:', { type: 2 }),
	 new Text('Операционная система:'),
	 new Text('Процессор:'),
	 new Text('Память:'),
	 new Text('Видеокарта:'),
	 new Text('Аудиокарта:'),
	 new Text('Свободное место:'),
	 new Text('Платформа:', { desc: 'Для мобильных, консольных и интерактивных игр' }),
	 new Text('Занимаемое место:', { desc: 'Для мобильных, консольных и интерактивных игр' })
	]),
	advanced: '[b]Минимальные системные требования:[/b]\n[b]Операционная система:[/b] Windows 10/11 64-бит\n[b]Процессор:[/b] Core i5-8400 / Ryzen 5 2600\n[b]Память:[/b] 8 ГБ\n[b]Видеокарта:[/b] 4 ГБ, GeForce GTX 960 / Radeon R9 380, DirectX 11\n[b]Аудиокарта:[/b] Совместимая с ОС\n[b]Свободное место:[/b] 20 ГБ'
   },
   notes: {
    simple: [
	 new BBList('Меню: поиск раздач', 'searchm', 10, 'Заголовок ссылки', 'Строка поиска', { desc: 'Добавьте ссылки на поиск других раздач, которые могут заинтересовать зрителей' }),
     new BBList('Меню: ознакомление', 'linkm', 10, 'Заголовок ссылки', 'Адрес ссылки'),
     new BBTabs('Дополнительные вкладки', { desc: 'Вы можете указать дополнительную информацию о раздаваемом материале. Допустимо использовать не больше шести вкладок', max: 6 },
     	[
     		{ title: 'Особенности', text: '01. Перечень' },
     		{ title: 'Установка', text: 'Порядок установки и обхода защиты, порядок запуска, для консольных игр сведения о прошивке и регионе' },
     		{ title: 'Дополнительно', text: 'Дополнительные сведения по игре' },
     		{ title: 'Русификация', text: 'Степень русификации игры' }
     	],
     	[
     		{ title: 'Скриншоты', text: '[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]',
			  control: new BBList('Скриншоты', 'url', 6, 'Ссылка на полноразмерный скриншот', 'Ссылка на превью шириной 320 пикс', { innerTag: 'img', columns: 2, add: 'скриншот', toggle: true, nosh: 1, desc: 'Скриншотов должно быть четное количество, с превью шириной 320 пикселей. Разместите скриншоты на <a href="//forum.kinozal.tv/showthread.php?t=78697" target="_blank">хостингах изображений</a>. Для вставки готовых ссылок используйте кнопку «Сменить вид» справа' })
     		}
     	]
     )
	],
	advanced: '[searchm=Подобные раздачи]Поисковое слово, название[/searchm]\n[linkm=Полезная информация]https://ссылка[/linkm]\n\n[pagesd=Особенности]01. Перечень[/pagesd]\n\n[pagesd=Установка]Порядок установки и обхода защиты, порядок запуска, для консольных игр сведения о прошивке и регионе[/pagesd]\n\n[pagesd=Русификация]Степень русификации игры[/pagesd]\n\n[pagesd=Скриншоты]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[/pagesd]'
   }
 },

 { title: 'Аудиокнига',
   predesc: {
    simple: new Set([
     new Text('Автор:'), new Text('Название:'), new Text('Год выпуска:'), new Text('Жанр:'), new Text('Выпущено:'), new Text('Озвучивает:')
    ]),
    advanced: '[b]Автор:[/b] Имя Фамилия\n[b]Название:[/b] Название книги\n[b]Год выпуска:[/b] 2009\n[b]Жанр:[/b] Классика, радиоспектакль, фантастика\n[b]Выпущено:[/b] Название издательства\n[b]Озвучивает:[/b] Имя Фамилия'
   },
   desc: {
    simple: new Set([
	 new Text('Описание:', { type: 1, desc: 'Рекомендуем писать собственное описание, а не копировать его из сети &ndash; это положительно скажется на количестве сидов и пиров и значительно увеличит срок жизни раздачи. Помощь по созданию описаний к раздачам <a href="//forum.kinozal.tv/forumdisplay.php?f=244" target="_blank">здесь</a>' })
	]),
	advanced: '[b]Описание:[/b] Краткая аннотация к книге...'
   },
   tech: {
    simple: new Set([
	 new Text('Аудио:'),
	 new Text('Размер:', { desc: Torrent.supported() ? 'Устанавливается автоматически при выборе торрент-файла' : 'Размер раздаваемого материала в МБ или ГБ', id: 'size4' } ),
	 new Text('Продолжительность:'), new Combo('Язык:', ['Русский', 'Английский'], { listwidth: '150px' })
	]),
	advanced: '[b]Аудио:[/b] MP3, 96 Кбит/с, стерео\n[b]Размер:[/b] 635 МБ\n[b]Продолжительность:[/b] 29:55:41\n[b]Язык:[/b] Русский'
   },
   notes: {
    simple: [
	 new BBList('Меню: поиск раздач', 'searchm', 10, 'Заголовок ссылки', 'Строка поиска', { desc: 'Добавьте ссылки на поиск других раздач, которые могут заинтересовать зрителей' }),
     new BBList('Меню: ознакомление', 'linkm', 10, 'Заголовок ссылки', 'Адрес ссылки'),
     new BBTabs('Дополнительные вкладки', { desc: 'Вы можете указать дополнительную информацию о раздаваемом материале. Допустимо использовать не больше шести вкладок', max: 6 }, [
     	{ title: 'Содержание', text: '01. Перечень' },
     	{ title: 'Цикл', text: '1. Перечень' },
		{ title: 'Об издании', text: 'Прочитано по изданию, обработано, оцифровано, очищено' },
		{ title: 'О спектакле', text: 'Информация о спектакле' },
     	{ title: 'Интересно', text: 'Интересные факты' },
     	{
     		title: 'Обложки', text: '[url=https://полноразмерная_обложка][img]https://обложка_180х.jpg[/img][/url]',
     		control: new BBList('Обложки', 'url', 6, 'Ссылка на полноразмерную обложку', 'Ссылка на превью шириной 180 пикс', { innerTag: 'img', columns: 3, add: 'обложку', toggle: true, nosh: 1 })
     	}
     ])
	],
	advanced: '[searchm=Цикл аудиокниг]Поисковое слово, название[/searchm]\n[searchm=Подобные раздачи]Поисковое слово, название[/searchm]\n[linkm=Послушать]https://ссылка[/linkm]\n\n[pagesd=Содержание]01. Перечень[/pagesd]\n\n[pagesd=Об издании]Прочитано по изданию, обработано, оцифровано, очищено[/pagesd]\n\n[pagesd=Интересно]Интересные факты[/pagesd]\n\n[pagesd=Обложки][url=https://полноразмерная_обложка][img]https://обложка_180х.jpg[/img][/url][/pagesd]'
   }
 },

 { title: 'Программа',
   predesc: {
    simple: new Set([
     new Text('Оригинальное название:'),
     new Text('Год выпуска:'),
     new Combo('Жанр:', ['ОС','Система','CD-DVD','Сеть','Безопасность','Мультимедиа','Графика','Офис','Программирование','САПР','ГИС','Образование','Детские','Прикладная','Каталоги','Руководства','Оборудование','Досуг','Сборник'], { listwidth: '130px', noscroll: 1 }),
     new Text('Разработчик:'),
	 new Text('Версия:'),
     new Combo('Язык:', ['Русский','Английский','Немецкий','Французский','Испанский','Итальянский'], { multi: 1, listwidth: '130px', noscroll: 1 })
    ]),
    advanced: '[b]Оригинальное название:[/b] Program name\n[b]Год выпуска:[/b] 2009\n[b]Жанр:[/b] Безопасность\n[b]Разработчик:[/b] Наименование компании\n[b]Версия:[/b] 1.0\n[b]Язык:[/b] Русский, английский'
   },
   desc: {
    simple: new Set([
	 new Text('О программе:', { type: 1, desc: 'Рекомендуем писать собственное описание, а не копировать его из сети &ndash; это положительно скажется на количестве сидов и пиров и значительно увеличит срок жизни раздачи. Помощь по созданию описаний к раздачам <a href="//forum.kinozal.tv/forumdisplay.php?f=244" target="_blank">здесь</a>' })
	]),
	advanced: '[b]О программе:[/b] Краткое описание программы...'
   },
   tech: {
    simple: new Set([
	 new Text('Минимальные системные требования:', { type: 2 }),
	 new Text('Операционная система:'),
	 new Text('Процессор:'),
	 new Text('Память:'),
	 new Text('Видеокарта:'),
	 new Text('Аудиокарта:'),
	 new Text('Свободное место:'),
	 new Text('Платформа:', { desc: 'Для мобильного и навигационного ПО' }),
	 new Text('Занимаемое место:', { desc: 'Для мобильного и навигационного ПО' })
	]),
	advanced: '[b]Минимальные системные требования:[/b]\n[b]Операционная система:[/b] Windows XP/Vista/7/8\n[b]Процессор:[/b] Pentium 4 2 ГГц\n[b]Память:[/b] 512 МБ\n[b]Видеокарта:[/b] 128 МБ, GeForce FX 5600 / Radeon 9600, 1024х768, Shader 2.0, DirectX 9.0c\n[b]Аудиокарта:[/b] Совместимая с ОС\n[b]Свободное место:[/b] 1 ГБ'
   },
   notes: {
    simple: [
	 new BBList('Меню: поиск раздач', 'searchm', 10, 'Заголовок ссылки', 'Строка поиска', { desc: 'Добавьте ссылки на поиск других раздач, которые могут заинтересовать зрителей' }),
     new BBList('Меню: ознакомление', 'linkm', 10, 'Заголовок ссылки', 'Адрес ссылки'),
     new BBTabs('Дополнительные вкладки', { desc: 'Вы можете указать дополнительную информацию о раздаваемом материале. Допустимо использовать не больше шести вкладок', max: 6 }, [
     	{ title: 'Особенности', text: '01. Перечень' },
     	{ title: 'Установка', text: 'Порядок запуска, порядок установки' },
     	{ title: 'Дополнительно', text: 'Дополнительные сведения по программе' },
     	{ title: 'Русификация', text: 'Степень русификации программы' }
     ],
     [
     	{
     		title: 'Скриншоты', text: '[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]',
     		control: new BBList('Скриншоты', 'url', 6, 'Ссылка на полноразмерный скриншот', 'Ссылка на превью шириной 320 пикс', { innerTag: 'img', columns: 2, add: 'скриншот', toggle: true, nosh: 1, desc: 'Скриншотов должно быть четное количество, с превью шириной 320 пикселей. Разместите скриншоты на <a href="//forum.kinozal.tv/showthread.php?t=78697" target="_blank">хостингах изображений</a>. Для вставки готовых ссылок используйте кнопку «Сменить вид» справа' })
     	}
     ])
	],
	advanced: '[searchm=Подобные раздачи]Поисковое слово, название[/searchm]\n[linkm=Полезная информация]https://ссылка[/linkm]\n[linkm=Версия программы]https://полноразмерный_скриншот[/linkm]\n\n[pagesd=Особенности]01. Перечень[/pagesd]\n\n[pagesd=Установка]Порядок запуска, порядок установки[/pagesd]\n\n[pagesd=Русификация]Степень русификации программы[/pagesd]\n\n[pagesd=Скриншоты]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[/pagesd]'
   }
 },

 { title: 'Библиотека',
   predesc: {
    simple: new Set([
     new Text('Автор:'),
     new Text('Название:'),
     new Text('Оригинальное название:'),
     new Text('Год выпуска:'),
	 new Text('Серия:'),
     new Text('Жанр:'),
     new Text('Выпущено:'),
     new Combo('Язык:', ['Русский','Русский (старая орфография)','Английский','Немецкий','Латинский','Французский','Украинский'], {  multi: 1, listwidth: '185px' })
    ]),
    advanced: '[b]Автор:[/b] Имя Фамилия\n[b]Название:[/b] Название публикации\n[b]Оригинальное название:[/b] Publication title\n[b]Год выпуска:[/b] 2009\n[b]Жанр:[/b] Периодика, раритеты, журнал\n[b]Выпущено:[/b] Страна, город, название издательства\n[b]Язык:[/b] Русский'
   },
   desc: {
    simple: new Set([
	 new Text('Описание:', { type: 1, desc: 'Рекомендуем писать собственное описание, а не копировать его из сети &ndash; это положительно скажется на количестве сидов и пиров и значительно увеличит срок жизни раздачи. Помощь по созданию описаний к раздачам <a href="//forum.kinozal.tv/forumdisplay.php?f=244" target="_blank">здесь</a>' })
	]),
	advanced: '[b]Описание:[/b] Описание публикации...'
   },
   tech: {
    simple: new Set([
	 new Combo('Формат:', ['PDF','DjVu','FB2','JPEG'], { listwidth: '100px' }),
     new Combo('Качество:', ['eBook','Электронная копия','Отсканированные страницы','Отсканированные страницы (OCR)','Оригинал-макет'], { listwidth: '195px' }),
     new Text('Размеры изображений:'),
     new Text('Размеры листа:'),
     new Text('Разрешение:'),
     new Text('Глубина цвета:'),
     new Text('Количество страниц:'),
     new Text('Размер:', { desc: Torrent.supported() ? 'Устанавливается автоматически при выборе торрент-файла' : 'Размер раздаваемого материала в МБ или ГБ', id: 'size6' } )
	]),
	advanced: '[b]Формат:[/b] PDF\n[b]Качество:[/b] Отсканированные страницы\n[b]Размеры изображений:[/b] от 2249х3350 до 2250х3350\n[b]Размеры листа:[/b] 204x292 мм, А4\n[b]Разрешение:[/b] 72 пикс/дюйм, 300 пикс/дюйм\n[b]Глубина цвета:[/b] 8 бит, 24 бит\n[b]Количество страниц:[/b] 24\n[b]Размер:[/b] 500 МБ'
   },
   notes: {
    simple: [
	 new BBList('Меню: поиск раздач', 'searchm', 10, 'Заголовок ссылки', 'Строка поиска', { desc: 'Добавьте ссылки на поиск других раздач, которые могут заинтересовать зрителей' }),
     new BBList('Меню: ознакомление', 'linkm', 10, 'Заголовок ссылки', 'Адрес ссылки'),
     new BBTabs('Дополнительные вкладки', { desc: 'Вы можете указать дополнительную информацию о раздаваемом материале. Допустимо использовать не больше шести вкладок', max: 6 }, [
     	{ title: 'Содержание', text: '01. Перечень\n[url=https://полноразмерный_скриншот]Страница-1[/url] | [url=https://полноразмерный_скриншот]Страница-2[/url]' },
		{ title: 'Интересно', text: 'Интересные факты' },
     	{ title: 'Информация', text: 'ISBN: Номер_ISBN | ISSN: Номер_ISSN' },
     	{ title: 'Приложение', text: 'Дополнительные материалы, системные требования, запуск' },
     ],
     [
     	{
     		title: 'Скриншоты', text: '[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]',
     		control: new BBList('Скриншоты', 'url', 6, 'Ссылка на полноразмерный скриншот', 'Ссылка на превью шириной 320 пикс', { innerTag: 'img', columns: 2, add: 'скриншот', toggle: true, nosh: 1, desc: 'Скриншотов должно быть четное количество, с превью шириной 320 пикселей. Разместите скриншоты на <a href="//forum.kinozal.tv/showthread.php?t=78697" target="_blank">хостингах изображений</a>. Для вставки готовых ссылок используйте кнопку «Сменить вид» справа' })
     	}
     ])
	],
	advanced: '[searchm=Подобные раздачи]Поисковое слово, название[/searchm]\n[linkm=Полезная информация]https://ссылка[/linkm]\n\n[pagesd=Содержание]\n01. Перечень\n[url=https://полноразмерный_скриншот]Страница-1[/url] | [url=https://полноразмерный_скриншот]Страница-2[/url]\n[/pagesd]\n\n[pagesd=Интересно]Интересные факты[/pagesd]\n\n[pagesd=Информация]ISBN: Номер ISBN | ISSN: Номер ISSN[/pagesd]\n\n[pagesd=Скриншоты]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[/pagesd]'
   }
 },

 { title: 'Графика',
   predesc: {
    simple: new Set([
     new Text('Название:'),
     new Text('Оригинальное название:'),
     new Text('Год выпуска:'),
     new Combo('Жанр:', ['Арт','Фотографии','Фотоарт','Клипарты','Рамки','Виньетки','Обои для рабочего стола','Компьютерная графика'], { listwidth: '150px' }),
     new Text('Выпущено:'),
     new Text('Составитель:')
    ]),
    advanced: '[b]Название:[/b] Название\n[b]Оригинальное название:[/b] Title\n[b]Год выпуска:[/b] 2009\n[b]Жанр:[/b] Фотографии\n[b]Выпущено:[/b] Название издательства\n[b]Составитель:[/b] '
   },
   desc: {
    simple: new Set([
	 new Text('Описание:', { type: 1, desc: 'Рекомендуем писать собственное описание, а не копировать его из сети &ndash; это положительно скажется на количестве сидов и пиров и значительно увеличит срок жизни раздачи. Помощь по созданию описаний к раздачам <a href="//forum.kinozal.tv/forumdisplay.php?f=244" target="_blank">здесь</a>' })
	]),
	advanced: '[b]Описание:[/b] Описание раздачи...'
   },
   tech: {
    simple: new Set([
	 new Combo('Формат:', ['JPEG','PSD','PNG'], { listwidth: '100px' }),
     new Text('Размеры изображений:'),
     new Text('Разрешение:'),
     new Text('Глубина цвета:'),
     new Text('Количество:'),
     new Text('Размер:', { desc: Torrent.supported() ? 'Устанавливается автоматически при выборе торрент-файла' : 'Размер раздаваемого материала в МБ или ГБ', id: 'size7' })
	]),
	advanced: '[b]Формат:[/b] JPEG\n[b]Размеры изображений:[/b] 5000х4002, 6600х3004\n[b]Разрешение:[/b] 72 пикс/дюйм, 96 пикс/дюйм\n[b]Глубина цвета:[/b] от 2 бит до 24 бит\n[b]Количество:[/b] 920\n[b]Размер:[/b] 887 МБ'
   },
   notes: {
    simple: [
	 new BBList('Меню: поиск раздач', 'searchm', 10, 'Заголовок ссылки', 'Строка поиска', { desc: 'Добавьте ссылки на поиск других раздач, которые могут заинтересовать зрителей' }),
     new BBList('Меню: ознакомление', 'linkm', 10, 'Заголовок ссылки', 'Адрес ссылки'),
     new BBTabs('Дополнительные вкладки', { desc: 'Вы можете указать дополнительную информацию о раздаваемом материале. Допустимо использовать не больше шести вкладок', max: 6 }, [
     	{ title: 'Обзор', text: 'Сводные листы изображений:\n[url=https://сводный_лист]Лист-1[/url] | [url=https://сводный_лист]Лист-2[/url] | [url=https://сводный_лист]Лист-3[/url]'},
     	{ title: 'Интересно', text: 'Интересные факты' }
     ],
     [
     	{
     		title: 'Скриншоты', text: '[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]',
     		control: new BBList('Скриншоты', 'url', 6, 'Ссылка на полноразмерный скриншот', 'Ссылка на превью шириной 320 пикс', { innerTag: 'img', columns: 2, add: 'скриншот', toggle: true, nosh: 1, desc: 'Скриншотов должно быть четное количество, с превью шириной 320 пикселей. Разместите скриншоты на <a href="//forum.kinozal.tv/showthread.php?t=78697" target="_blank">хостингах изображений</a>. Для вставки готовых ссылок используйте кнопку «Сменить вид» справа' })
     	}
     ])
	],
	advanced: '[searchm=Подобные раздачи]Поисковое слово, название[/searchm]\n[linkm=Полезная информация]https://ссылка[/linkm]\n\n[pagesd=Обзор]\nСводные листы изображений:\n[url=https://сводный_лист]Лист-1[/url] | [url=https://сводный_лист]Лист-2[/url] | [url=https://сводный_лист]Лист-3[/url]\n[/pagesd]\n\n[pagesd=Скриншоты]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url] [url=https://полноразмерный_скриншот][img]https://скриншот_320х.jpg[/img][/url]\n[/pagesd]'
   }
 }
];

// upload.js
Upl=function(){function k(a,c,b){var f=form[h-1][l[a]].simple,e=UF.el("f"+(a+1)),m=UF.el("d"+(a+1));if(c){b=n(f);for(e.style.display="none";e.hasChildNodes();)e.removeChild(e.lastChild);m.value=b;m.style.display="block"}else{var d=bbParse(m.value);if(f instanceof Array)for(var j=0;j<f.length;++j)f[j].parse(d);else f.parse(d);d.skipEmpty(0);if((d=strTrim(d.toString()))&&(!b||!v(d)))return p(f),!1;w(f,e);m.style.display="none";e.style.display="block";2==a&&x()}g[a]=c;return!0}function p(a){if(a instanceof
Array)for(var c=0;c<a.length;++c)a[c].clear();else a.clear()}function n(a,c){var b=[];if(a instanceof Array)for(var f=0;f<a.length;++f){var e=a[f];e.isEmpty()||b.push(e.toString());c||e.clear()}else a.isEmpty()||(b.push(a.toString()),c||a.clear());return b.join("\n")}function w(a,c){if(a instanceof Array)for(var b=0;b<a.length;++b)c.appendChild(a[b].get());else c.appendChild(a.get())}function v(a){return confirm("\u041f\u0440\u0438 \u0441\u043c\u0435\u043d\u0435 \u0440\u0435\u0436\u0438\u043c\u0430 \u043f\u0440\u043e\u043f\u0430\u0434\u0435\u0442 \u0447\u0430\u0441\u0442\u044c \u043e\u0444\u043e\u0440\u043c\u043b\u0435\u043d\u0438\u044f:\n\n"+
a+"\n\n\u0412\u044b \u0434\u0435\u0439\u0441\u0442\u0432\u0438\u0442\u0435\u043b\u044c\u043d\u043e \u0436\u0435\u043b\u0430\u0435\u0442\u0435 \u043f\u0435\u0440\u0435\u043a\u043b\u044e\u0447\u0438\u0442\u044c\u0441\u044f \u043d\u0430 \u043e\u0431\u044b\u0447\u043d\u044b\u0439 \u0440\u0435\u0436\u0438\u043c?")}function q(a){UF.el("m"+a).className="bx1 up_tmpl up_tmpls"}function y(a){UF.el("m"+a).className="bx1 sbab up_tmpl"}function j(a,c){alert("\u041e\u0448\u0438\u0431\u043a\u0430: "+c);for(var b=
a.parentNode.previousSibling;!b.scrollIntoView;)b=b.previousSibling;b.scrollIntoView();a.focus();return!1}function r(a,c,b,f){var e=document.forms.upt,d=e.elements.name;d.value=strTrim(d.value);if(!f){if(strEmpty(d.value))return j(d,"\u041d\u0435 \u0443\u043a\u0430\u0437\u0430\u043d\u043e \u043d\u0430\u0437\u0432\u0430\u043d\u0438\u0435 \u0440\u0430\u0437\u0434\u0430\u0447\u0438");if(150<d.value.length)return j(d,"\u041d\u0430\u0437\u0432\u0430\u043d\u0438\u0435 \u0440\u0430\u0437\u0434\u0430\u0447\u0438 \u0431\u043e\u043b\u0435\u0435 150 \u0441\u0438\u043c\u0432\u043e\u043b\u043e\u0432")}if(b){b=
e.elements.file;if(strEmpty(b.value))return j(b,"\u041d\u0435 \u0443\u043a\u0430\u0437\u0430\u043d \u0442\u043e\u0440\u0440\u0435\u043d\u0442-\u0444\u0430\u0439\u043b");d=b.value.lastIndexOf(".");if(-1==d||"torrent"!=b.value.substring(d+1,b.value.length).toLowerCase())return j(b,"\u042d\u0442\u043e\u0442 \u0444\u0430\u0439\u043b \u043d\u0435 \u0442\u043e\u0440\u0440\u0435\u043d\u0442-\u0444\u0430\u0439\u043b")}for(b=0;b<l.length;++b)g[b]||(UF.el("d"+(b+1)).value=n(form[h-1][l[b]].simple,1));for(b=
0;b<s.length;++b){var k=e.elements["desc"+(b+1)];if(3!=b&&strEmpty(k.value))return j(k,"\u0412\u044b \u043d\u0435 \u0432\u043d\u0435\u0441\u043b\u0438 "+s[b]);d=k.value.length-z[b];if(0<d)return a=k,c="\u0412\u044b \u043d\u0430 "+d+" ",f=d%10,e=d%100,f=1==f&&11!=e?"\u0441\u0438\u043c\u0432\u043e\u043b":2==f&&12!=e||3==f&&13!=e||4==f&&14!=e?"\u0441\u0438\u043c\u0432\u043e\u043b\u0430":"\u0441\u0438\u043c\u0432\u043e\u043b\u043e\u0432",j(a,c+f+" \u043f\u0440\u0435\u0432\u044b\u0441\u0438\u043b\u0438 \u0434\u043e\u043f\u0443\u0441\u0442\u0438\u043c\u044b\u0439 \u043b\u0438\u043c\u0438\u0442 ("+
z[b]+" \u0441\u0438\u043c\u0432\u043e\u043b\u043e\u0432) \u0443 \u043f\u043e\u043b\u044f \u00ab"+s[b]+"\u00bb")}b=UF.el("type"+h);if(!f&&0==b.value)return j(b,"\u041d\u0435 \u0432\u044b\u0431\u0440\u0430\u043d \u0440\u0430\u0437\u0434\u0435\u043b");UF.el("type").value=b.value;e.action=a;e.target=c;e.submit()}function A(a){t=a;g[2]||x()}function x(){if(t){var a=UF.el("size"+h);a&&(a.value=t)}}var u=1,d=1,l=["predesc","desc","tech","notes"],s=["\u041f\u0440\u0435\u0434\u0432\u0430\u0440\u0438\u0442\u0435\u043b\u044c\u043d\u043e\u0435 \u043e\u043f\u0438\u0441\u0430\u043d\u0438\u0435",
"\u041e\u043f\u0438\u0441\u0430\u043d\u0438\u0435","\u0422\u0435\u0445\u043d\u0438\u0447\u0435\u0441\u043a\u0438\u0435 \u0434\u0430\u043d\u043d\u044b\u0435","\u041e\u0444\u043e\u0440\u043c\u043b\u0435\u043d\u0438\u0435, \u0432\u043a\u043b\u0430\u0434\u043a\u0438, \u043f\u0440\u0438\u043c\u0435\u0447\u0430\u043d\u0438\u044f, \u0441\u043a\u0440\u0438\u043d\u0448\u043e\u0442\u044b"],z=[900,12E3,1200,16E3],g=[1,1,1,1],h=0,t;return{showTemplates:function(){for(var a=UF.el("tlist"),c=0;c<form.length;++c){var b=
UF.newEl("li","up_tmpl bx1 sbab");b.appendChild(UF.newText(form[c].title));b.tmpl=c+1;b.onclick=function(){Upl.setTemplate(this.tmpl)};a.appendChild(b);form[c].domTitle=b}Torrent.supported()&&Torrent.setListener(document.forms.upt.elements.file,A)},setTemplate:function(a){if(a!=h){if(0!=h){form[h-1].domTitle.className="up_tmpl bx1 sbab";UF.el("type"+h).className="w250 up_hide";for(var c=0;c<l.length;++c)if(!g[c]){var b=form[h-1][l[c]].simple;u?p(b):UF.el("d"+(c+1)).value=n(b);for(b=UF.el("f"+(c+1));b.hasChildNodes();)b.removeChild(b.lastChild)}}h=
a;if(c=form[a-1].domTitle)c.className="bx1 up_tmpl up_tmpls";UF.el("type"+a).className="w250";for(c=0;c<l.length;++c)u&&(UF.el("d"+(c+1)).value=form[a-1][l[c]].advanced),g[c]||k(c,0)}},switchMode:function(a){k(a,!g[a],1)&&(-1==d?g[0]&&g[1]&&g[2]&&g[3]?(d=1,q(1)):!g[0]&&(!g[1]&&!g[2]&&!g[3])&&(d=0,q(0)):d^g[a]&&(y(d),d=-1))},build:w,skip:v,changeMode:function(a){if(a!=d){-1!=d&&y(d);d=a;q(a);for(a=0;4>a;++a)g[a]!=d&&k(a,d)}},upload:function(){r("/takeupload.php","_self",1,0)},test:function(){r("/detailstest.php",
"_blank",0,1)},edit:function(){r("/takeedit.php","_self",0,0)},editMode:function(){u=0},reset:function(){if(confirm("\u0412\u044b \u0434\u0435\u0439\u0441\u0442\u0432\u0438\u0442\u0435\u043b\u044c\u043d\u043e \u0436\u0435\u043b\u0430\u0435\u0442\u0435 \u0441\u0431\u0440\u043e\u0441\u0438\u0442\u044c \u0432\u043d\u0435\u0441\u0435\u043d\u043d\u044b\u0435 \u0438\u0437\u043c\u0435\u043d\u0435\u043d\u0438\u044f?")){document.forms.upt.reset();for(var a=0;a<l.length;++a)g[a]||(p(form[h-1][l[a]].simple),
k(a,0)||(g[a]=1,UF.el("f"+(a+1)).style.display="none",UF.el("d"+(a+1)).style.display="block"))}}}}();