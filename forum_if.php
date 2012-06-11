<?

class ForumDataIf
{
	var $err;

	function getError()
	{
		return $this->err;
	}
	function setUser($user)
	{
	}
	function getUser()
	{
	}
	function getUinfo(&$name_, &$email_, &$wpage_)
	{
	}
	function getPagesize()
	{
	}
	function setTopic($topic)
	{
	}
	function getRow($id)
	{
	}
	function getLast()
	{
	}
	function startRead($id, $len)
	{
	}
	function readNext()
	{
	}
	function finishRead()
	{
	}
	function getLastList()
	{
	}
	function getTopicList()
	{
	}
	function addNew($subj, $body, $orig)
	{
	}
}

class ForumViewIf
{
	function setTopic($topic)
	{
	}
	function printError($err)
	{
	}
	function showError($err)
	{
	}
	function startPage()
	{
	}
	function finishPage()
	{
	}
	function startRows($from, $to, $pgsize)
	{
	}
	function genRow($row, $istarget)
	{
	}
	function finishRows($from, $to, $pgsize)
	{
	}
	function showReply($name, $email, $wpage, $orig, $subj)
	{
	}
	function startLast()
	{
	}
	function genLast($name, $where)
	{
	}
	function finishLast()
	{
	}
	function startSelect()
	{
	}
	function genSelect($id, $name)
	{
	}
	function finishSelect()
	{
	}
	function refresh()
	{
	}
}

class Forum
{
	var $dif;
	var $vif;

	function setTopic($topic)
	{
		$this->dif->setTopic($topic);
		$this->vif->setTopic($topic);
	}

	function showTop()
	{
		$pgsize = $this->dif->getPagesize();
		$from = -$pgsize;
		if ($this->dif->startRead($from, $pgsize) === false) {
			$this->vif->showError($this->dif->getError());
			return;
		}
		$to = $from+$pgsize;
		$nid = $from;
		$this->vif->startRows($from, $to, $pgsize);
		while (($r = $this->dif->readNext()) !== false && $r["id"] < $to) {
			$this->vif->genRow($r, false);
			$nid = $r["id"];
		}
		$this->vif->finishRows($from, $nid+1, $pgsize);
	}

	function showId($id)
	{
		$pgsize = $this->dif->getPagesize();
		if (($from = $id-$pgsize/2) < 0)
			$from = 1;
		if ($this->dif->startRead($from, $pgsize) === false) {
			$this->vif->showError($this->dif->getError());
			return;
		}
		$to = $from+$pgsize;
		$nid = $from;
		$this->vif->startRows($from, $to, $pgsize);
		while (($r = $this->dif->readNext()) !== false && $r["id"] < $to) {
			$this->vif->genRow($r, $r["id"] == $id);
			$nid = $r["id"];
		}
		$this->vif->finishRows($from, $nid+1, $pgsize);
	}

	function showReply($id)
	{
		if ($id == 0) {
			$subj = "";
		}
		else {
			if (($r = $this->dif->getRow($id)) === false) {
				$this->vif->showError((($err = $this->dif->getError()) == "")?"invalid orig message":$err);
				return;
			}
			$subj = $r["subj"];
			if (!ereg("[rR][eE]:", $subj, $regs))
				$subj = "Re: $subj";
		}
		$this->dif->getUinfo($name, $email, $wpage);
		$this->vif->showReply($name, $email, $wpage, $id, $subj);
	}

	function showLast()
	{
		if (($l = $this->dif->getLastList()) === false) {
			$this->vif->showError($this->dif->getError());
			return;
		}
		$this->vif->startLast();
		foreach ($l as $k => $v) {
			ereg("^([^:]*):(.*)$", $v, $regs);
			$this->vif->genLast($k, $regs[1], $regs[2]);
		}
		$this->vif->finishLast();
	}

	function showSelect()
	{
		if (($l = $this->dif->getTopicList()) === false) {
			$this->vif->showError($this->dif->getError());
			return;
		}
		$this->vif->startSelect();
		foreach ($l as $k => $v) {
			$this->vif->genSelect($k, $v);
		}
		$this->vif->finishSelect();
	}

	function showSet()
	{
	}

	function goId()
	{
	}

	function addMsg($subj, $body, $orig)
	{
		if ($this->dif->addNew($subj, $body, $orig) === false) {
			$this->vif->showError($this->dif->getError());
		}
		else {
			$this->vif->refresh();
		}
	}
}

?>
