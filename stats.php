<?
require_once "race_basic.php";

if (!season_input_validate()) {
	std_html_bad_input();
	exit();
}

season_html_start("Statistiky $season");
readfile_htmlstrip("$season/stats.html");
season_html_finish();

?>
