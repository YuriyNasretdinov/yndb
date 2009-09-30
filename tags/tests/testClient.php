<?php

include 'Client.php';

$db = new YNClient('test');
$q = $db->query('select * from cdr');
while ($r = $q->fetch()) {
	echo join("\t", $r) . "\n";
}

?>