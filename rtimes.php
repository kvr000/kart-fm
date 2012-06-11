<?
require "race_basic.php";

function fgets_nempty($fh, $max)
{
	do {
		$s = fgets($fh, $max);
	} while (preg_match("/^\\s*\$/", $s) && !feof($fh));
	return $s;
}

if (!race_input_validate()) {
	std_html_bad_input();
	exit();
}

if ($tfmt == "") {
	if (($q = $HTTP_SERVER_VARS['QUERY_STRING']) != "")
		$q .= "&";
	header("Location: rtimes.php?".$q."tfmt=ver");
	exit();
}

race_html_start("Výsledky závodu");

if (!($finfo = fopen("$season/$race/_info", "r"))) {
	die("fopen _info");
}

fscanf($finfo, "%d %d %d\n", $driver_cnt, $driver_pnt, $group_cnt);

$nr_base = 1;
$r_group = 0;

while (fscanf($finfo, "%d\n", $nr) && $nr != 0) {

	$names = array();
	$teams = array();
	$qual_cars = array();
	$qual_pos = array();
	$qual_times = array();
	$race_cars = array();
	$race_times = array();
	$flap_times = array();

	for ($n = 0; $n < $nr; $n++) {
		$sn = sprintf("%02d", $n+$nr_base);
		if (!($fh = fopen("$season/$race/$sn.racer", "r"))) {
			die("fopen $n-th racer");
		}
		$flags = "!\n";
		while (ereg("^!(.*)$", ($l = fgets_nempty($fh, 666)), $regs))
			$flags .= $regs[1];
		$names[$n] = chop($l);
		$teams[$n] = chop(fgets_nempty($fh, 666));
		$t = chop(fgets_nempty($fh, 666));
		preg_match("/^Q: ([^\t]*)\t([^\t]*)\t([^\t]*)\\s*\$/", $t, $regs);
		$qual_cars[$n] = $regs[1];
		$qual_pos[$n] = $regs[2];
		$qual_times[$n] = $regs[3];
		$t = chop(fgets_nempty($fh, 666));
		preg_match("/^R: ([^\t]*)\t([^\t]*)\\s*\$/", $t, $regs);
		$race_cars[$n] = $regs[1];
		$flap_times[$n] = $regs[2];
		$race_times[$n] = array();
		while (($t = chop(fgets_nempty($fh, 666))) != "") {
			$t = preg_replace("/\\s*/", "", $t);
			array_push($race_times[$n], $t);
		}
	}

	if ($group_cnt > 1)
		printf("<h3>Skupina %c</h3>\n", 65+$r_group);

	if ($tfmt == "hor" || $tfmt == "horT") {
		printf("<table border=1 width=\"100%%\">\n<tr><th width=\"%d%%\">Jezdec</th>", 100-$nr*floor(100/($nr+1)));
		for ($n = 0; $n < $nr; $n++) {
			printf("<th width=\"%d%%\">%s</th>", floor(100/($nr+1)), htmlspecialchars($names[$n]));
		}
		print("\n<tr><td>Tým</td>");
		for ($n = 0; $n < $nr; $n++) {
			printf("<td align=center>%s</td>", htmlspecialchars($teams[$n]));
		}
		print("\n<tr><td>Pozice v kvalifikaci</td>");
		for ($n = 0; $n < $nr; $n++) {
			printf("<td align=right>%s</td>", htmlspecialchars($qual_pos[$n]));
		}
		print("\n<tr><td>Stroj v kvalifikaci</td>");
		for ($n = 0; $n < $nr; $n++) {
			printf("<td align=right>%s</td>", htmlspecialchars($qual_cars[$n]));
		}
		print("\n<tr><td>Čas v kvalifikaci</td>");
		for ($n = 0; $n < $nr; $n++) {
			printf("<td align=right>%s</td>", htmlspecialchars($qual_times[$n]));
		}
		print("\n<tr><td>Stroj v závodě</td>");
		for ($n = 0; $n < $nr; $n++) {
			printf("<td align=right>%s</td>", htmlspecialchars($race_cars[$n]));
		}
		print("\n<tr><td>Nejrychlejší kolo</td>");
		for ($n = 0; $n < $nr; $n++)
			printf("<td align=right>%s</td>", htmlspecialchars($flap_times[$n]));
		print("\n");

		if ($tfmt == "horT") {
# print race times
			for ($lap = 0; ; $lap++) {
				$fcount = 0;

#	for ($n = 0; $n < $nr; $n++) {
#		if (array_count_values(($race_times[$n])) != 0)
#			$fcount++;
#	}
#	if ($fcount == 0)
#		break;

				$s = sprintf("<tr><td>%d. kolo", $lap+1);
				for ($n = 0; $n < $nr; $n++) {
					is_null($t = array_shift($race_times[$n]))?($t = ""):($fcount++);
#		$t = (array_count_values($race_times[$n]) == 0)?"":array_shift($race_times[$n]);
					$s .= sprintf("<td align=right>%s", $t);
				}
				$s .= "\n";
				if ($fcount == 0)
					break;
				print($s);
			}
		}
		print("</table><br/>\n");
	}
	elseif ($tfmt == "ver" || $tfmt == "verT") {
		print("<table border=1 width=\"100%\">\n<tr><th></th><th>Jezdec</th><th>Tým</th><th>Pozice v kvalifikaci</th><th>Stroj v kvalifikaci</th><th>Čas v kvalifikaci</th><th>Stroj v závodě</th><th>Nejrychlejší kolo</th>".(($tfmt == "verT")?"<th>Časy v závodě</th>":"")."</tr>\n");
		for ($n = 0; $n < $nr; $n++) {
			printf("<tr><td align=right>%d</td><td>%s</td><td align=right>%s</td><td align=right>%s</td><td align=right>%s</td><td align=right>%s</td><td align=right>%s</td><td align=right>%s</td>", $n+1, htmlspecialchars($names[$n]), htmlspecialchars($teams[$n]), htmlspecialchars($qual_pos[$n]), htmlspecialchars($qual_cars[$n]), htmlspecialchars($qual_times[$n]), htmlspecialchars($race_cars[$n]), htmlspecialchars($flap_times[$n]));
			if ($tfmt == "verT") {
				print("<td align=right>");
				if (is_null($t = array_shift($race_times[$n]))) {
					print("N/A");
				}
				else {
					print("\n");
					do {
						printf("%s<br/>\n", htmlspecialchars($t));
					} while (!is_null($t = array_shift($race_times[$n])));
				}
				print("</td>");
			}
			print("</tr>\n");
		}
		print("</table><br/>\n");
	}

	$nr_base += $nr;
	$r_group++;
}

fclose($finfo);

@readfile_htmlstrip("$season/$race/_rtimes_fin.html");

echo "<hr/>Zobrazit <a href=\"rtimes.php?tfmt=ver&amp;season=$season&amp;race=$race\">Vertikálně</a> <a href=\"rtimes.php?tfmt=verT&amp;season=$season&amp;race=$race\">(se všemi časy)</a> <a href=\"rtimes.php?tfmt=hor&amp;season=$season&amp;race=$race\">Horizontálně</a> <a href=\"rtimes.php?tfmt=horT&amp;season=$season&amp;race=$race\">(se všemi časy)</a>\n";

race_html_finish();

function rtimes_dummy_html()
{
?>
<html>
<!-- let confuse WZ-REKLAMA-adder, it's already in main_basic.php, which is always included -->
<!--WZ-REKLAMA-1.0-->
</html>
<?
}
?>
<? /* vi: set cindent: */ ?>
