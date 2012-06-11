<?
/*
Copyright (C) by Krysa von Ratteburg (http://kvr.matfyz.cz)

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 2 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.

See the GNU General Public License for more details. (http://www.gnu.org)
*/

require_once "main_basic.php";

global $this_name;		# this script name
$this_name = "forum.php?";
global $this_name_s;		# this script name (for forms)
$this_name_s = "forum.php";
global $this_name_si;		# forms include
$this_name_si = "";
global $this_name_p;		# header's address
$this_name_p = "forum.php?";
global $this_cookie_path;
$this_cookie_path = "/"; #"$SCRIPT_NAME";
global $forum_file;
$forum_file = "forum_data/%s/forum.txt";
global $forum_tdir;
$forum_tdir = "forum_data/";
global $forum_lastdir;
$forum_lastdir = "forum_data/last/";
global $pagesize;

global $html_started;
$html_started = 0;
global $html_finished;
$html_finished = 0;

function revalid_html($s)
{
	global $forv_adv;

	$s = ereg_replace("\t", " ", $s);
	if ($forv_adv) {
		$s = ereg_replace("&", "&amp;", $s);
		$s = ereg_replace("&amp;(#?[a-zA-Z0-9]*;)", "&\\1", $s);
		$s = ereg_replace("[ ]*\r?\n$", "", $s);
		$s = ereg_replace("(<[bB][rR]>)?[ ]*\r?\n", "<br>", $s);
	}
	else {
		$s = ereg_replace("&", "&amp;", $s);
		$s = ereg_replace("<", "&lt;", $s);
		$s = ereg_replace(">", "&gt;", $s);
		$s = ereg_replace("[ ]*\r?\n$", "", $s);
		$s = ereg_replace("(&lt;[bB][rR]&gt;)?[ ]*\r?\n", "<br>", $s);
	}
	return $s;
}

function filerec_escape_to($s)
{
	$s = ereg_replace("[ \t\r\n]", " ", $s);
	return $s;
}

function filerec_escape_from($s)
{
	return $s;
}

function format_time($t)
{
	return strftime("%Y-%m-%d %H:%M:%S %Z", $t);
}

function decode_id($id, &$name, &$email, &$wpage)
{
	if (ereg("^([^;]*);([^;]*);([^;]*)$", $id, $regs)) {
		$name = $regs[1]; $email = $regs[2]; $wpage = $regs[3];
	}
	else {
		$name = $id; $email = ""; $wpage = "";
	}
}

function encode_id($name, $email, $wpage)
{
	return ereg_replace("[\t\n]", " ", "$name;$email;$wpage");
}

function show_one_rec(&$out, $row)
{
	global $topic;
	global $this_name;

	if (!ereg("^(.*)\t(.*)\t(.*)\t(.*)\t(.*)\t(.*)\n\$", $row, $regs)) {
		printf("<b>invalid record-format</b>: %s<br>", htmlspecialchars($row));
		return -1;
	}
	$t = filerec_escape_from($regs[1]); # text
	$s = filerec_escape_from($regs[2]); # subject
	$i = filerec_escape_from($regs[3]); # name
	$d = $regs[4]; # time
	$o = $regs[5]; # original
	$n = $regs[6]; # id

	decode_id($i, $name, $email, $wpage);

	$out = "";
	if (strlen($email) > 0)
		$out .= sprintf("<b>Jméno:</b> <a href=\"mailto:%s\">%s</a>", ereg_replace("%40", "@", urlencode($email)), htmlspecialchars($name));
	else
		$out .= sprintf("<b>Jméno:</b> %s", htmlspecialchars($name));
	if (strlen($wpage) > 0)
		$out .= sprintf(" (<a href=\"http://%s\">%s</a>)", $wpage, htmlspecialchars($wpage));
	$out .= "<br>\n";
	$out .= sprintf("<b>ID:</b> %d, <b>Čas:</b> %s<br>\n", htmlspecialchars($n), htmlspecialchars(format_time($d)));
	$out .= sprintf("<b>Předmět:</b> %s\n", $s);
	$out .= sprintf("<a href=\"%saction=reply&amp;topic=%d&amp;id=%d&amp;subj=%s\"><b>(Odpovědět)</b></a>", $this_name, $topic, $n, urlencode($s));
	if ($o != 0)
		$out .= sprintf(" <a href=\"%saction=go&amp;topic=%d&amp;id=%d#id%d\"><b>(Původní %d)</b></a>", $this_name, $topic, $o, $o, $o);
	$out .= sprintf("<p>\n%s<br><hr>\n", $t);
	return $n;
}

