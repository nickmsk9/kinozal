/// ALL HTML 
var p_arr2 =new Array();
var tb2_old=-1;
function settab2(tb) {
if(tb2_old==-1) { p_arr2[100]=$('#tabs2').html(); tb2_old=100; }
$('#tbch2'+tb2_old).attr('class', '');
$('#tbch2'+tb).attr('class', 'mn');
tb2_old=tb;
if(p_arr2[tb]) {$('#tabs2').html(p_arr2[tb]); return false; }
return true;
}



function showtab2(id, tb) {
	if(settab2(tb)) {
		$('#tabs2').html('<div class=pad5x5>Загрузка...</div>');
		$.get('/ajax/details_get.php?id='+id+'&sr='+tb, function(s) {$('#tabs2').html(s); p_arr2[tb]=s;} );
	}
}


function cat(id) {
	document.location.href="/browse.php?c="+id;
	return false;
}
function getRetio() {
	if(!$('#user_retio').html()){
		$('#user_retio').html('<div class="pad5x5 b">Загрузка...</div>');
		$('#user_retio').load('/get_srv_user_retio.php');
	} else $('#user_retio').html('');
	return false;
}
/// ALL HTML 
/// DETAILS
var mode="";
var pg_array = new Array(); 

var voted=0;
var p_arr =new Array();
var tb_old=-1;

function showcontainer() { $('#container').show(); }
function hidecontainer() { $('#container').hide(); mode='none'; }
function setheader(title) { $('#containerheader').html('<span class="bulet"></span>' + title + ''); }
function get_torm(id,act,zag,sub) {
	if(mode==act) return;
	mode=act;
	setheader(zag);
	showcontainer();
	if(!pg_array[act]){
		$('#containerdata').html('Загрузка...');
		$.get("/get_srv_details.php?id="+id+"&action="+act+sub, function(s){
		pg_array[act] = s;
		if(act == 10) {
			var pos = s.search("<!>");
			if(pos != -1) {
				var res = s.split("<!>");
				data = "^"+ res[0];
				seed = get_peerstab(0)
				if(seed!="") pg_array[act] = "<div class=bx2_0><table class='tables3 w100p'><tbody><tr class='mn'><td width=140 nowrap>Ник</td><td align=right>Залил</td><td align=right>Соотн.</td><td align=right>Скачал</td><td align=right>Соотн.</td><td align=right>Рейтинг</td><td align=right>Подкл.</td></tr>"+seed+"</table></div>";
			}
		}
		if(act == 11) {
			var pos = s.search("<!>");
			if(pos != -1)  {
				var res = s.split("<!>");
				data = "^"+ res[1];
				peer = get_peerstab(1)
				if(peer!="") pg_array[act] = "<div class=bx2_0><table class='tables3 w100p'><tbody><tr class='mn'><td width=140 nowrap>Ник</td><td align=right>Залил</td><td align=right>Соотн.</td><td align=right>Скачал</td><td align=right>Соотн.</td><td align=right>Рейтинг</td><td align=right>%</td><td align=right>Подкл.</td></tr>"+peer+"</table></div>";
			}
		}
		if(act == 3) {
			var res = s.split("<!>");
			if(res[2]=="OK") {
				data = "^"+ res[1];
				pg_array[act] = draw_users_to();
			}
		}
		$('#containerdata').html(pg_array[act]); } );
	} else { $('#containerdata').html(pg_array[act]); }
}


function vote(id, rat) {
if(voted>1) return;
voted++;
$('#starbar').addClass("user");
$('#starbar').width((rat * 20) + 'px');
$('#ratio_get').html('Загрузка...');
$.get("/get_srv_details.php?id="+id+"&action=4&rat="+rat, function(s){$('#ratio_get').html(s); } );
}

function settab(tb) {
if(tb_old==-1) { p_arr[100]=$('#tabs').html(); tb_old=100; }
$('#tbch'+tb_old).attr('class', '');
$('#tbch'+tb).attr('class', 'mn');
tb_old=tb;
if(p_arr[tb]) {$('#tabs').html(p_arr[tb]); return false; }
return true;
}
function showtab(id, tb) {
	if(settab(tb)) {
		$('#tabs').html('Загрузка...');
		$.get('/get_srv_details.php?id='+id+'&pagesd='+tb, function(s) {$('#tabs').html(s); p_arr[tb]=s;} );
	}
}

