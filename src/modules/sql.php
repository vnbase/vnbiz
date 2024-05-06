<?php



function vnbiz_sql_gen_create_table($table_name)
{
	return "
		create table if not exists  `$table_name` (
			id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
		) ENGINE INNODB COLLATE \"utf8mb4_unicode_ci\" ;
	";
}


function vnbiz_sql_gen_column($model_name, $field_name, $field_def)
{
	$sql_type = '';

	switch ($field_def['type']) {
		case 'model_name':
			$sql_type = 'VARCHAR(20)';
			break;
		case 'model_id':
		case 'ref':
		case 'file':
		case 'image':
			$sql_type = 'INT UNSIGNED';
			break;
		case 'bool':
			$sql_type = 'BOOL';
			break;
		case 'date':
			$sql_type = 'DATE';
			break;
		case 'datetime':
			$sql_type = 'DATETIME(3)';
			break;
		case 'int':
			$sql_type = 'BIGINT';
			break;
		case 'uint':
			$sql_type = 'BIGINT UNSIGNED';
			break;
		case 'password':
		case 'string':
		case 'slug':
			$sql_type = 'VARCHAR(255)';
			break;
		case 'text':
			$sql_type = 'LONGTEXT';
			break;
		case 'json':
			$sql_type = 'LONGTEXT';
			break;
		case 'float':
			$sql_type = 'FLOAT';
			break;
		case 'enum':
		case 'status':
			$options	= array_map(function ($item) {
				return '"' . $item . '"';
			}, $field_def['options']);
			$sql_type = 'ENUM(' . join(',', $options) . ')';
			break;
		default:
			throw new \Error("Missing sql type for " . $field_def['type']);
	}

	return "
		ALTER TABLE `$model_name` CHANGE COLUMN IF EXISTS `$field_name` `$field_name` $sql_type ;
		ALTER TABLE `$model_name` ADD COLUMN IF NOT EXISTS `$field_name` $sql_type ;
	";
}

function vnbiz_sql_generate()
{
	$models = vnbiz()->models();
	$sql = '';

	foreach ($models as $model) {
		$schema = $model->schema();
		$model_name = $schema->model_name;
		$sql .= vnbiz_sql_gen_create_table($model_name);
		// $fields = $model->schema;
		foreach ($schema->schema as $field_name => $field_def) {
			if ($field_name !== 'id') {
				$sql .= vnbiz_sql_gen_column($model_name, $field_name, $field_def);
			}
		}
	}

	$c = [
		'sql' => ''
	];
	vnbiz_do_action('sql_gen_index', $c);

	$sql .= $c['sql'];

	return $sql;
}

function vnbiz_sql_alter_tables()
{
	$sql = vnbiz_sql_generate();
	return R::exec($sql);
}
