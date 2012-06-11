<?
require_once "main_basic.php";

function season_html_start($page_title)
{
	global $season;

	std_html_start($page_title);

	echo "<a href=\"season.php?spage=races&amp;season=$season\">Závody</a> <a href=\"season.php?spage=stats&amp;season=$season\">Statistiky</a> <a href=\"season.php?spage=tracks&amp;season=$season\">Tratě</a> <a href=\"season.php?spage=regs&amp;season=$season\">Pravidla</a> <a href=\"seasel.php?season=$season\">Archív sezón</a>\n";
	echo "<hr>\n";
}

function season_html_finish()
{
	std_html_finish();
}

function race_html_start($page_title)
{
	global $season;
	global $race;

	std_html_start($page_title);

	$rft = (($ft = @filemtime("$season/$race/rforum.txt")) === false)?"":" <a href=\"rforum.php?season=$season&amp;race=$race\">Diskuse (".std_format_time($ft).")</a>";

	echo "<a href=\"rstatic.php?rpage=main&amp;season=$season&amp;race=$race\">Komentář</a> <a href=\"rtimes.php?tfmt=ver&amp;season=$season&amp;race=$race\">Výsledky závodu</a> <a href=\"rstatic.php?rpage=points&amp;season=$season&amp;race=$race\">Bodování</a> <a href=\"rstatic.php?rpage=graphs&amp;season=$season&amp;race=$race\">Grafy</a> <a href=\"rstatic.php?rpage=comments&amp;season=$season&amp;race=$race\">Komentáře jezdců</a> <a href=\"rmm.php?mfmt=pre&amp;season=$season&amp;race=$race\">Fotogalerie apod.</a>$rft<br>\n";
	echo "<hr>\n";
}

function race_html_finish()
{
	std_html_finish();
}

function season_input_validate()
{
	global $season;

	if (!ereg("^[0-9]+$", $season) || $season < 2001 || $season > 2004)
		return false;
	return true;
}

function race_input_validate()
{
	global $race;

	if (!season_input_validate())
		return false;
	if (!ereg("^[0-9n][0-9]+$", $race))
		return false;
	return true;
}

function race_dummy_html()
{
?>
<html>
<!-- let confuse WZ-REKLAMA-adder, it's already in main_basic.php, which is always included -->
<!--WZ-REKLAMA-1.0-->
</html>
<?
}
?>
