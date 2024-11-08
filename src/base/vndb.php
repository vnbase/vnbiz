<?php


/**
 *	vndb_init("localhost", "database", "password", "database") => mysqli;
 *	vndb_connection() => mysqli;
 *  vndb_query('SELECT *', '???', ['a', 1]) => []; 
 *  vndb_exec('SELECT *', '???', ['a', 1]) => affected_rows; 
 *  vndb_get_table_names(use_cache = true) => ['a', 'b']
 *  vndb_get_table_columns('table_name', use_cache = true) => ['id' => 'i']
 *  vndb_query_extract('table_name', $model)
 *	vndb_model_insert('table_name', ['id => 1])
 *  vndb_model_update_one('table_name', ['id => 1], ['name' => 'a'])
 *  vndb_model_find('table_name', ['filter' => '1'], limit, offset)
 *  vndb_model_find_one('table_name', ['filter' => '1'])
 * 
 * */

function vndb_init($servername = 'localhost', $username = "", $password = "", $dbname = '')
{
	if (function_exists('mysqli_connect') == false) {
		die("vndb_init: mysqli module is missing!");
	}

	if (function_exists('apcu_enabled') == false) {
		die("vndb_init: apcu module is missing!");
	}


	$GLOBALS['vndb_connection_instance'] = new mysqli($servername, $username, $password, $dbname);

	if ($GLOBALS['vndb_connection_instance']->connect_error) {
		die("vndb_failed: " . $GLOBALS['vndb_connection_instance']->connect_error);
	}

	return $GLOBALS['vndb_connection_instance'];
}

function vndb_connection()
{
	if (!isset($GLOBALS['vndb_connection_instance'])) {
		die("vndb_failed: call vndb_init(..) first!");
	}

	return $GLOBALS['vndb_connection_instance'];
}

function vndb_query($query, $types = null, $values = null)
{
	echo $query . '<br/>';
	$connection = vndb_connection();
	$stmt = $connection->prepare($query);

	if ($stmt == false) {
		die("vndb_failed: prepare fail " . $connection->error);
	}

	if ($types) {
		if ($stmt->bind_param($types, ...$values) == false) {
			die("vndb_failed: bind_param fail " . $connection->error);
		}
	}

	if ($stmt->execute() == false) {
		die("vndb_failed: execute fail " . $connection->error);
	}

	$response = $stmt->get_result();

	$results = [];
	while ($row = $response->fetch_assoc()) {
		$results[] = $row;
	}

	$stmt->close();

	return $results;
}


function vndb_exec($query, $types = null, $values = null)
{
	$connection = vndb_connection();
	$stmt = $connection->prepare($query);

	if ($stmt == false) {
		die("vndb_failed: prepare fail " . $connection->error);
	}

	if ($types) {
		if ($stmt->bind_param($types, ...$values) == false) {
			die("vndb_failed: bind_param fail " . $connection->error);
		}
	}

	if ($stmt->execute() == false) {
		die("vndb_failed: execute fail " . $connection->error);
	}

	$affected_rows = $stmt->affected_rows;
	$stmt->close();

	return $affected_rows;
}


function vndb_get_table_names($use_cache = true)
{
	echo $use_cache;
	if ($use_cache) {
		$cached = apcu_fetch('vndb_get_table_names');

		if ($cached) {
			return $cached;
		}
	}

	$rows = vndb_query('SHOW TABLES');

	$names = [];
	foreach ($rows as $row) {
		$names[] = array_values($row)[0];
	}


	apcu_store('vndb_get_table_names', $names, 15);
	return $names;
}

function vndb_get_table_columns($table_name, $use_cache = true)
{
	if ($use_cache) {
		$cached = apcu_fetch("vndb_get_table_columns_$table_name");

		if ($cached) {
			return $cached;
		}
	}

	$rows = vndb_query("DESCRIBE `$table_name`");

	$names = [];
	foreach ($rows as $row) {
		$type = strstr($row['Type'], '(', true);
		$mark = 's';
		switch ($type) {
			case 'int':
			case 'tinyint':
			case 'smallint':
			case 'mediumint':
			case 'bigint':
				$mark = 'i';
				break;
			case 'float':
			case 'double':
			case 'decimal':
				$mark = 'd';
				break;
			case 'blob':
				$mark = 'b';
				break;
		}
		$names[$row['Field']] = $mark;
	}

	apcu_store("vndb_get_table_columns_$table_name", $names, 15);

	return $names;
}

