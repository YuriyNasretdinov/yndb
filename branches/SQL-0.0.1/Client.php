<?php

/**
 * WARNING: These classes are not yet intended for production use!
 * Their goal is to provide a basic level of support for SQL for testing purposes.
 * Although the interface (public methods, parameters) may be pretty simple
 * and straightforward, it is unlikely that it will undergo serious changes.
 * Nevertheless, you SHOULD NOT rely on this interface until first release,
 * for it may slightly change in future versions without any notice.
 */

include 'Parser.php';

class YNResource {
	protected $db = null;
	protected $sql = null;
	protected $parser = null;
	protected $plan = null;
	
	public function __construct($db, $parser, $sql) {
		$this->db = $db;
		$this->parser = $parser;
		$this->sql = $sql;
		if (isset($this->parser)) {
			$this->plan = $this->parser->getPlan($this->sql);
			$this->plan->execute();
		} else {
			throw new Exception('Parser not instantiated.');
		}
	}
	
	// removing circular references, so not causing a memory leak
	public function __destruct() {
		$this->db     = null;
		$this->parser = null;
		$this->sql    = null;
		$this->plan   = null;
	}
	
	public function fetch() {
		return $this->plan->fetch();
	}
}

class YNClient {
	protected $db = null;
	protected $parser = null;
	
	public function __construct($dir) {
		// eventually, YNDataInterface will support exceptions, as intended here
		$this->db = new YNDataInterface($dir);
		$this->parser = new YNParser($this->db);
	}
	
	// removing circular references, so not causing a memory leak
	public function __destruct() {
		$this->db     = null;
		$this->parser = null;
	}
	
	public function query($sql) {
		return new YNResource($this->db, $this->parser, $sql);
	}
}

?>