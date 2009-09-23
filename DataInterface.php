<?

include('db.php');

// a data interface to table
// an extension of YNDb class,
// which just provides a method-like API for data access, without raw access

// should be initiatied like YNDb:
// $DI = new YNDataInterface( 'work_directory' );
// 
// you must not have multiple instances of that class!
// you must not also have both YNDb and YNDataInterface be instantiated at the same time!

class YNDataInterface extends YNDb
{
	
	function __construct($dir)
	{
		// perhaps, some logic could be added here
		
		return parent::__construct($dir);
	}
	
	function __destruct()
	{
		// perhaps, some logic could be added here
		
		return parent::__destruct();
	}
	
	// returns "resource" for fetchRow
	
	// string $name    -- name of table
	// mixed  $columns -- list of columns to be read ( array(fieldname1, fieldname2, ....) ), or false if need to return all fields
	
	// returns false or "resource"
	
	function openTable_FullLookup($name, $columns = false)
	{
		if(!$this->lock_table($name)) return false;
		
		// oh yeah, better use exceptions :)
		// perhaps it will be rewritten to use them
		
		do
		{
			if(!$fp = fopen_cached($this->dir.'/'.$name.'.dat', 'r+b'))
			{
				$err = 'Data file corrupt.';
				break;
			}
			
			fseek($fp, 0, SEEK_END);
			$end = ftell($fp) - 1; // actually, I do not remember why last byte should not be read :))
			
			$fields = $this->locked_tables_list[$name]['fields'];
			
			fseek($fp, 0);
			
			if($columns)
			{
				if(sizeof($inv = array_udiff($columns, $valid = array_keys($fields), 'strcmp')))
				{
					$err = 'Unknown column(s): '.implode(', ',$inv).'. Valid are: '.implode(', ',$valid);
					break;
				}
				
				$columns = array_flip($columns); // create an array like array( 'field1' => 0, 'field2' => 1, ... )
			}
		}while(false);
		
		if(isset($err))
		{
			$this->unlock_table($name);
			return $this->set_error($err);
		}

		return array( $name, $fp, $columns, $fields, $end );
	}
	
	// fetch the next row
	// 
	// returns array( 'field1' => value1, 'field2' => value2, ... ) or false
	
	function fetchRow_FullLookup($resource)
	{
		if(!$resource) return $this->set_error('Invalid resource specified');
		
		list( ,$fp, $columns, $fields, $end) = $resource;
		
		if(ftell($fp)>=$end) return false;
		
		$res = $this->read_row($fields, $fp);
		
		if(!$columns) return $res;
		else          return array_intersect_key($res, $columns);
	}
	
	// close table
	// better not to call more, than once :)
	//
	// returns true or false
	
	function closeTable_FullLookup($resource)
	{
		if(!$resource) return $this->set_error('Invalid resource specified');
		
		list($name, , , , ) = $resource;
		return $this->unlock_table($name);
		
		// no fclose, because we use descriptor caching
	}
}

?>