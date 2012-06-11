<?

class ForumDataIf_File extends ForumDataIf
{
	var $forum_dir;

	var $topic;

	var $fd;

	var $user;

	function setUser($user_)
	{
		$this->user = $user_;
		if (!ereg("^([^;]*);([^;]*);([^;]*)$", $this->user, $regs)) {
			$this->user = ";;";
		}
		$this->decode_id($this->user, $name, $email, $wpage);
		if (!preg_match("/^[\\s.]*$/", $name) && !preg_match("/.*[\\/].*/", $name)) {
			if (($f = fopen("$this->forum_dir/last/$name", "w"))) {
				fputs($f, join("<br>\n", file("$this->forum_dir/$this->topic/_info")));
				fclose($f);
			}
		}
	}

	function getUser()
	{
		return $this->user;
	}

	function getUinfo(&$name_, &$email_, &$wpage_)
	{
		$this->decode_id($this->user, $name_, $email_, $wpage_);
	}

	function getPagesize()
	{
		return $this->pagelen;
	}

	function openOnId(&$id)
	{
		if ($id < 0) {
			if (($id += $this->getLast()+1) < 0)
				$id = 1;
		}
		if (($fh = fopen($this->forum_dir.$this->topic."/forum.txt", "r")) === false) {
			$this->err = sprintf("Failed to open forum file: %s", posix_strerror(posix_get_last_error()));
			return false;
		}
		clearstatcache();
		$regs = fstat($fh);
		$min = 0; $max = $regs["size"];
		if (($pos = $max-8192) < 0) # optimize read to go for last 8k
			$pos = 0;
		fseek($fh, $pos, SEEK_SET);
		$search = "\t".($id-1)."\n";
		#print("$min, $max, $pos<br>\n");
		for (;;) {
			$d = fread($fh, 4096);
			#print("--$d--<br>\n");
			if (($spos = strpos($d, $search)) !== false) {
				$pos += $spos+strlen($search);
				break;
			}
			if (!ereg("^.*\t([0-9]*)\n.*$", $d, $regs)) {
				$this->err = sprintf("Forum file corrupted near position %d", $pos);
				fclose($fh);
				return false;
			}
			if ($regs[1] >= $id) {
				$max = $pos;
			}
			elseif ($regs[1] < ($id-1)) {
				$min = $pos;
			}
			else {
				$this->err = sprintf("strpos didn't find it! (%d)", ($id-1));
				fclose($fh);
				return false;
			}
			$newpos = ($min+$max)/2;
			if ($newpos == $pos) {
				$this->err = sprintf("failed to find %d in forum file", ($id-1));
				fclose($fh);
				return false;
			}
			$pos = $newpos;
			if (fseek($fh, $pos, SEEK_SET) < 0) {
				$this->err = sprintf("failed to seek to position %d", $pos);
				fclose($fh);
				return false;
			}
		}
		if (fseek($fh, $pos, SEEK_SET) < 0) {
			fclose($fh);
			$this->err = sprintf("failed to seek to position %d", $pos);
			return false;
		}
		return $fh;
	}

	function closeFile($fh)
	{
		fclose($fh);
	}

	function escapeFrom($s)
	{
		return $s;
	}

	function escapeTo($s)
	{
		$s = ereg_replace("[ \t\r\n]", " ", $s);
		return $s;
	}

	function decode_id($id, &$name, &$email, &$wpage)
	{
		if (ereg("^([^;]*);([^;]*);([^;]*)$", $id, $regs)) {
			$name = $regs[1]; $email = $regs[2]; $wpage = $regs[3];
		}
		else {
			$name = $id; $email = ""; $wpage = "";
		}
	}

	function encode_id($name, $email, $wpage)
	{
		return ereg_replace("[\t\n]", " ", "$name;$email;$wpage");
	}

	function fileNext($fh)
	{
		$o = array();
		$row = fgets($fh, 4096);
		if (!ereg("^(.*)\t(.*)\t(.*)\t(.*)\t(.*)\t(.*)\n\$", $row, $regs)) {
			$this->err = sprintf("invalid record-format: %s", htmlspecialchars($row));
			return false;
		}
		$o["body"] = $this->escapeFrom($regs[1]);
		$o["subj"] = $this->escapeFrom($regs[2]);
		$o["uid"] = $this->escapeFrom($regs[3]);
		$o["time"] = $regs[4];
		$o["oid"] = $regs[5];
		$o["id"] = $regs[6];
		$this->decode_id($o["uid"], $o["name"], $o["email"], $o["wpage"]);

		return $o;
	}

	function setTopic($topic_)
	{
		$this->topic = $topic_;
	}

	function getRow($id)
	{
		if (($fh = $this->openOnId($id)) === false)
			return;
		$o = $this->fileNext($fh);
		$this->closeFile($fh);
		return $o;
	}

	function startRead(&$id, $len)
	{
		return ($this->fd = $this->openOnId($id));
	}

	function readNext()
	{
		$o = $this->fileNext($this->fd);
		return $o;
	}

	function finishRead()
	{
		$this->closeFile($this->fd);
	}

	function getNewId()
	{
		return $this->getLast()+1;
	}

	function getLast()
	{
		$row = "";
		if (!($fh = fopen(sprintf($this->forum_dir.$this->topic."/forum.txt"), "r"))) {
			$this->err = sprintf("failed to open forum file: %s", posix_strerror(posix_get_last_error()));
			return false;
		}
		fseek($fh, -13, SEEK_END);
		$row = fread($fh, 13);
		if (!ereg("\t([0-9]+)\n\$", $row, $regs)) {
			$this->err = "invalid format on last row";
			return false;
		}
		fclose($fh);
		return $regs[1];
	}

	function getLastList()
	{
		$ct = time();
		$fldir = "$this->forum_dir/last/";
		$out = array();
		if (!($dh = opendir("$fldir")))
			return false;

		while (($fn = readdir($dh))) {
			if ($fn == "." || $fn == "..")
				continue;
			$t = filemtime($fldir.$fn);
			if ($t < $ct-3600) {
				unlink($fldir.$fn);
			}
			else {
				$out[$fn] = "$t:".join("\n", file("$fldir$fn"));
			}
		}
		return $out;
	}

	function getTopicList()
	{
		$out = array();
		if (!($dh = opendir("$this->forum_dir/")))
			return false;

		while (($fn = readdir($dh)) !== false) {
			if (!ereg("^[0-9]+$", $fn))
				continue;
			$out[$fn] = join("\n", file("$this->forum_dir/$fn/_info"));
		}
		return $out;
	}

	function addNew($subj, $body, $orig)
	{
		if (($fw = fopen($this->forum_dir.$this->topic."/forum.txt", "a")) === false) {
			$this->err = sprintf("failed to open forum file for writing: %s", posix_strerror(posix_get_last_error()));
			return false;
		}
		if (!flock($fw, LOCK_EX)) {
			$this->err = sprintf("failed to lock forum file: %s", posix_strerror(posix_get_last_error()));
			fclose($fw);
			return false;
		}
		if (($next = $this->getNewId()) === false) {
			return false;
		}
		$row = sprintf("%s\t%s\t%s\t%d\t%d\t%d\n", $this->escapeTo($body), $this->escapeTo($subj), $this->escapeTo($this->user), time(), $orig, $next);
		if (strlen($row) > 4070) {
			$this->err = "too long";
			fclose($fw);
			return false;
		}
		fputs($fw, $row);
		flock($fw, LOCK_UN);
		fclose($fw);
	}
}

?>
