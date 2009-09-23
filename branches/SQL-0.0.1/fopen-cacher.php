<?
// descriptor caching (can really improve speed in some cases)

function fopen_cached($name, $mode, $lock = false) // note, that arguments are not the same as fopen()!
{
	static $fopen_cache = array();
	
	$key = $name.':'.$mode;
	if(isset($fopen_cache[$key])) return $fopen_cache[$key]['fp'];
	
	if(!($fp = fopen($name, $mode)) && is_file($name))
	{
		// check other stuff
		if((strpos($mode,'r')!==false || strpos($mode,'+')!==false) && !is_readable($name)) return false;
		if((strpos($mode,'w')!==false || strpos($mode,'a')!==false) && !is_writable($name)) return false;
		
		// if all is ok (file readable & writable and it is file)
		// the we just hit the limit of max. opened files and should
		// free a file that is stored in cache to get a room for a new entry
		
		$el=(array_shift($fopen_cache));
		fclose($el['fp']); // fclose releases lock, if it was set
	}
	
	if($lock) @flock($fp, LOCK_EX);
	
	$fopen_cache[$name.':'.$mode] = array('fp'=>$fp, 'mode'=>$mode/*, 'locked'=>$lock*/);
	
	return $fp;
}
?>