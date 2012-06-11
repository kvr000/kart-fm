<?
require_once "race_basic.php";

if (!season_input_validate()) {
	std_html_bad_input();
	exit();
}

$av_pages = array(
		"races" => array( "races.html", "Závody" ),
		"stats" => array( "stats.html", "Statistiky" ),
		"tracks" => array( "tracks.html", "Tratě" ),
		"regs" => array( "regs.html", "Pravidla" ),
	    );

if (!is_null($av_pages[$spage])) {
	season_html_start($av_pages[$spage][1]);
	if (!@readfile_htmlstrip("$season/$race/".$av_pages[$spage][0])) {
		echo "Hmm, nic...";
	}
	season_html_finish();
}
else {
	std_html_bad_input();
	exit();
}

?>
