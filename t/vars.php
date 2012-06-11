<?
?>
<html>
<?

echo "$REMOTE_ADDR:$REMOTE_PORT<br>\n";

foreach ($HTTP_SERVER_VARS as $key => $val) {
	printf("%s: %s<br>\n", htmlspecialchars($key), htmlspecialchars($val));
}

$rh = $HTTP_SERVER_VARS['REMOTE_ADDR'];
if (!is_null($HTTP_SERVER_VARS["REMOTE_HOST"]))
	$rh = $HTTP_SERVER_VARS['REMOTE_HOST'];
if (!is_null($HTTP_SERVER_VARS["ORIGINAL_REMOTE_HOST"]))
	$rh = $HTTP_SERVER_VARS['ORIGINAL_REMOTE_HOST'];
elseif (!is_null($HTTP_SERVER_VARS['HTTP_X_FORWARDED_FOR']))
	$rh = ereg_replace("^.*, *", "", $HTTP_SERVER_VARS['HTTP_X_FORWARDED_FOR'])."*".$rh;
printf("HOST: %s<br>\n", $rh);

foreach ($HTTP_POST_FILES as $key => $val) {
	$fi = $HTTP_POST_FILES[$key];
	printf("<p>File %s<br>\n", htmlspecialchars($key));
	foreach ($fi as $fk => $fv) {
		printf("%s: %s<br>\n", htmlspecialchars($fk), htmlspecialchars($fv));
	}
	printf("</p>\n");
}

?>
</html>
<?
?>
