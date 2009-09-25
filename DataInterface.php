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
//
// you also must not have the same table be opened several times simultaneously (e.g. FullScan + Index)

// So, remember: single instance per directory, all opened tables must be unique
// The last limitation could be avoided if libc supported normal "dup()" analogue. But it does not.

define('DI_INDEX_PRIMARY', 0);
define('DI_INDEX_INDEX',   1);
define('DI_INDEX_UNIQUE',  2);

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
	
	function set_error($err)
	{
		return parent::set_error('DataInterface (internal) error: '.$err);
	}
	
	// returns "resource" for fetchRow
	
	// string $name    -- name of table
	// mixed  $columns -- list of columns to be read ( array(fieldname1, fieldname2, ....) ), or false if need to return all fields
	
	// returns false or "resource"
	
	function openTable_FullScan($name, $columns = false)
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
				$columns = $this->checkColumns($fields, $columns);
				if(is_string($columns)) { $err = $columns; break; }
			}
		}while(false);
		
		if(isset($err))
		{
			$this->unlock_table($name);
			return $this->set_error($err);
		}

		return array( $name, $fp, $columns, $fields, $end );
	}
	
	// checks if $columns tries to fetch only fields that exist
	// returns string with error description in case of failure
	// otherwise returns a flipped columns array
	
	protected function checkColumns($fields, $columns)
	{
		if(sizeof($inv = array_udiff($columns, $valid = array_keys($fields), 'strcmp')))
		{
			return 'Unknown column(s): '.implode(', ',$inv).'. Valid are: '.implode(', ',$valid);
		}

		return array_flip($columns); // create an array like array( 'field1' => 0, 'field2' => 1, ... )
	}
	
	// fetch the next row
	// 
	// returns array( 'field1' => value1, 'field2' => value2, ... ) or false
	
	function fetchRow_FullScan($resource)
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
	
	function closeTable_FullScan($resource)
	{
		if(!$resource) return $this->set_error('Invalid resource specified');
		
		return $this->unlock_table($resource[0] /*name*/);
		
		// no fclose, because we use descriptor caching
	}
	
	// the same, as openTable_FullScan, but uses index(es) for column $col, using $value
	
	function openTable_Index_ExactMatch($name, $columns, $col, $value)
	{
		static $uniqid = 0;
		
		if(!$this->lock_table($name)) return false;
		
		// oh yeah, better use exceptions :)
		// perhaps it will be rewritten to use them
		
		$type = false; // types can be: DI_INDEX_PRIMARY, DI_INDEX_INDEX, DI_INDEX_UNIQUE
		$pointers = false; // if one pointer, than it is just a value. Several pointers should be presented as cortege (an ordered list)
		
		do
		{
			if(!$fp = fopen_cached($this->dir.'/'.$name.'.dat', 'r+b'))
			{
				$err = 'Data file corrupt.';
				break;
			}
			
			extract(/*$str_res = */$this->locked_tables_list[$name]);
			
			if($col == $aname)               $type = DI_INDEX_PRIMARY;
			else if(in_array($col, $index))  $type = DI_INDEX_INDEX;
			else if(in_array($col, $unique)) $type = DI_INDEX_UNIQUE;
			else
			{
				$err = 'No index for column `'.$col.'`"';
				break;
			}
			
			if($columns)
			{
				$columns = $this->checkColumns($fields, $columns);
				if(is_string($columns)) { $err = $columns; break; }
			}
			
			switch($type)
			{
				case DI_INDEX_PRIMARY:
					
					$pfp = fopen_cached($this->dir.'/'.$name.'.pri', 'r+b');
					
					if(!$pfp)
					{
						$err = 'Primary index corrupt';
						break(2);
					}
					
					$pointers = $pfp;
					break;
				case DI_INDEX_UNIQUE:
				
					$ufp = fopen_cached($this->dir.'/'.$name.'.btr', 'r+b');
					
					if(!$ufp)
					{
						$err = 'B-Tree index corrupt';
						break(2);
					}
					
					$pointers = $ufp;
					break;
				case DI_INDEX_INDEX:
				
					$ifp  = fopen_cached($this->dir.'/'.$name.'.btr', 'r+b');
					$ifpi = fopen_cached($this->dir.'/'.$name.'.idx', 'r+b');
					
					if(!$ifp || !$ifpi)
					{
						$err = 'Either B-Tree or List index is corrupt';
						break(2);
					}
					
					$pointers = array($ifp, $ifpi);
					break;
				default:
					
					$err = 'Unknown index type (this error must never happen)';
					break(2);
					
					break;
			}
			
		}while(false);
		
		if(isset($err))
		{
			// should not close anything, as descriptors are cached (they are not cached in case of failure, so no need to worry about this either)
			$this->unlock_table($name);
			return $this->set_error($err);
		}
		
		$uniqid++;
		
		return array($name, $columns, $col, $value, $meta, $type, $pointers, $fp, $uniqid, $fields);
	}
	
	function fetchRow_Index_ExactMatch($resource)
	{
		static $results_cache = array(); // array( uniqid => array( entry1, entry2, entry3, ... ) )
		//static $results_index_cache = array(); // array( uniqid => counter )
		
		if(!$resource) return $this->set_error('Invalid resource specified');
		
		list($name, $columns, $col, $value, $meta, $type, $pointers, $fp, $uniqid, $fields) = $resource;
		
		switch($type)
		{
			case DI_INDEX_PRIMARY:
				
				$pfp = $pointers;
				
				if(isset($results_cache[$uniqid]))
				{
					unset($results_cache[$uniqid]);
					return false; // only 1 entry can be returned
				}
				
				if($value <= 0) return false; // primary key values are positive-only
				
				fseek($pfp, 0, SEEK_END);
				$end = ftell($pfp);
				
				// cannot have values that exceed auto_increment value
				// 'acnt' contains the already-incremented counter, so
				// we use ">=", as value 'acnt' does not also exist yet
				if($value >= $this->locked_tables_list[$name]['acnt']) return false;
				
				fseek($pfp, $value*4);
				
				list(,$offset) = unpack('l', fread($pfp, 4));
				
				if($offset < 0) return false; // negative values mean that entry is deleted (oh yes, I know that it is a waste of space :))
				
				$results_cache[$uniqid] = true; // a flag that should indicate that an entry (max. 1 entry) is returned and no need to return it again
				
				break;
			case DI_INDEX_UNIQUE:
			
				$ufp = $pointers;
				
				if(isset($results_cache[$uniqid]))
				{
					unset($results_cache[$uniqid]);
					return false; // only 1 entry can be returned
				}
				
				$res = $this->I->BTR->fsearch($ufp, $meta[$col], $value);
				
				if(!$res) return false;
				
				list(,$offset) = $res;
				
				$results_cache[$uniqid] = true; // a flag that should indicate that an entry (max. 1 entry) is returned and no need to return it again
				
				break;
			case DI_INDEX_INDEX:
			
				list($ifp, $ifpi) = $pointers;
				
				if(!isset($results_cache[$uniqid]))
				{
					$res = $this->I->BTRI->search($ifp, $ifpi, $meta[$col], $value);
					
					if(!$res) return false;
					
					$results_cache[$uniqid] = $res;
				}
				
				if(!sizeof($results_cache[$uniqid]))
				{
					unset($results_cache[$uniqid]);
					return false; // all entries already returned
				}
				
				$offset = array_pop($results_cache[$uniqid]); // steadily decreasing number of elements to return until nothing left
				
				break;
			default:
				
				$err = 'Unknown index type (this error must never happen)';
				break(2);
				
				break;
		}
		
		fseek($fp, $offset);
		
		$res = $this->read_row($fields, $fp);
		
		if(!$columns) return $res;
		else          return array_intersect_key($res, $columns);
	}
	
	function closeTable_Index_ExactMatch($resource)
	{
		if(!$resource) return $this->set_error('Invalid resource specified');
		
		return $this->unlock_table($resource[0] /*name*/);
	}
}

?>