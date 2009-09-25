<?
error_reporting(E_ALL);
ini_set('display_errors', 'On');

include 'YNDb/DataInterface.php';

function print_header($res)
{
	$keys = array_keys($res);
	
	echo '<table border=1><tr>';
	foreach($keys as $v) echo '<th>'.$v.'</th>';
	echo '</tr>';
}
function print_res($res)
{
	echo '<tr>';
	
	foreach($res as $v)
	{
		echo '<td>'.$v.'</td>';
		
	}
	echo '</tr>';
}
function print_footer($res)
{
	echo '</table>';
}


echo '<h3>All columns:</h3>';

$DI = new YNDataInterface('data');

$dd = $DI->openTable_FullLookup('test3') or die($DI->get_error());

$i = 0;

$start_time = microtime(true);

while($res = $DI->fetchRow_FullLookup($dd))
{
	$i++;
	
	//if(!$i++) print_header($res);
	
	//print_res($res);
}

$DI->closeTable_FullLookup($dd);

echo 'DataInterface took '.round( microtime(true) - $start_time, 4 ).' sec ('.$i.' results)<br>';

$start_time = microtime(true);

$res = $DI->select( 'test3', array( 'limit' => 10000000 ) );

echo 'select() method took '.round( microtime(true) - $start_time, 4 ).' sec ('.sizeof($res).' results)<br>';


echo '<h3>Several columns:</h3>';

$dd = $DI->openTable_FullLookup('test3', array('rand', 'another_rand')) or die($DI->get_error());

$i = 0;

$start_time = microtime(true);

while($res = $DI->fetchRow_FullLookup($dd))
{
	$i++;
	
	//if(!$i++) print_header($res);
	
	//print_res($res);
}

$DI->closeTable_FullLookup($dd);

echo 'DataInterface took '.round( microtime(true) - $start_time, 4 ).' sec ('.$i.' results)<br>';

$start_time = microtime(true);

$res = $DI->select( 'test3', array( 'col' => 'rand,another_rand', 'limit' => 10000000 ) );

echo 'select() method took '.round( microtime(true) - $start_time, 4 ).' sec ('.sizeof($res).' results)<br>';

//print_footer($res);
?>