function c_replay(id,name) {
$('#testcomm').html($('#cm'+id).html());
$('#testcomm a').remove();
$('#testcomm fieldset').remove();
$('#testcomm blockquote').remove();
$('#testcomm').html($('#testcomm').html().replace( /<br[^>]*>/gi, "\n").replace( /(\r\n|\r|\n)+/g, "\n"));
$('#text').val('\n\n' + '[quote='+name+']'+  $.trim($('#testcomm').text()) +'[/quote]');
showcomm(1);
return false;
}

function c_del(id,subid) {
if(confirm('Вы уверены что хотите удалить комментарий №'+id)) {
$.get("/comment.php", { id: subid, action: "del", cid: id },
   function(data){
	 $('#cm'+id).html(data);
   });
}
return false;
}
function c_red(id,subid) {
document.location.href="/comment.php?id="+subid+"&action=modifycomment&cid="+id;
return false;
}
function cmt_submit() {
	df = document.forms["cmt"];
	if(df.text.value.length<10) {
		alert('Комментарий не может быть меньше 10 символов!');
		return false;
	}
	return true;
}
function showcomm(id) {
	if(id==1) $('#cmtcomm').show();
	else if(id==2) $('#cmtcomm').hide();
	else $('#cmtcomm').togle();
	$('#cmfoc').focus();
}

function mess_out(mes) {
	ht = document.getElementsByTagName("html");
	document.body.style.filter = "progid:DXImageTransform.Microsoft.BasicImage(grayscale=1)";
	if (confirm(mes)) { document.body.style.filter = ""; return true; 	}
	else { document.body.style.filter = ""; return false;}
}

function get_string() {
var doc="";
while((tmp=data.charAt(i++))) {
 if(tmp=="^") return doc
 else doc+=tmp
}
return tmp;
}

function draw_usersarray() {
d1=new Array(); d2=new Array(); d3=new Array(); d4=new Array(); 
d5=new Array(); d6=new Array(); d7=new Array();
i=0;x=0;var t_x;
date=get_string();
while(doc=get_string()) {
	t_x=0;
	d1[x]=""; d2[x]=""; d3[x]=""; d4[x]=""; d5[x]=""; d6[x]="";d7[x]="";
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d1[x]+=tmp; //id
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d2[x]+=tmp; //name
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d3[x]+=tmp; //class
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d4[x]+=tmp; //gen
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d5[x]+=tmp; //grup
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d6[x]+=tmp;//cub
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d7[x]+=tmp;//park
	x++;
	}
d = document;
y=-1;
while(d2[++y]) {
	if(y>0) d.write(", ");
	d.write("<a href='/userdetails.php?id="+d1[y]+"' class=u"+d3[y]+">"+d2[y]+"</a>");
	if(d4[y]>0) d.write("<i class=\"i1 s_dv\"></i>");
	if(d5[y]>0) d.write("<i class=\"i1 s"+d5[y]+"\"></i>");
	if(d6[y]>0) d.write("<i class=\"i1 cb"+d6[y]+"\"></i>");
	if(d7[y]>0) d.write("<i class=\"i1 s_park\"></i>");
}
}

function draw_users_to() {
d1=new Array(); d2=new Array(); d3=new Array(); d4=new Array(); 
d5=new Array(); d6=new Array(); d7=new Array();
i=0;x=0;var t_x;
date=get_string();
while(doc=get_string()) {
	t_x=0;
	d1[x]=""; d2[x]=""; d3[x]=""; d4[x]=""; d5[x]=""; d6[x]="";d7[x]="";
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d1[x]+=tmp; //id
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d2[x]+=tmp; //name
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d3[x]+=tmp; //class
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d4[x]+=tmp; //gen
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d5[x]+=tmp; //grup
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d6[x]+=tmp;//cub
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d7[x]+=tmp;//park
	x++;
	}
var txt_to;
txt_to="";
y=-1;
while(d2[++y]) {
	if(y>0) txt_to += ", ";
	txt_to += "<a href='/userdetails.php?id="+d1[y]+"' class=u"+d3[y]+">"+d2[y]+"</a>";
	if(d4[y]>0) txt_to += "<i class=\"i1 s_dv\"></i>";
	if(d5[y]>0) txt_to += "<i class=\"i1 s"+d5[y]+"\"></i>";
	if(d6[y]>0) txt_to += "<i class=\"i1 cb"+d6[y]+"\"></i>";
	if(d7[y]>0) txt_to += "<i class=\"i1 s_park\"></i>";
}

return txt_to;
}

