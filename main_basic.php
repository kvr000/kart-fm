<?

function std_format_time($t)
{
	return strftime("%Y-%m-%d %H:%M:%S %Z", $t);
}


function log_internal($str)
{
	if (($l = fopen("log/internal.log", "a"))) {
		fputs($l, $str);
		fclose($l);
	}
}

function std_html_log_access()
{
	global $HTTP_SERVER_VARS;

	if (($fa = @fopen("log/mb_access.log", "a"))) {
		$rh = $HTTP_SERVER_VARS['REMOTE_ADDR'];
		if (!is_null($HTTP_SERVER_VARS['REMOTE_HOST']))
			$rh = $HTTP_SERVER_VARS['REMOTE_HOST'];
		if (!is_null($HTTP_SERVER_VARS['ORIGINAL_REMOTE_HOST']))
			$rh = $HTTP_SERVER_VARS['ORIGINAL_REMOTE_HOST']."*".$rh;
		elseif (!is_null($HTTP_SERVER_VARS['HTTP_X_FORWARDED_FOR']))
			$rh = ereg_replace("^.*, *", "", $HTTP_SERVER_VARS['HTTP_X_FORWARDED_FOR'])."*".$rh;
		$ref = "unknown";
		if (!is_null($HTTP_SERVER_VARS['HTTP_REFERER']))
			$ref = $HTTP_SERVER_VARS['HTTP_REFERER'];
		$req = $HTTP_SERVER_VARS['REQUEST_METHOD']." ".$HTTP_SERVER_VARS['REQUEST_URI'];
		$uang = $HTTP_SERVER_VARS['HTTP_USER_AGENT'];
		$tim = strftime("%Y-%m-%d %H:%M:%S");
		#@fputs($fa, sprintf("%s, %s: %s (%s), %s\n", $tim, $req, $rh, $ref, $uang));
		@fputs($fa, sprintf("%s - - [%s] \"%s\" 0 0 \"%s\" \"%s\"\n", $rh, $tim, $req, $ref, $uang));
		fclose($fa);
	}
}

# read html file without advertising, advertising is always printed via std_html_finish
function readfile_htmlstrip($file)
{
	return readfile($file);
#	if (!($fh = fopen($file, "r")))
#		return false;
#	if (fseek($fh, -2048, SEEK_END) < 0)
#		rewind($fh); # expect advert to be in last 2k max
#	$cont = fread($fh, 2048);
#	$s = ftell($fh);
#	rewind($fh);
#	if (($l = strpos($cont, "<!--WZ-REKLAMA-")) === false) {
#		fpassthru($fh);
#	}
#	else {
#		$s -= strlen($cont)-$l;
#		log_internal(sprintf("obsolete: WZ-REKLAMA in %s:%d\n", $file, $s));
#		while ($s > 0) {
#			$tr = ($s > 16384)?16384:$s;
#			$c = fread($fh, $tr); # does it return false or what on EOF ?
#			if ($c === false || strlen($c) == 0)
#				break;
#			print($c);
#			$s -= strlen($c);
#		}
#		fclose($fh);
#	}
	return true;
}

function get_current_address()
{
	global $HTTP_SERVER_VARS;

	return "http".($HTTP_SERVER_VARS['HTTPS'] == "on"?"s":"")."://".$HTTP_SERVER_VARS['SERVER_NAME'].$HTTP_SERVER_VARS['REQUEST_URI'];
}

function show_poll($dom, $poll_num)
{
	global $SERVER_NAME;
	global $REQUEST_URI;

	global $poll_last;

	if (!($fh = file("polls/$dom$poll_num.count")))
		return;
	if (!($fd = file("polls/$dom$poll_num.def")))
		return;

	echo array_shift($fd);
	$total = array_shift($fh);

	echo "<form action=\"poll.php\">\n";
	echo "<input type=hidden name=\"poll_dom\" value=\"$dom\">\n";
	echo "<input type=hidden name=\"poll_id\" value=$poll_num>\n";
	echo "<input type=hidden name=referer value=\"".htmlspecialchars(get_current_address())."\">\n";
	echo "<table class=lother width=\"100%\">\n";

	for ($id = 0; count($fd) > 0; $id++)
		echo "<tr><td><input type=radio name=poll_answ value=$id>".array_shift($fd)."</td><td align=right>".(($total == 0)?"0%":sprintf("%d%%", 100*array_shift($fh)/$total))."</td></tr>\n";

	if (ereg("$dom@([0-9]+)", $poll_last, $regs))
		$pl = $regs[1];
	else
		$pl = 0;
	echo "<tr><td>".(($pl < $poll_num)?"<input type=submit name=ac_vote value=Hlasuj>":"")."</td><td align=right>$total hlasů</td></tr>\n";
	echo "</table>\n";
	echo "</form>\n";
}

