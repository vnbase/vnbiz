<?php

namespace VnBiz;

use Error, R;

class Schema
{
	public $model_name;

	public $schema = [];

	public bool $has_tags = false;

	public bool $has_comments = false; 

	public bool $has_history = false; 

	public bool $has_reviews = false; 

	public bool $has_trash = false; 

	public $has_usermarks = []; 

	public $back_refs = [];

	public $text_search = false;

	public $ui_meta = [];

	public function __construct($model_name)
	{
		$this->model_name = $model_name;
	}

	public function model_name(): string
	{
		return $this->model_name;
	}

	public function add_field($field_name, $type, $default_value = null)
	{
		if (isset($this->schema[$field_name])) {
			throw new Error("field $field_name already existed");
		}


		if (!isset($type) || !$type) {
			$model_name = $this->model_name;
			throw new VnbizError("Missing model type $model_name.$field_name");
		}


		$this->schema[$field_name] = [
			'type' => $type
		];
		if ($default_value !== null) {
			$this->schema[$field_name]['default'] = $default_value;

		}
	}

	public function set_field($field_name, $desc): void
	{

		if (isset($this->schema[$field_name])) {
			$this->schema[$field_name] = array_merge($this->schema[$field_name], $desc);
			return;
		}

		$this->schema[$field_name] = $desc;
	}

	public function get_fields_by_type($type): Array
	{
		$result = [];
		// echo json_encode($this->schema);
		foreach ($this->schema as $field_name => $field_def) {
			if ($field_def['type'] == $type) {
				$result[$field_name] = $field_def;
			}
		}
		return $result;
	}

	public function get_field_names(): Array
	{
		return array_keys($this->schema);
	}

	public function crop(&$model): Array
	{
		$new_model = [];

		foreach ($this->get_field_names() as $field_name) {
			if (isset($model[$field_name])) {
				$new_model[$field_name] = $model[$field_name];
			}
		}

		return $new_model;
	}
}