function show_forum_spec($first, $next, $id)
{
	global $topic;
	global $forum_file;

	$prev = $first-1;

	if ($first == $next)
		return 0;
	$search = "\t$prev\n";
	if (!($fh = fopen(sprintf($forum_file, $topic), "r"))) {
		printf("<b>Failed to open forum file: %s</b>\n", "how in hell can I get errno in php?!?");
		return -1;
	}
	clearstatcache();
	$regs = fstat($fh);
	$size = $regs["size"];
	$min = 0;
	$max = $size;
	if (($pos = $size-8192) < 0) # optimize read to go for last 8k
		$pos = 0;
	fseek($fh, $pos, SEEK_SET);
	#printf("<br>Going from pos %d, first == %d<br>\n", $pos, $first);
	for (;;) {
		$d = fread($fh, 4096);
		if (($spos = strpos($d, $search)) !== false) {
			$pos += $spos+strlen($search);
			break;
		}
		if (!ereg("^.*\t([0-9]*)\n.*$", $d, $regs)) {
			fclose($fh);
			#printf("<b>Forum file corrupted on position %d</b>\n", $d);
			return -1;
		}
		if ($regs[1] > $prev) {
			$max = $pos;
		}
		elseif ($regs[1] < $prev) {
			$min = $pos;
		}
		else {
			printf("strpos didn't find it! (%d), search is '%s', line is: '%s'\n", $prev, $search, $d);
			exit(0);
		}
		$newpos = ($min+$max)/2;
		#printf("<p>pos %d: Found %s, (prev %s), newpos set to %d</p>\n", $pos, $regs[1], $prev, $newpos);
		if ($newpos == $pos) {
			fclose($fh);
			printf("<b>Failed to find &quot;prev&quot; record in forum file</b>\n");
			return -1;
		}
		$pos = $newpos;
		if (fseek($fh, $pos, SEEK_SET) < 0) {
			fclose($fh);
			printf("<b>Failed to seek to position %d</b>\n", $pos);
			return -1;
		}
	}
	if (fseek($fh, $pos, SEEK_SET) < 0) {
		fclose($fh);
		printf("<b>Failed to seek to position %d</b>\n", $pos);
		return -1;
	}
	while (!feof($fh)) {
		if (strlen($fr = fgets($fh, 4096)) == 0)
			break;
		if (($r = show_one_rec($tmp, $fr)) < 0)
			break;
		if ($r >= $next)
			break;
		$out = $tmp.$out;
		if ($r == $id) {
			$out = "<b><a name=\"id$id\"></a><a name=\"id\">&gt;&gt;&gt;</a></b><br>\n".$out;
		}
	}
	print($out);
	fclose($fh);
	return 0;
}

function get_next()
{
	global $topic;
	global $forum_file;

	$row = "";
	if (!($fh = fopen(sprintf($forum_file, $topic), "r"))) {
		printf("<b>Failed to open forum file: %s</b>\n", "how in hell to get errno in php?!?");
		return -1;
	}
	fseek($fh, -13, SEEK_END);
	$row = fread($fh, 13);
	if (!ereg("\t([0-9]+)\n\$", $row, $regs)) {
		printf("<b>Invalid format on last row</b>\n");
		return -1;
	}
	fclose($fh);
	$next = $regs[1]+1;
	return $next;
}

