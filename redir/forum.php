<?
$q = $HTTP_SERVER_VARS['QUERY_STRING'];
if ($action == "go")
	$q .= "#id$id";
header("Location: ../forum.php?topic=0".($q == ""?"":"&$q"));
?>
