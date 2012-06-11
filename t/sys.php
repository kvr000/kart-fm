<?
if (0) {
header("Content-type: text/plain; charset=UTF-8");
echo "id:\n";
system("id");
echo "machine:\n";
readfile("/proc/cpuinfo");
echo "mem:\n";
system("free");
}
elseif (0) {
	phpinfo();
}
elseif (0) {
	header("Content-type: text/plain; charset=UTF-8");
}
?>