function html_start()
{
	global $html_started;
	if ($html_started)
		return;
	$html_started = 1;
	std_html_start("Fórum");
}

function html_finish()
{
	global $html_finished;
	if ($html_finished)
		return;
	$html_finished = 1;
	std_html_finish();
}

function html_full_error($err)
{
	html_start();
	echo htmlspecialchars($err);
	html_finish();
}

function show_basic($is_view)
{
	global $topic;
	global $this_name;
	global $this_name_s;
	global $this_name_si;

	html_start();
	echo "<a href=\"".$this_name."action=reply&amp;topic=$topic\">Přidat zprávu</a> <a href=\"".$this_name."topic=$topic\">Obnovit/Nahoru</a> <a href=\"".$this_name."action=last&amp;topic=$topic\">Poslední přihlášení</a> <a href=\"".$this_name."action=set&amp;topic=$topic\">Nastavení</a> <a href=\"".$this_name."action=tsel&amp;topic=$topic\">Téma</a><hr>\n";

	if ($is_view) {
		echo "<form action=\"".$this_name_s."#id\">\n",
		$this_name_si,
		"<input type=hidden name=\"action\" value=\"go\">\n",
		"<input type=hidden name=\"topic\" value=\"$topic\">\n",
		"Jdi na <input type=text name=\"id\" value=\"\">. <input type=submit name=\"ac_go\" value=\"příspěvek\">\n",
		"</form>\n";
		echo "<br>\n<hr>\n";
	}
}

function show_prevnext($first, $next)
{
	global $topic;
	global $this_name;
	global $pagesize;

	if (($first -= ceil($pagesize/2)) <= 0)
		$first = 1;
	$next += floor($pagesize/2);
	printf("<a href=\"%saction=go&amp;topic=%d&amp;id=%d\">Novějších $pagesize</a>", $this_name, $topic, $next);
	printf(" <a href=\"%saction=go&amp;topic=%d&amp;id=%d\">Starších $pagesize</a>\n", $this_name, $topic, $first);
}

function show_id($id)
{
	global $pagesize;

	$id = max(ceil($id), 1);
	if (($lastp = get_next()) < 0)
		return -1;

	show_basic(1);
	if (($next = $id+ceil($pagesize/2)) > $lastp)
		$next = $lastp;
	if (($first = $next-$pagesize) <= 0)
		$first = 1;
	if (show_forum_spec($first, $next, $id) < 0)
		return -1;
	return show_prevnext($first, $next);
}

function show_top()
{
	global $pagesize;

	if (($next = get_next()) < 0)
		return -1;

	header("Expires: ".gmdate("D, d M, Z H:i:s")." GMT");
	header("Pragma: no-cache");
	header("Cache-Control: no-cache, must revalidate");
	show_basic(1);
	if (($first = $next-$pagesize) <= 0)
		$first = 1;
	if (show_forum_spec($first, $next, 0) < 0)
		return -1;
	return show_prevnext($first, $next);
}

function show_reply($subj, $orig)
{
	global $topic;
	global $this_name_s;
	global $this_name_si;
	global $forv_ids;

	if ($orig != 0 && !ereg("^ *Re:", $subj))
		$subj = "Re: $subj";
	html_start();

	show_basic(0);

	decode_id($forv_ids, $name, $email, $wpage);
	echo "<form action=\"".$this_name_s."\" method=post>\n",
	$this_name_si,
	"<input type=hidden name=\"action\" value=\"add\">\n",
	"<input type=hidden name=\"topic\" value=\"$topic\">\n",
	"<input type=hidden name=\"orig\" value=\"$orig\">\n",
	"<table>\n",
	"<tr><td><b>Jméno:</b></td><td><input type=text name=\"name\" value=\"".htmlspecialchars($name)."\"></td></tr>\n",
	"<tr><td><b>E-mail:</b></td><td><input type=text name=\"email\" value=\"".htmlspecialchars($email)."\"></td></tr>\n",
	"<tr><td><b>Homepage:</b></td><td><input type=text name=\"wpage\" value=\"".htmlspecialchars($wpage)."\"></td></tr>\n",
	"<tr><td><b>Předmět:</b></td><td><input type=text name=\"subj\" value=\"".$subj."\"></td></tr>\n",
	"<tr><td><b>Text:</b></td><td><textarea name=\"cont\" rows=10 cols=78></textarea></td></tr>\n",
	"<tr><td></td><td><input type=submit name=\"ac_send\" value=\"Odeslat\">\n",
	"</table>\n",
	"</form>\n";
	html_finish();
	return 0;
}

