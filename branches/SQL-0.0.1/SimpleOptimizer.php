<?php

echo "SimpleOptimizer included.\n";

class YNSimpleOptimizer {
	protected $db = null;
	protected $tokens = array();
	protected $hash = '';
	protected $running_table = '';
	
	public function __construct($db) {
		$this->db = $db;
	}
	
	public function setQuery($tokens, $hash) {
		// sanity check
		if (is_array($tokens)) {
			$this->tokens = $tokens;
			$this->hash = $hash;
		} else {
			throw new Exception('Array expected.');
		}
	}
	
	protected function validateSelect($from, $to) {
		if ($to !== $from + 1
			or $this->tokens[$to][0] !== YNParser::TOKEN_SINGLECHAR
			or $this->tokens[$to][1] !== '*') {
			throw new Exception('SELECT clause can contain only \'*\'.');
		}
	}
	
	protected function validateFrom($from, $to) {
		if ($to !== $from + 1) {
			throw new Exception('FROM clause must contain one table reference.');
		}
		$table_token = $this->tokens[$to];
		if ($table_token[0] !== YNParser::TOKEN_IDENTIFIER) {
			throw new Exception('Table reference must be an identifier.');
		}
		$table_name = $table_token[1];
		# TODO: check if the table exists
		$this->running_table = $table_name;
	}

	public function createPlan() {
		// Here's where the Optimizer's real job is done.
		// This is going to be very complex, but by now we have to make it pretty simple for the sake of development.

		// Blow up if the statement is not a SELECT:
		if (!($this->tokens[0][0] == YNParser::TOKEN_RESERVED and $this->tokens[0][1] == 'SELECT')) {
			throw new Exception("Not implemented.");
		}

		// a VERY SIMPLE clause search:
		$clauses = array('SELECT' => 0);
		foreach ($this->tokens as $_t_k => $t) {
			if ($t[0] = YNParser::TOKEN_RESERVED) {
				if (in_array($t[1], array('FROM', 'WHERE', 'GROUP', 'ORDER'))) {
					$clauses[$t[1]] = $_t_k;
				}
			}
		}
		$last_token_id = count($this->tokens);
		
		// Validate ORDER BY clause:
		if (isset($clauses['ORDER'])) {
			throw new Exception('Not implemented yet.');
		}
 		// Validate GROUP BY clause:
		if (isset($clauses['GROUP'])) {
			throw new Exception('Not implemented yet.');
		}
		// Validate WHERE clause:
		if (isset($clauses['WHERE'])) {
			throw new Exception('Not implemented yet.');
			//$this->validateWhere($clauses['WHERE'], $last_token_id - 1);
			//$last_token_id = $clauses['WHERE'];
		}
		// Validate FROM clause:
		if (isset($clauses['FROM'])) {
			$this->validateFrom($clauses['FROM'], $last_token_id - 1);
			$last_token_id = $clauses['FROM'];
		}
		// Validate SELECT clause:
		$this->validateSelect(0, $last_token_id - 1);

		// Generate the execution plan class file:
		$export_tokens = var_export($this->tokens, true);
		$plan_class_name = YNParser::PLAN_PREFIX . $this->hash;
		$plan_class_path = dirname(__FILE__) . '/plans/' . $plan_class_name . '.php';
		file_put_contents(
			$plan_class_path,
			<<<EOD
<?php

class $plan_class_name {
	protected \$db = null;
	protected \$rt = '';
	protected \$r = array();
	
	function __construct() {
		\$this->rt = '$this->running_table';
		echo 'Created ' . __CLASS__ . ".\n";
	}
	
	function setDB(\$db) {
		\$this->db = \$db;
	}
	
	function execute() {
		\$this->r = \$this->db->openTable_FullScan(\$this->rt);
	}
		
	function fetch() {
		return \$this->db->fetchRow_FullScan(\$this->r);
	}

	function __test() {
		echo 'Ran ' . __CLASS__ . "::__test().\n";
	}
}

return new $plan_class_name();

?>
EOD
		);

		// Include the generated class and return it to the outer scope:
		$plan = require($plan_class_path);
		$plan->setDB($this->db);
		$plan->__test();
		return $plan;
	}
}

if (array_shift(get_included_files()) === __FILE__) {
	if (isset($argv[1])) {
		$yo = new YNSimpleOptimizer();
		$yo->__test($argv[1]);
	} else {
		echo "Usage: php " . basename(__FILE__) . " <sql text>\n";
	}
}

?>