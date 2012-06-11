<?
require "main_basic.php";
require "forum_if.php";
require "forum_dif_file.php";

function rewrite_html($s, $allowhtml)
{
	$s = ereg_replace("\t", " ", $s);
	if ($allowhtml) {
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

class ForumViewIf_KFM extends ForumViewIf
{
	var $this_name; # this script's name
	var $this_name_p; # this script's name for post methods
	var $this_name_pi; # post include data
	var $this_name_h; # this script's name for headers
	var $cookie_path; # path for cookies

	var $topic;

	var $rows;

	function format_time($t)
	{
		return strftime("%Y-%m-%d %H:%M:%S %Z", $t);
	}

	function setTopic($topic_)
	{
		$this->topic = $topic_;
	}

	function printError($err)
	{
		print("<b>Error occured: $err</b><br>\n");
	}

	function printHeader()
	{
		echo "<a href=\"".$this->this_name."action=reply&amp;topic=$this->topic\">Přidat zprávu</a> <a href=\"".$this->this_name."topic=$this->topic\">Obnovit/Nahoru</a> <a href=\"".$this->this_name."action=last&amp;topic=$this->topic\">Poslední přihlášení</a> <a href=\"".$this->this_name."action=set&amp;topic=$this->topic\">Nastavení</a> <a href=\"".$this->this_name."action=tsel&amp;topic=$this->topic\">Téma</a><hr>\n";
	}

	function printFind()
	{
		echo "<form action=\"".$this->this_name_p."#id\">\n",
		$this->this_name_pi,
		"<input type=hidden name=\"action\" value=\"go\">\n",
		"<input type=hidden name=\"topic\" value=\"$this->topic\">\n",
		"Jdi na <input type=text name=\"id\" value=\"\">. <input type=submit name=\"ac_go\" value=\"příspěvek\">\n",
		"</form>\n";
		echo "<br>\n<hr>\n";
	}

	function showError($err)
	{
		$this->startPage();
		$this->printError($err);
		$this->finishPage();
	}

	function startPage()
	{
		std_html_start("Fórum");
		$this->printHeader();
	}

	function finishPage()
	{
		std_html_finish();
	}

	function startRows($from, $to, $pgsize)
	{
		header("Expires: ".gmdate("D, d M, Z H:i:s")." GMT");
		header("Pragma: no-cache");
		header("Cache-Control: no-cache, must revalidate");
		$this->startPage();
		#$this->printHeader();
		$this->printFind();
		$rows = "";
	}

	function finishRows($from, $to, $pgsize)
	{
		print($this->rows);
		if (($from -= ceil($pgsize/2)) <= 0)
			$from = 1;
		$to += floor($pgsize/2);
		printf("<a href=\"%saction=go&amp;topic=%d&amp;id=%d\">Novějších $pgsize</a>", $this->this_name, $this->topic, $to);
		printf(" <a href=\"%saction=go&amp;topic=%d&amp;id=%d\">Starších $pgsize</a>\n", $this->this_name, $this->topic, $from);
		$this->finishPage();
	}

	function genRow($r, $istarget)
	{
		$out = "";
		if ($istarget)
			$out .= "<b><a name=\"id".$r["id"]."\"></a><a name=\"id\">&gt;&gt;&gt;</a></b><br>\n";
		if (strlen($r["email"]) > 0)
			$out .= sprintf("<b>Jméno:</b> <a href=\"mailto:%s\">%s</a>", ereg_replace("%40", "@", urlencode($r["email"])), htmlspecialchars($r["name"]));
		else
			$out .= sprintf("<b>Jméno:</b> %s", htmlspecialchars($r["name"]));
		if (strlen($r["wpage"]) > 0)
			$out .= sprintf(" (<a href=\"http://%s\">%s</a>)", $r["wpage"], htmlspecialchars($r["wpage"]));
		$out .= "<br>\n";
		$out .= sprintf("<b>ID:</b> %d, <b>Čas:</b> %s<br>\n", htmlspecialchars($r["id"]), htmlspecialchars($this->format_time($r["time"])));
		$out .= sprintf("<b>Předmět:</b> %s\n", $r["subj"]);
		$out .= sprintf("<a href=\"%saction=reply&amp;topic=%d&amp;id=%d\"><b>(Odpovědět)</b></a>", $this->this_name, $this->topic, $r["id"]);
		if ($r["oid"] != 0)
			$out .= sprintf(" <a href=\"%saction=go&amp;topic=%d&amp;id=%d#id\"><b>(Původní %d)</b></a>", $this->this_name, $this->topic, $r["oid"], $r["oid"]);
		$out .= sprintf("<p>\n%s<br><hr>\n", $r["body"]);

		$this->rows = $out.$this->rows;
	}

	function showReply($name, $email, $wpage, $id, $subj)
	{
		$check_code = (rand()%10).(rand()%10).(rand()%10).(rand()%10);
		$this->startPage();
		#$this->printHeader();
		echo "<form action=\"".$this->this_name_p."\" method=post>\n",
		$this->this_name_pi,
		"<input type=hidden name=\"action\" value=\"add\">\n",
		"<input type=hidden name=\"topic\" value=\"$this->topic\">\n",
		"<input type=hidden name=\"orig\" value=\"$id\">\n",
		"<input type=hidden name=\"check0\" value=\"$check_code\">\n",
		"<table>\n",
		"<tr><td><b>Jméno:</b></td><td><input type=text name=\"name\" value=\"".htmlspecialchars($name)."\"></td></tr>\n",
		"<tr><td><b>E-mail:</b></td><td><input type=text name=\"email\" value=\"".htmlspecialchars($email)."\"></td></tr>\n",
		"<tr><td><b>Homepage:</b></td><td><input type=text name=\"wpage\" value=\"".htmlspecialchars($wpage)."\"></td></tr>\n",
		"<tr><td><b>Předmět:</b></td><td><input type=text name=\"subj\" value=\"".$subj."\"></td></tr>\n",
		"<tr><td><b>Text:</b></td><td><textarea name=\"cont\" rows=10 cols=78></textarea></td></tr>\n",
		"<tr><td><b>Kód:</b></td><td><input type=text name=\"check\">\n<br>Anti-spam: Vepište $check_code</td></tr>\n",
		"<tr><td></td><td><input type=submit name=\"ac_send\" value=\"Odeslat\">\n",
		"</table>\n",
		"</form>\n";
		$this->finishPage();
	}

	function startLast()
	{
		header("Expires: ".gmdate("D, d M, Z H:i:s")." GMT");
		header("Pragma: no-cache");
		header("Cache-Control: no-cache, must revalidate");
		$this->startPage();
		echo "\n<table>\n<tr><th>Jméno</th><th>Čas přihlášení</th><th>Téma</th></tr>\n";
	}

	function genLast($name, $time, $where)
	{
		printf("<tr><td>%s</td><td>%s</td><td>%s</td></tr>\n", htmlspecialchars($name), $this->format_time($time), htmlspecialchars($where));
	}

	function finishLast()
	{
		echo "</table>\n";
		$this->finishPage();
	}

	function startSelect()
	{
		$this->startPage();
	}

	function genSelect($id, $name)
	{
		echo "<a href=\"".$this->this_name."topic=$id\">".htmlspecialchars($name)."</a><br>\n";
	}

	function finishSelect()
	{
		$this->finishPage();
	}

	function refresh()
	{
		header("Location: ".$this->this_name_h."topic=$this->topic");
	}

	function setScriptName($i)
	{
		$this->this_name = $i[""]."?";
		$this->this_name_p = $i[""];
		$this->this_name_h = $i[""]."?";

		$this->this_name_pi = "";
		unset($i[""]);

		foreach ($i as $k => $v) {
			$this->this_name_pi .= "<input type=hidden name=\"".htmlspecialchars($k)."\" value=\"".htmlspecialchars($v)."\">\n";
			$this->this_name .= urlencode($k)."=".urlencode($v)."&amp;";
			$this->this_name_h .= urlencode($k)."=".urlencode($v)."&";
		}
	}

}

$f = new Forum;
$d = new ForumDataIf_File;
$d->forum_dir = "forum_data/";
$v = new ForumViewIf_KFM;
$v->setScriptName(array("" => "forum.php"));
$v->cookie_path = "/";
$f->dif = &$d;
$f->vif = &$v;

if ("".intval($topic) != "$topic") {
	$v->showError("invalid topic specified");
}
else {
	$f->setTopic($topic);
	$d->setUser($forv_ids);
	if (($ps = intval($forv_pgsize)) < 1 || $ps > 100)
		$ps = 10;
	$d->pagelen = $ps;
	if ($action == "reply") {
		$f->showReply(intval($id));
	}
	elseif ($action == "add") {
		$wpage = ereg_replace("^http:\\/\\/", "", $wpage);
		if (strlen($check0) != 4 || $check != $check0) {
			$v->startPage();
			echo "Nekorektni kod";
			$v->finishPage();
		}
		else {
			$d->setUser(ereg_replace("[;]", ",", $name).";".ereg_replace("[;]", ",", $email).";".ereg_replace("[;]", ",", $wpage));
			setcookie("forv_ids", $d->getUser(), time()+365*24*3600, $v->cookie_path);
			setcookie("forv_pgsize", $d->getPagesize(), time()+365*24*3600, $v->cookie_path);
			setcookie("forv_adv", $forv_adv, time()+365*24*3600, $v->cookie_path);
			$subj = stripslashes($subj);
			$cont = stripslashes($cont);
			$subj = rewrite_html($subj, 0);
			$cont = rewrite_html($cont, 0);
			$f->addMsg(stripslashes($subj), stripslashes($cont), $orig);
		}
	}
	elseif ($action == "go") {
		$f->showId($id);
	}
	elseif ($action == "last") {
		$f->showLast();
	}
	elseif ($action == "tsel") {
		$f->showSelect();
	}
	elseif ($action == "set") {
		# should be moved to auth module but no was written yet ;)
		if ($set == "set") {
			$wpage = ereg_replace("^http:\\/\\/", "", $wpage);
			$d->setUser(ereg_replace("[;]", ",", $name).";".ereg_replace("[;]", ",", $email).";".ereg_replace("[;]", ",", $wpage));
			$d->pagelen = $for_psize;

			setcookie("forv_ids", $d->getUser(), time()+365*24*3600, $v->cookie_path);
			setcookie("forv_pgsize", $d->getPagesize(), time()+365*24*3600, $v->cookie_path);
			setcookie("forv_adv", $advhtml, time()+365*24*3600, $v->cookie_path);
			$v->refresh();
		}
		else {
			$d->getUinfo($name, $email, $wpage);

			header("Expires: ".gmdate("D, d M, Z H:i:s")." GMT");
			header("Pragma: no-cache");
			header("Cache-Control: no-cache, must revalidate");
			$v->startPage();

			echo "<form action=\"".$v->this_name_p."\" method=post>\n",
			$v->this_name_pi,
			"<input type=hidden name=\"action\" value=\"set\">\n",
			"<input type=hidden name=\"topic\" value=\"$v->topic\">\n",
			"<input type=hidden name=\"set\" value=\"set\">\n",
			"<table>\n",
			"<tr><td><b>Jméno:</b></td><td><input type=text name=\"name\" value=\"".htmlspecialchars($name)."\"></td></tr>\n",
			"<tr><td><b>E-mail:</b></td><td><input type=text name=\"email\" value=\"".htmlspecialchars($email)."\"></td></tr>\n",
			"<tr><td><b>Homepage:</b></td><td><input type=text name=\"wpage\" value=\"".htmlspecialchars($wpage)."\"></td></tr>\n",
			"<tr><td><b>Počet zobrazených příspěvků:</b></td><td><input type=text name=\"for_psize\" value=\"".htmlspecialchars($d->getPagesize())."\"></td></tr>\n",
			"<tr><td></td><td><input type=submit name=\"ac_set\" value=\"Nastavit\">\n",
			"</table>\n",
			"</form>\n";
		}
	}
	else {
		$f->showTop();
	}
}

?>
