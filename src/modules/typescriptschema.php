<?php


function vnbiz_echo_typescript_map_type($type) {
	$types = [
		'string' => 'string',
		'int' => 'number',
		'uint' => 'number',
		'image' => 'any',
		'model_id' => 'number',
		'slug' => 'string',
		'password' => 'string',
		'text' => 'string',
		'model_name' => 'string',
		'ref' => 'string',
		'enum' => 'string',
		'status' => 'string',
		'bool' => 'boolean',
		'file' => 'any',
		'json' => 'any'
	];
	$result = $types[$type];
	if ($result) {
		return $result;
	}
	return '???' . $type;
}

function vnbiz_echo_typescript_schema() {
    $models = vnbiz()->models();
    $jsonmodels = [];
    foreach ($models as $model_name=>$model) {
    	$fields = $model->schema()->schema;
    	
        echo "export interface $model_name {\n";
    	
    	foreach ($fields as $field_name=>$field) {
			if ($field['type'] === 'enum' || $field['type'] === 'status') {
				$properties[$field_name]['enum'] = $field['options'];
			}
			if ($field['type'] === 'ref') {
                echo "\t$field_name ?: " . vnbiz_echo_typescript_map_type($field['type']);
                echo "\n";
                echo "\t'@$field_name' ?: " . $field['model_name'];
			} else if ($field['type'] === 'enum' || $field['type'] === 'status') {
                $values = [""];
                array_push($values, ...$field['options']);
                $values = array_map( function ($val) {
                    return "'" . $val . "'";
                } , $values);
                echo "\t$field_name ?: " . join('|', $values);
			} else {
                echo "\t$field_name ?: " . vnbiz_echo_typescript_map_type($field['type']);
            }
            echo "\n";
    	}
        
        echo "};\n";
    }
    
}