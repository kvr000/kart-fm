<?
require_once "main_basic.php";

$av_pages = array(
		"intro" => array( "intro.html", "Intro" ),
		"links" => array( "links.html", "Odkazy" ),
		"about" => array( "about.html", "O serveru" ),
	    );

if (!is_null($av_pages[$page])) {
	std_html_start($av_pages[$page][1]);
	readfile_htmlstrip($av_pages[$page][0]);
	std_html_finish();
}
else {
	std_html_not_found();
	exit();
}

function static_dummy_html()
{
?>
<html>
<!-- let confuse WZ-REKLAMA-adder, it's already in main_basic.php, which is always included -->
<!--WZ-REKLAMA-1.0-->
</html>
<?
}
?>
