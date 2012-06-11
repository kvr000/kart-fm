<?
require_once "race_basic.php";

if ($mfmt == "") {
	header("Location: rmm.php?".$HTTP_SERVER_VARS['QUERY_STRING']."&mfmt=pre");
	exit();
}

if (!race_input_validate()) {
	std_html_bad_input();
	exit();
}

race_html_start("Fotogalerie");
if (!($fh = @fopen("$season/$race/_rmm.html", "r"))) {
	echo "Hmm, nic...";
}
else {
	while (($s = fgets($fh, 8192)) && $s != "") {
		if (preg_match("/^::\\s+(.*?)\\s*$/", $s, $r)) {
			foreach (preg_split("/\\s+/", $r[1]) as $v) {
				if (!preg_match("/^(.*)(\\..*)$/", $v, $r)) {
					echo "File without extension, can't find preview: $v\n";
				}
				if ($mfmt == "pre") {
					echo "<a href=\"$season/$race/$v\"><img src=\"$season/$race/".$r[1]."_pre".$r[2]."\" alt=photo></a>\n";
				}
				elseif ($mfmt == "full") {
					echo "<img src=\"$season/$race/$v\" alt=photo><br>\n";
				}
			}
		}
		else {
			echo $s;
		}
	}
	echo "<hr/>Zobrazit <a href=\"rmm.php?mfmt=pre&amp;season=$season&amp;race=$race\">Preview</a> <a href=\"rmm.php?mfmt=full&amp;season=$season&amp;race=$race\">Velké</a>\n";
}

race_html_finish();


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
