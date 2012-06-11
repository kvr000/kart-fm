<?
require_once "race_basic.php";

if (!race_input_validate()) {
	std_html_bad_input();
	exit();
}

$av_pages = array(
		"main" => array( "_rrace.html", "Komentář" ),
		"points" => array( "_rpoints.html", "Bodování" ),
		"comments" => array( "_rcomments.html", "Komentáře jezdců" ),
		"graphs" => array( "_rgraphs.html", "Grafy" ),
	    );

if (!is_null($av_pages[$rpage])) {
	if (!ereg("^n?[0-9]+\$", $race)) {
		std_html_bad_input();
		exit();
	}

	race_html_start($av_pages[$rpage][1]);
	if (!@readfile_htmlstrip("$season/$race/".$av_pages[$rpage][0])) {
		echo "Hmm, nic...";
	}
	race_html_finish();
}
else {
	std_html_bad_input();
	exit();
}

function rstatic_dummy_html()
{
?>
<html>
<!-- let confuse WZ-REKLAMA-adder, it's already in main_basic.php, which is always included -->
<!--WZ-REKLAMA-1.0-->
</html>
<?
}
?>
