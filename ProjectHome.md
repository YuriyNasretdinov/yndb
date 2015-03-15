## Abstract ##

YNDb is the high-performance database engine, written in PHP, with support of indexed fields.

For a moment, it does not support SQL.

Some usage examples:

```

$db = new YNDb('my_data');

$db->create('test_table', array('id' => 'INT', 'name' => 'TINYTEXT'), array('AUTO_INCREMENT' => 'id') );

for($i = 0; $i < 100; $i++)
{
$db->insert('test_table', array('name' => 'value'.$i));
}

$results = $db->select('test_table', array('cond' => 'id > 30', 'limit' => '30,50', 'order' => 'name ASC') );

print_r($results);

```

## Purpose ##

The database is made to be a proof-of-concept that one can write a relatively high-performance RDBMS on any language, including interpreteded ones as PHP.

It is not production-ready at the moment, as contains several bugs that can break database consistency.