<?php


function vnbiz_echo_jsonschema_map_type($type) {
	$types = [
		'string' => 'string',
		'int' => 'integer',
		'uint' => 'integer',
		'image' => 'object',
		'model_id' => 'integer',
		'slug' => 'string',
		'password' => 'string',
		'text' => 'string',
		'model_name' => 'string',
		'ref' => 'string',
		'enum' => 'string',
		'status' => 'string',
		'bool' => 'boolean',
		'file' => 'object',
		'json' => 'object'
	];
	$result = $types[$type];
	if ($result) {
		return $result;
	}
	return '???' . $type;
}

function vnbiz_echo_jsonschema() {
    $models = vnbiz()->models();
    $jsonmodels = [];
    foreach ($models as $model_name=>$model) {
    	$properties = [];
    	$fields = $model->schema()->schema;
    	
    	
    	foreach ($fields as $field_name=>$field) {
    		$properties[$field_name] = [
    			'type' => vnbiz_echo_jsonschema_map_type($field['type'])
			];
			if ($field['type'] === 'enum' || $field['type'] === 'status') {
				$properties[$field_name]['enum'] = $field['options'];
			}
			if ($field['type'] === 'ref') {
				$properties['@' . $field_name] = [
					'$ref' => '#' . $field['model_name']
				];
			}
    	}
    	
    	$jsonmodel = $jsonmodels[$model_name] = [
    		'id' => '#' . $model_name,
    		"type" => "object",
    		'properties' => $properties
		];
    }
    
    
    $json = [
    	'$schema' => "https://json-schema.org/draft/2020-12/schema",
    	"id" => "#root",
    	'type' => 'object',
    	"properties" => $jsonmodels
	];
	header('Content-Type: application/json; charset=utf-8');
    echo json_encode($json);
}