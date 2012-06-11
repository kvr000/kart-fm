<?
require_once "main_basic.php";

global $this_name;
$this_name = "news.php?";
global $this_name_s;
$this_name_s = "news.php";
global $this_name_si;
$this_name_si = "";
global $this_name_p;
$this_name_p = "news.php?";

std_html_start("Novinky");

if (ereg("^[0-9]+\$", $npage)) {
	readfile_htmlstrip("news/$npage.html");
}
else {
	readfile_htmlstrip("news.html");
}

std_html_finish();


function news_dummy_html()
{
?>
<html>
<!-- let confuse WZ-REKLAMA-adder, it's already in main_basic.php, which is always included -->
<!--WZ-REKLAMA-1.0-->
</html>
<?
}
?>
