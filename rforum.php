<?
require_once "race_basic.php";


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
	return "$name;$email;$wpage";
}

function revalid_html($s)
{
	$s = ereg_replace("\t", " ", $s);
	$s = ereg_replace("&", "&amp;", $s);
	$s = ereg_replace("<", "&lt;", $s);
	$s = ereg_replace(">", "&gt;", $s);
	$s = ereg_replace("[ ]*\r?\n$", "", $s);
	$s = ereg_replace("(&lt;[bB][rR]&gt;)?[ ]*\r?\n", "<br>", $s);
	return $s;
}


if (!race_input_validate()) {
	std_html_bad_input();
	exit();
}

if (!file_exists("$season/$race/rforum.txt")) {
	std_html_bad_input();
	exit();
}

if ($action == "add") {
	if (false) {
	if (preg_match("/^\\s*\$/", $name)) {
		header("Location: rforum.php?season=$season&race=$race&action=err&err=0");
	}
	if (!preg_match("/^\\s*\$/", $cont)) {
		if (!($fh = fopen("$season/$race/rforum.txt", "a"))) {
			printf("<b>Failed to open forum file: %s</b>\n", "how in hell can I get errno in php?!?");
			exit();
		}
		$ids = encode_id($name, $email, $wpage);
		setcookie("forv_ids", $ids, time()+365*24*3600, "/");
		fputs($fh, revalid_html($cont)."\t".$ids."\t".time()."\n");
		fclose($fh);
	}
	}
	else {
		printf("<b>Sorry, temporarily disabled, use main forum</b>\n");
	}
	header("Location: rforum.php?season=$season&race=$race&action=top");
}
else {
	header("Expires: ".gmdate("D, d M, Z H:i:s")." GMT");
	header("Pragma: no-cache");
	header("Cache-Control: no-cache, must revalidate");

	decode_id($forv_ids, $name, $email, $wpage);

	race_html_start("Fórum k závodu");
	if ($action == "err") {
		if ($err == 0) {
			echo "<b>Nebylo vyplněno jméno!</b>";
		}
		else {
			echo "<b>Neznámá chyba ;)</b>";
		}
	}

	echo "<form action=\"rforum.php\" method=post>\n",
	"<input type=hidden name=\"action\" value=\"add\">\n",
	"<input type=hidden name=\"season\" value=\"$season\">\n",
	"<input type=hidden name=\"race\" value=\"$race\">\n",
	"<table>\n",
	"<tr><td><b>Jméno:</b></td><td><input type=text name=\"name\" value=\"".htmlspecialchars($name)."\"></td></tr>\n",
	"<tr><td><b>E-mail:</b></td><td><input type=text name=\"email\" value=\"".htmlspecialchars($email)."\"></td></tr>\n",
	"<tr><td><b>Homepage:</b></td><td><input type=text name=\"wpage\" value=\"".htmlspecialchars($wpage)."\"></td></tr>\n",
	"<tr><td><b>Text:</b></td><td><textarea name=\"cont\" rows=10 cols=78></textarea></td></tr>\n",
	"<tr><td></td><td><input type=submit name=\"ac_send\" value=\"Odeslat\">\n",
	"</table>\n",
	"</form>\n";

	$tlist = file("$season/$race/rforum.txt");
	while (!is_null($l = array_pop($tlist))) {
		if (!ereg("^(.*)\t(.*)\t(.*)\n\$", $l, $regs)) {
			printf("<b>invalid record-format</b>: %s<br>", htmlspecialchars($row));
		}
		else {
			$t = $regs[1];
			$i = $regs[2];
			$d = $regs[3];
			decode_id($i, $name, $email, $wpage);
			echo "<hr>\n<font size=\"+0\">";
			if (strlen($email) > 0)
				printf("<b><a href=\"mailto:%s\">%s</a></b>", $email, $name);
			else
				printf("<b>%s</b>", $name);
			if (strlen($wpage) > 0)
				printf(" (<a href=\"http://%s\">%s</a>)", $wpage, htmlspecialchars($wpage));
			printf(" - %s</font>\n", std_format_time($d));
			echo "<p>$t</p>\n";
		}
	}
	race_html_finish();
}

function rforum_dummy_html()
{
?>
<html>
<!-- let confuse WZ-REKLAMA-adder, it's already in main_basic.php, which is always included -->
<!--WZ-REKLAMA-1.0-->
</html>
<?
}
?>