function vndb_query_extract($table_name, $model, $strict_model_columns = false)
{
	$table_types = vndb_get_table_columns($table_name);

	$columns = [];	 // ['`id`', '`name`']
	$marks = [];	// // ['?', '?']		
	$types = [];	// ['`i`', '`s`']
	$values = [];	// [1, 'z']

	if ($strict_model_columns) {
		foreach ($model as $key => $value) {
			if (isset($value)) {
				if (isset($table_types[$key]) == false) {
					throw new Error("vndb_query_extract: table[$table_name] doesn't have column[$key]");
				}

				$columns[] = "`$key`";
				$marks[] = "?";
				$types[] = $table_types[$key];
				$values[] = $value;
			}
		}
	} else {
		foreach ($table_types as $column => $type) {
			if (isset($model[$column])) {
				if (is_array($model[$column])) {
					throw new Error("Value input can't be array `$table_name.$column` ");
				}

				$columns[] = "`$column`";
				$marks[] = "?";
				$types[] = $type;
				$values[] = $model[$column];
			}
		}
	}


	return [
		'columns' => $columns,
		'marks' => $marks,
		'types' => $types,
		'values' => $values
	];
}

function vndb_model_insert($table_name, $model)
{
	$input = vndb_query_extract($table_name, $model);

	$columns = join(',', $input['columns']);
	$marks = join(',', $input['marks']);
	$types = join('', $input['types']);
	$values = $input['values'];

	if (empty($values)) {
		return null;
	}

	$query = "INSERT INTO `$table_name` ($columns) VALUES ($marks);";

	vndb_exec($query, $types, $values);

	return vndb_connection()->insert_id;
}

function vndb_model_update_one($table_name, $filter, $model)
{
	$input_filter = vndb_query_extract($table_name, $filter, true);

	$input = vndb_query_extract($table_name, $model);


	if (empty($input_filter['columns'])) {
		throw new Error("vndb_model_update: missting filter ");
	}

	if (empty($input['values'])) {
		return 0;
	}

	$set = [];
	foreach ($input['columns'] as $column) {
		$set[] = "$column=?";
	}

	$query = "UPDATE `$table_name` SET " . join(',', $set);

	// $has_filter = !empty($input_filter['values']);

	$set = [];
	foreach ($input_filter['columns'] as $column) {
		$set[] = "$column=?";
	}

	// WEHRE id=?, values=?
	$query .= " WHERE " . join(' AND ', $set);

	$types = join('', array_merge($input['types'], $input_filter['types']));
	$values = array_merge($input['values'], $input_filter['values']);

	$query .= ' LIMIT 1';

	return vndb_exec($query, $types, $values);

	// return vndb_connection()->affected_rows;
}

function vndb_model_find($table_name, $filter = [], $limit = 10, $offset = 0)
{
	$input_filter = vndb_query_extract($table_name, $filter, true);

	$query = "SELECT * FROM $table_name ";

	if (!empty($input_filter['columns'])) {
		$set = [];
		foreach ($input_filter['columns'] as $column) {
			$set[] = "$column=?";
		}

		$query .= " WHERE " . join(' AND ', $set);
	}

	$query .= " LIMIT $limit OFFSET $offset";

	$types = join('', $input_filter['types']);
	$values = $input_filter['values'];

	return vndb_query($query, $types, $values);
}

function vndb_model_find_one($table_name, $filter = [])
{
	$result = vndb_model_find($table_name, $filter, 1, 0);

	if (sizeof($result) > 0) {
		return $result[0];
	}

	return null;
}

// vndb_init("localhost", "vnrootco_demo", "vnrootC@7JcWZx-Y!VX7*$@XkHQ", "vnrootco_demo");

// $result = vndb_model_find('user', [
// 		'uid' => [10],
// 		'name' => 'sdf'
// 	]);
// echo json_encode($result);