function draw_userssmall() {
d1=new Array(); d2=new Array(); d3=new Array();
i=0;x=0;var t_x;
date=get_string();
while(doc=get_string()) {
	t_x=0;
	d1[x]=""; d2[x]=""; d3[x]="";
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d1[x]+=tmp; //id
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d2[x]+=tmp; //name
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d3[x]+=tmp; //class
	x++;
	}
d = document;
y=-1;
while(d2[++y]) {
	if(y>0) d.write(", ");
	d.write("<a href='/userdetails.php?id="+d1[y]+"' class=u"+d3[y]+">"+d2[y]+"</a>");
}
}

function get_peerstab(leecher) {
ret="";
d1=new Array(); d2=new Array(); d3=new Array(); d4=new Array(); 
d5=new Array(); d6=new Array(); d7=new Array(); 

d8=new Array(); d9=new Array(); d10=new Array(); d11=new Array(); d12=new Array(); 
d13=new Array(); d14=new Array(); d15=new Array(); d16=new Array(); d17=new Array(); d18=new Array();
i=0;x=0;var t_x; 
date=get_string();
while(doc=get_string()) {
	t_x=0;
	d1[x]=""; d2[x]=""; d3[x]=""; d4[x]=""; d5[x]=""; d6[x]="";d7[x]="";d8[x]="";
	d9[x]="";d10[x]="";d11[x]="";d12[x]="";d13[x]="";d14[x]="";d15[x]="";d16[x]="";d17[x]="";d18[x]="";
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d1[x]+=tmp; //id
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d2[x]+=tmp; //name
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d3[x]+=tmp; //class
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d4[x]+=tmp; //gen
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d5[x]+=tmp; //grup
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d6[x]+=tmp; //cub
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d7[x]+=tmp; //strana

	while((tmp=doc.charAt(t_x++))&&tmp!="|") d8[x]+=tmp; //zalil
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d9[x]+=tmp; //procza
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d10[x]+=tmp; //skachal
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d11[x]+=tmp; //procsk
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d12[x]+=tmp; //retio
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d13[x]+=tmp; //retiocolor
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d14[x]+=tmp; //podtime
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d15[x]+=tmp; //connect
	while((tmp=doc.charAt(t_x++))&&tmp!="|") d16[x]+=tmp; //procent
	x++;
	}
y=-1;
while(d2[++y]) {
	ret += "<tr><td nowrap><img class='i2 c"+d7[y]+"' src=/pic/emty.gif>  ";
	ret += "<a href='/userdetails.php?id="+d1[y]+"' class=u"+d3[y]+">"+d2[y]+"</a>";
	if(d4[y]>0) ret +="<i class=\"i1 s_dv\"></i>";
	if(d5[y]>0) ret += "<i class=\"i1 s"+d5[y]+"\"></i>";
	if(d6[y]>0) ret += "<i class=\"i1 cb"+d6[y]+"\"></i>";
	ret += "<td align=right class=small>";
	ret += d8[y];
	ret += "<td align=right class=small>"+d9[y]+"/с</td>";
	ret += "<td align=right class=small>"+d10[y]+"</td>";
	ret += "<td align=right class=small>"+d11[y]+"/с</td>";
	ret += "<td align=right class=small><b><font color="+d13[y]+">"+d12[y]+"</font></b></td>";
	if(leecher==1) ret += "<td align=right class=small>"+d16[y]+"%</td>";
	ret += "<td align=right class=small>"+d14[y]+"</td>";
}
return ret;
}