function do_send($name, $email, $wpage, $subj, $cont, $orig)
{
	global $topic;
	global $this_name_p;
	global $this_cookie_path;
	global $forum_file;
	global $forv_pgsize;

	if (!preg_match("/^\\s*$/", $cont) && !preg_match("/^\\s*$/", $name)) {

		$wpage = ereg_replace("^http:\\/\\/", "", $wpage);
		$name = ereg_replace(";", ",", $name);
		$email = ereg_replace(";", "", $email);
		$wpage = ereg_replace(";", "", $wpage);
		$ids = encode_id($name, $email, $wpage);

		if (!($fw = fopen(sprintf($forum_file, $topic), "a"))) {
			html_full_error(sprintf("<b>Failed to open forum file for writing</b>: %s<br>", "how in hell to get errno in php?!?"));
			return -1;
		}
		if (!flock($fw, LOCK_EX)) {
			html_full_error(sprintf("<b>Failed to lock forum file: %s</b><br>\n", "how in hell to get errno in php?!?"));
			fclose($fw);
			return -1;
		}
		if (($next = get_next()) < 0) {
			html_full_error(sprintf("<b>get_next returned -1: %s</b><br>\n", "how in hell to get errno in php?!?"));
			fclose($fw);
			return -1;
		}
		$row = sprintf("%s\t%s\t%s\t%d\t%d\t%d\n", filerec_escape_to(revalid_html($cont)), filerec_escape_to(revalid_html($subj)), filerec_escape_to($ids), time(), $orig, $next);
		if (strlen($row) > 4070) {
			html_full_error("<b>Přílíš dlouhý příspěvek, max. možná délka je 4000 znaků (včetně jména)</b>\n");
			fclose($fw);
			return -1;
		}
		fputs($fw, $row);
		flock($fw, LOCK_UN);
		fclose($fw);
	}
# re-update cookie vars
	setcookie("forv_ids", $ids, time()+365*24*3600, "$this_cookie_path");
	setcookie("forv_pgsize", $forv_pgsize, time()+365*24*3600, "$this_cookie_path");
	header("Location: ".$this_name_p."topic=$topic");
	print("<a href=\"".$this_name_p."topic=$topic\"click</a> to refresh...");
#	header("Content-type: text/plain; charset=UTF-8");
#	echo "Redirecting to: ".$this_name_p."action=top";
	return 0;
}

function show_last()
{
	global $forum_lastdir;
	$ct = time();

	header("Expires: ".gmdate("D, d M, Z H:i:s")." GMT");
	header("Pragma: no-cache");
	header("Cache-Control: no-cache, must revalidate");
	show_basic(0);

	echo "\n<table>\n<tr><th>Jméno</th><th>Čas přihlášení</th><th>Téma</th></tr>\n";
	if (!($dh = opendir($forum_lastdir)))
		return;

	while (($fn = readdir($dh))) {
		if ($fn == "." || $fn == "..")
			continue;
		$t = filemtime($forum_lastdir.$fn);
		if ($t < $ct-3600) {
			unlink($forum_lastdir.$fn);
		}
		else {
			printf("<tr><td>%s</td><td>%s</td><td>%s</td></tr>\n", htmlspecialchars($fn), format_time($t), htmlspecialchars(join("<br>\n", file($forum_lastdir.$fn))));
		}
	}
	echo "</table>\n";
}

