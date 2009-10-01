<?

error_reporting(E_ALL);
ini_set('display_errors', 'On');

set_time_limit(0);

class ErrorPrinter
{
    function set_error($err)
    {
        echo '<b>Error:</b> '.$err.'<br>';
    }
}

$errpr = new ErrorPrinter();

include('YNDb/core/BTree.php');

fclose(fopen('btr.dat', 'wb')); // clear previous contents and create file

$fp = fopen('btr.dat', 'r+b');

$btr = new YNBTree($errpr, $fp);

$btr->create();

$num = 9;
if(isset($_GET['num'])) $num = $_GET['num'];
if(isset($argv[1])) $num = $argv[1];

define('NUM', $num);
define('STEP', 1);

$vals = range(0, NUM);
shuffle( $vals );

$b4 = microtime(true);

ob_start();

for($i = NUM; $i >= 0; $i-=STEP )
{
    //echo 'Insert '.$vals[$i].'<br>';
    $btr->insert($vals[$i], NUM+$vals[$vals[$i]]);
}

ob_end_clean();

echo 'Inserted '.floor(NUM/STEP).' for '.round(microtime(true)-$b4,4).' sec<br>';

// delete 1/2 of all entries
define('DELETE_START', round(NUM/4/STEP)*STEP);
define('DELETE_STOP', round(3*NUM/4/STEP)*STEP);

$b4 = microtime(true);

for($i = DELETE_START; $i < DELETE_STOP; $i+=STEP)
{
    //echo 'Delete '.$i.'<br>';
    $btr->delete($i);
}

echo 'Deleted '.floor((DELETE_STOP-DELETE_START)/STEP).' for '.round(microtime(true)-$b4,4).' sec<br>';

$ins_back = round( ( DELETE_START + (DELETE_STOP-DELETE_START)/2 ) / STEP) * STEP - STEP;

define('INSERT_BACK', min(round(NUM/2),10));

for($j = 0; $j < INSERT_BACK; $j+=STEP)
{
    $ins_back+=STEP;
    echo 'Insert value back: '.$ins_back.'<br>';
    $btr->insert($ins_back, NUM+$vals[$vals[$ins_back]]);
}

$root = $btr->read_root();

$b4 = microtime(true);

for($i = 0; $i <= NUM; $i+=STEP )
{
    $res = $btr->search($root, $i);
    
    if(!$res && ($i < DELETE_START || $i >= DELETE_STOP))
    {
        echo '<b>Error:</b> Key '.$i.' not found<br>';
        continue;
    }else if($res && ($i >= DELETE_START && $i < DELETE_STOP)) // means that something found that should have been :)
    {
        echo '<b>Error:</b> Found a deleted entry '.$i.'<br>';
        continue;
    }else if(!$res)
    {
        continue;
    }
    
    list($value, $offset) = $res;
    
    if($value != $i || $offset != NUM+$vals[$i])
    {
        echo 'Incorrect entry returned (expected '.$i.':'.(NUM+$i).', got '.$value.':'.$offset.')<br>';
    }
}

echo 'Searched for '.round(microtime(true)-$b4,4).' sec<br>';

fclose($fp);

?>