function std_html_not_found()
{
	global $HTTP_SERVER_VARS;

	header("HTTP/1.0 404 Not Found");
	std_html_start("Stránka nebyla nalezena");
	echo "<h1>Stránka nebyla nalezena</h1>\n";
	$page_addr = htmlspecialchars(get_current_address());
	echo "(<a href=\"$page_addr\">$page_addr</a>)<br>\n";
	std_html_finish();
}

function std_html_bad_input()
{
	global $HTTP_SERVER_VARS;

	header("HTTP/1.0 404 Bad Request");
	std_html_start("Neočekávaný požadavek");
	echo "<h1>Neočekávaný požadavek</h1>\n";
	$page_addr = htmlspecialchars(get_current_address());
	echo "(<a href=\"$page_addr\">$page_addr</a>)<br>\n";
	std_html_finish();
}

function std_html_start($page_title)
{
	global $season;

	if ($season == "")
		$lseason = 2004;
	else
		$lseason = $season;

	if ($page_title == "")
		$page_title = "Kart Fastest Man";
	else
		$page_title = "$page_title - Kart Fastest Man";

	std_html_log_access();

	header("Content-type: text/html; charset=UTF-8");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://kart-fm.motokary.net/">
<html>

<head>
<meta http-equiv="Content-type" content="text/html; charset=UTF-8">
<meta name="keywords" lang="cs" content="karting, motokáry, kart-fm">
<meta name="author" content="Krysa von Ratteburg, kvr@centrum.cz">
<meta name="robots" content="ALL,FOLLOW">
<meta http-equiv="Reply-to" content="kart-fm@motokary.net">
<link rel=STYLESHEET href="default.css" type="text/css">
<title><? echo $page_title ?></title>
</head>

<body bgcolor="#cdff9c">

<table cellpadding=0 bgcolor="#cdf79c" width="100%"><tr><td>
<table border=0 cellpadding=0 cellspacing=0 width="100%">
	<tr class=mtitle valign=top>
	<td valign=middle><font class=mtitle>Kart Fastest Man</font><td valign=middle><a href="http://www.motokary.net/"><img align=right border=0 src="http://www.motokary.net/img/m_icon.jpg" alt=motokary_logo></a>
	</tr>
	<tr class=lother valign=bottom><td colspan=2>
	<table border=0 cellpadding=0 cellspacing=0 width="100%">
		<tr>
		<td>
		Aktualizace:
		</td>
		<td>
		14. 03. <a href="redir/season.php?season=2004&amp;t=0">Sezóna 2004</a><br/>
		14. 04. <a href="redir/season.php?season=2004&amp;t=2"><b>Závod v sobotu 18:00 v Měcholupech</b></a><br/>
		</td>
		</tr>
	</table>
	</tr>
</table>
<td width=468 align=right>
<?
	if (1) {
?>
<!-- BBSTART: ad2.billboard.cz kod 1.1 - SERVER: Kart-FM(4379), SEKCE: Main(1), POZICE: (1), TYPBANNERU: Full Banner(1), OKRAJ: ne, POPIS: ne. -->
<script language='JavaScript' type='text/javascript'>
<!--
var bbs=screen,bbn=navigator,bbh;bbh='&ubl='+bbn.browserLanguage+'&ucc='+bbn.cpuClass+'&ucd='+bbs.colorDepth+'&uce='+bbn.cookieEnabled+'&udx='+bbs.deviceXDPI+'&udy='+bbs.deviceYDPI+'&usl='+bbn.systemLanguage+'&uje='+bbn.javaEnabled()+'&uah='+bbs.availHeight+'&uaw='+bbs.availWidth+'&ubd='+bbs.bufferDepth+'&uhe='+bbs.height+'&ulx='+bbs.logicalXDPI+'&uly='+bbs.logicalYDPI+'&use='+bbs.fontSmoothingEnabled+'&uto='+(new Date()).getTimezoneOffset()+'&uui='+bbs.updateInterval+'&uul='+bbn.userLanguage+'&uwi='+bbs.width;
var bb9_bgcolor='C0C0C0';
var bb9_text='000000';
var bb9_link='0000FF';
if(document.bgColor) { bb9_bgcolor=document.bgColor.substr(1); }
if(document.fgColor) { bb9_text=document.fgColor.substr(1); }
if(document.linkColor) { bb9_link=document.linkColor.substr(1); }
document.write("<IFRAME WIDTH=468 HEIGHT=60 MARGINWIDTH=0 MARGINHEIGHT=0 FRAMEBORDER=0 HSPACE=0 VSPACE=0 SCROLLING=no BORDERCOLOR='#000000' SRC='http://ad2.billboard.cz/please/showit/4379/1/1/1/?typkodu=html&topmargin=0&leftmargin=0"+bbh+"&href="+escape(location.href)+"&popis=ne&okraj=ne&bgcolor="+bb9_bgcolor+"&text="+bb9_text+"&link="+bb9_link+"&bust="+Math.random()+"&target=_blank'>");
document.write("<scr"+"ipt language='JavaScript' type='text/javascript'>");
document.write("document.write(\"<scr\"+\"ipt language='JavaScript' type='text/javascript' src='http://ad2.billboard.cz/please/showit/4379/1/1/1/?typkodu=js\"+bbh+\"&href=\"+escape(location.href)+\"&popis=ne&okraj=ne&bust=\"+Math.floor(10000000*Math.random())+\"&target=_blank'><\/scr\"+\"ipt>\");");
document.write("<\/scr"+"ipt>");
document.write("<\/IFRAME>");
/**///-->
</script>
<noscript>
<table border="0" cellpadding="0" cellspacing="0"><tr><td>
<a href="http://ad2.billboard.cz/please/redir.bb/4379/1/1/1/" target="_blank">
<img src="http://ad2.billboard.cz/please/showit/4379/1/1/1/?typkodu=img" border="0" width="468" height="60" alt='Kliknete prosim!'></a>
</td></tr></table>
</noscript>
<!-- BBEND: ad2.billboard.cz kod 1.1 -->
<?
	}
	else {
?>
<p align=center><font color=red size=60>PF 2005!</font></p>
<?
	}
?>
</td></tr></table>

<hr>

<table width="100%"><tr>
	<td width=140 valign=top>
		<table class=lmenu width=140 border=0 cellspacing=5 cellpadding=0>
		<tr><td><a class=lmenu href="static.php?page=intro">Hlavní stránka</a>
		<tr><td><a class=lmenu href="news.php">Novinky</a>
		<tr><td><a class=lmenu href="redir/season.php?<?echo "season=$lseason";?>">Sezóna <?echo $lseason;?></a>
		<tr><td><a class=lmenu href="seasel.php">Archív sezón</a>
		<tr><td><a class=lmenu href="redir/forum.php">Diskusní fórum</a>
		<tr><td><a class=lmenu href="static.php?page=links">Odkazy</a>
		<tr><td><a class=lmenu href="static.php?page=about">O serveru</a>
		</table>
		<br/><hr>
		<div class=lother>
<?
		show_poll("b", 5);
?>
		</div>
	</td>
	<td width=6></td>
	<td valign=top>

<?
}

function std_html_lowfin()
{
	global $REQUEST_URI;
	global $SERVER_NAME;
?>
<table align=right><tr>
<td>
<!-- NAVRCHOLU.cz -->
<script src="http://c1.navrcholu.cz/code?site=39831;t=lbb24" type="text/javascript"></script><noscript><div><a href="http://navrcholu.cz/"><img src="http://c1.navrcholu.cz/hit?site=39831;t=lbb24;ref=;jss=0" width="24" height="24" alt="NAVRCHOLU.cz" style="border:none" /></a></div></noscript>
<!-- NAVRCHOLU.cz - konec -->
<td>
<a href="http://validator.w3.org/check?uri=http%3A%2F%2F<?echo urlencode($SERVER_NAME.$REQUEST_URI);?>;doctype=Inline"><img border="0" src="http://www.w3.org/Icons/valid-html401" alt="Valid HTML 4.01!" height="31" width="88"></a>
</tr></table>
<?
}

function std_html_finish()
{
?>

	</td></tr>
</table>
<?
	std_html_lowfin();
?>
</body>
</html>
<?
}

?>
<? /* vi: set cindent sw=8: */ ?>
