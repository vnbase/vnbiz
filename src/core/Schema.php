<?php

namespace VnBiz;

use Error, R;

class Schema
{
	public $model_name;

	public $schema = [];

	public $has_tags = false; // done

	public $has_comments = false; //done

	public $has_history = false; //done

	public $has_reviews = false;    //TODO

	public $has_trash = false;  // done

	public $has_usermarks = [];  // done

	public $back_refs = [];

	public $text_search = false;
	public $ui_meta = [];

	public function __construct($model_name)
	{
		$this->model_name = $model_name;
	}

	public function model_name()
	{
		return $this->model_name;
	}

	public function add_field($field_name, $type)
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
	}

	public function set_field($field_name, $desc)
	{

		if (isset($this->schema[$field_name])) {
			$this->schema[$field_name] = array_merge($this->schema[$field_name], $desc);
			return;
		}

		$this->schema[$field_name] = $desc;
	}

	public function get_fields_by_type($type)
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

	public function get_field_names()
	{
		return array_keys($this->schema);
	}

	public function crop(&$model)
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