function do_settings()
{
	global $topic;
	global $set;
	global $this_name_p;
	global $this_name_s;
	global $this_name_si;
	global $forv_ids;
	global $forv_adv;
	global $for_psize;
	global $advhtml;
	global $pagesize;
	global $this_cookie_path;

	if ($set == "set") {
		global $name, $email, $wpage;

		$wpage = ereg_replace("^http:\\/\\/", "", $wpage);
		$ids = encode_id($name, $email, $wpage);

		setcookie("forv_ids", $ids, time()+365*24*3600, $this_cookie_path);
		setcookie("forv_pgsize", $for_psize, time()+365*24*3600, $this_cookie_path);
		setcookie("forv_adv", $advhtml, time()+365*24*3600, $this_cookie_path);
		header("Location: ".$this_name_p."topic=$topic");
	}
	else {
		decode_id($forv_ids, $name, $email, $wpage);

		header("Expires: ".gmdate("D, d M, Z H:i:s")." GMT");
		header("Pragma: no-cache");
		header("Cache-Control: no-cache, must revalidate");
		show_basic(0);

		echo "<form action=\"".$this_name_s."\" method=post>\n",
		$this_name_si,
		"<input type=hidden name=\"action\" value=\"set\">\n",
		"<input type=hidden name=\"topic\" value=\"$topic\">\n",
		"<input type=hidden name=\"set\" value=\"set\">\n",
		"<table>\n",
		"<tr><td><b>Jméno:</b></td><td><input type=text name=\"name\" value=\"".htmlspecialchars($name)."\"></td></tr>\n",
		"<tr><td><b>E-mail:</b></td><td><input type=text name=\"email\" value=\"".htmlspecialchars($email)."\"></td></tr>\n",
		"<tr><td><b>Homepage:</b></td><td><input type=text name=\"wpage\" value=\"".htmlspecialchars($wpage)."\"></td></tr>\n",
		"<tr><td><b>Počet zobrazených příspěvků:</b></td><td><input type=text name=\"for_psize\" value=\"".htmlspecialchars($pagesize)."\"></td></tr>\n",
		"<tr><td><b>Vkládání html:</b></td><td><input type=checkbox name=advhtml ".($forv_adv?"checked ":"")."></td></tr>\n",
		"<tr><td></td><td><input type=submit name=\"ac_set\" value=\"Nastavit\">\n",
		"</table>\n",
		"</form>\n";
	}
	html_finish();
	return 0;
}

function do_tselect()
{
	global $forum_tdir;
	global $this_name;

	show_basic(0);

	for ($t = 0; ($c = @file("$forum_tdir$t/_info")) !== false; $t++) {
		echo "<a href=\"".$this_name."topic=$t\">".htmlspecialchars($c[0])."</a><br>\n";
	}
}

if (!ereg("^[0-9]+$", $topic)) {
	std_html_bad_input();
	exit();
}

if (($pagesize = $forv_pgsize) < 1)
	$pagesize = 10;

decode_id($forv_ids, $t_name, $t_email, $t_wpage);
if (!preg_match("/^[\\s.]*$/", $t_name) && !preg_match("/.*[\\/].*/", $t_name)) {
	if (($f = fopen("$forum_lastdir/$t_name", "w"))) {
		fputs($f, join("<br>\n", file("$forum_tdir$topic/_info")));
		fclose($f);
	}
#	touch("$forum_lastdir/$t_name");
}
if ($action == "reply") {
	show_reply(stripslashes($subj), $id);
}
elseif ($action == "add") {
	do_send($name, $email, $wpage, stripslashes($subj), stripslashes($cont), $orig);
#	exit("location sent");
	$html_finished = 1;
}
elseif ($action == "go") {
	show_id($id);
}
elseif ($action == "last") {
	show_last();
}
elseif ($action == "set") {
	do_settings();
}
elseif ($action == "tsel") {
	do_tselect();
}
else {
	show_top();
}
html_finish();
?>
<? /* vi: set cindent: */ ?>
