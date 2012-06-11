<?
require_once "main_basic.php";

std_html_log_access();

$rh = $HTTP_SERVER_VARS['REMOTE_ADDR'];
if (!is_null($HTTP_SERVER_VARS['REMOTE_HOST']))
	$rh = $HTTP_SERVER_VARS['REMOTE_HOST'];
if (!is_null($HTTP_SERVER_VARS['ORIGINAL_REMOTE_HOST']))
	$rh = $HTTP_SERVER_VARS['ORIGINAL_REMOTE_HOST']."*".$rh;
elseif (!is_null($HTTP_SERVER_VARS['HTTP_X_FORWARDED_FOR']))
	$rh = ereg_replace("^.*, *", "", $HTTP_SERVER_VARS['HTTP_X_FORWARDED_FOR'])."*".$rh;

if (!ereg('^[a-z]+$', $poll_dom) || ($poll_id = intval($poll_id)) <= 0 || !file_exists("polls/$poll_dom$poll_id.count")) {
	# ignore invalid input
}
elseif (($poll_answ = intval($poll_answ)) < 0) {
	# ignore invalid input
}
else {
	$do_write = 1;
	if (ereg("$poll_dom@([0-9]+)", $poll_last, $regs)) {
		if ($poll_id > $regs[1])
			$poll_last = ereg_replace("$poll_dom@[0-9]+", "$poll_dom@$poll_id", $poll_last);
		else
			$do_write = 0;
	}
	else {
		$poll_last = "$poll_last:$poll_dom@$poll_id";
	}
	if (
			#!ereg("^.*192\\.168\\..*$", $rh) &&
			#!ereg("^.*unld...\\.nextra\\.cz.*$", $rh) &&
			#!ereg("^.*t182\\.mistral\\.cz.*$", $rh) &&
			$do_write
	   ) {
		if (!($fh = fopen("polls/$poll_dom$poll_id.count", "r+")) || !flock($fh, LOCK_EX))
			exit();
		$fl = file("polls/$poll_dom$poll_id.count");

		if ($poll_answ+1 < count($fl)) {
			$fl[0] += 1; $fl[0] .= "\n";
			$fl[$poll_answ+1] += 1; $fl[$poll_answ+1] .= "\n";
		}

		if (($flog = @fopen("log/poll_$poll_dom$poll_id.log", "a"))) {
			$tim = strftime("%Y-%m-%d %H:%M:%S");
			@fputs($flog, sprintf("%s, %s: %d\n", $tim, $rh, $poll_answ));
			fclose($flog);
		}

		fwrite($fh, join("", $fl));
		flock($fh, LOCK_UN);
		fclose($fh);
	}

	setcookie("poll_last", $poll_last, time()+365*24*3600, "/");
}

$uref = $referer;
$uref .= strpos($uref, "?") === false?"?":"&";
header("Location: ".$uref."poll_rand=".rand());
?>
