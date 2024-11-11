<?php

namespace VnBiz;

use Error;
use VnBiz\VnBizError;
use R;

class Model
{
	use \Model_event;
	use \vnbiz_trait_datascope;
	use \vnbiz_trait_editing_by;
	use \vnbiz_trait_s3_file;
	use \Model_permission;

	private $schema;

	public function __construct($model_name)
	{
		$this->schema = new Schema($model_name);

		if (isset($GLOBALS['VNBIZ_NAMESPACES'])) {
			if ($model_name !== 'namespace') {
				$this->has_ns();
			}
		}
		// $this->crop();
		// $this->has_trash();
		$this->time_at();
		$this->id();
	}

	private function has_ns()
	{
		$this->schema->add_field('ns', 'namespace', 0);
		$this->no_update('ns');
		$this->web_secure_id('ns');

		$this->db_before_create(function (&$context) {
			$context['model'] = $context['model'] ?? [];
			$context['model']['ns'] = vnbiz_namespace_id();
		});
		$this->db_before_update(function (&$context) {
			if (!isset($context['filter'])) {
				$context['filter'] = [];
			}
			$context['filter']['ns'] = vnbiz_namespace_id();
		});
		$this->db_before_find(function (&$context) {
			if (!isset($context['filter'])) {
				$context['filter'] = [];
			}
			$context['filter']['ns'] = vnbiz_namespace_id();
		});
		$this->db_before_count(function (&$context) {
			if (!isset($context['filter'])) {
				$context['filter'] = [];
			}
			$context['filter']['ns'] = vnbiz_namespace_id();
		});
		$this->db_before_delete(function (&$context) {
			if (!isset($context['filter'])) {
				$context['filter'] = [];
			}
			$context['filter']['ns'] = vnbiz_namespace_id();
		});
	}

	public function schema()
	{
		return $this->schema;
	}

	public function get_schema_details()
	{
		return $this->schema->schema;
	}

	public function get_model_field_names()
	{
		return array_keys($this->schema->schema);
	}

	// private function crop() {
	// 	$model_name = $this->schema->model_name;
	// 	$func_crop = function (&$context) {
	// 		$new_model = $context['model'];
	// 		$context['model'] = [];

	// 		foreach($this->get_model_field_names() as $field_name) {
	// 			if (isset($new_model[$field_name])) {
	// 				$context['model'][$field_name] = $new_model[$field_name];
	// 			}
	// 		}
	// 	};

	// 	$this->db_before_create($func_crop);
	// 	$this->db_before_update($func_crop);

	// 	return $this;
	// }

	public function ui($meta)
	{
		$this->schema()->ui_meta = $meta;
		return $this;
	}

	private function web_secure_id($field_name)
	{
		$encrypt_id = function (&$context) use ($field_name) {
			if (isset($context['filter']) && isset($context['filter'][$field_name]) && $context['filter'][$field_name]) {
				if (is_array($context['filter'][$field_name])) {
					$context['filter'][$field_name] = vnbiz_encrypt_ids($context['filter'][$field_name]);
				} else {
					$context['filter'][$field_name] = vnbiz_encrypt_id($context['filter'][$field_name]);
				}
			}
			if (isset($context['model']) && isset($context['model'][$field_name]) && $context['model'][$field_name]) {
				$context['model'][$field_name] = vnbiz_encrypt_id($context['model'][$field_name]);
			}
			if (isset($context['old_model']) && isset($context['old_model'][$field_name]) && $context['old_model'][$field_name]) {
				$context['old_model'][$field_name] = vnbiz_encrypt_id($context['old_model'][$field_name]);
			}
			if (isset($context['meta']) && isset($context['meta'][$field_name]) && $context['meta'][$field_name]) {
				$context['meta'][$field_name] = vnbiz_encrypt_id($context['meta'][$field_name]);
			}
			if (isset($context['models'])) {
				foreach ($context['models'] as &$model) {
					if (isset($model[$field_name]) && $model[$field_name]) {
						$model[$field_name] = vnbiz_encrypt_id($model[$field_name]);
					}
				}
			}
		};
		$decrypt_id = function (&$context) use ($field_name) {
			if (isset($context['filter']) && isset($context['filter'][$field_name]) && $context['filter'][$field_name]) {
				if (is_array($context['filter'][$field_name])) {
					if (is_array($context['filter'][$field_name])) {
						$arr = $context['filter'][$field_name];
						$new_arr = [];
						foreach ($arr as $key => $value) { // $gt $lt $e
							$new_arr[$key] = vnbiz_decrypt_id($value);
						}
						$context['filter'][$field_name] = $new_arr;
					} else {
						$context['filter'][$field_name] = vnbiz_decrypt_ids($context['filter'][$field_name]);
					}
				} else {
					$context['filter'][$field_name] = vnbiz_decrypt_id($context['filter'][$field_name]);
				}
			}
			if (isset($context['model']) && isset($context['model'][$field_name]) && $context['model'][$field_name]) {
				$context['model'][$field_name] = vnbiz_decrypt_id($context['model'][$field_name]);
			}
			if (isset($context['old_model']) && isset($context['old_model'][$field_name]) && $context['old_model'][$field_name]) {
				$context['old_model'][$field_name] = vnbiz_decrypt_id($context['old_model'][$field_name]);
			}
			if (isset($context['meta']) && isset($context['meta'][$field_name]) && $context['meta'][$field_name]) {
				$context['meta'][$field_name] = vnbiz_decrypt_id($context['meta'][$field_name]);
			}
			if (isset($context['models'])) {
				foreach ($context['models'] as &$model) {
					if (isset($model[$field_name]) && $model[$field_name]) {
						$model[$field_name] = vnbiz_decrypt_id($model[$field_name]);
					}
				}
			}
		};

		$this->web_before_create($decrypt_id);
		$this->web_before_update($decrypt_id);
		$this->web_before_delete($decrypt_id);
		$this->web_before_find($decrypt_id);

		$this->web_after_create($encrypt_id);
		$this->web_after_update($encrypt_id);
		$this->web_after_delete($encrypt_id);
		$this->web_after_find($encrypt_id);

		return $this;
	}

	private function id()
	{
		$this->model_id('id');
		$this->no_update('id');
		return $this;
	}

	public function web_readonly()
	{
		$field_names = func_get_args();

		foreach ($field_names as $field_name) {
			if (!isset($this->schema()->schema[$field_name])) {
				throw new VnBizError("$field_name is not defined");
			}
			$this->schema()->schema[$field_name]['meta'] = [
				'readonly' => true
			];
		}
		$remove_web_readonly_fields = function (&$context) use ($field_names) {
			foreach ($field_names as $field_name) {
				if (isset($context['model']) && isset($context['model'][$field_name])) {
					unset($context['model'][$field_name]);
				}
			}
		};
		$this->web_before_create($remove_web_readonly_fields);
		$this->web_before_update($remove_web_readonly_fields);
	}

	public function has_v()
	{
		$this->uint("v");
		$this->default(['v' => 1]);
		$this->web_readonly('v');

		$this->db_before_create(function (&$context) {
			$context['model']['v'] = 1;
		});

		$this->db_before_update(function (&$context) {
			if (isset($context['filter']) && isset($context['filter']['v'])) {
				if ($context['old_model']['v'] != $context['filter']['v']) {
					throw new VnBizError("Current value is " . $context['old_model']['v'] . '. But ' . $context['filter']['v'] . ' is provided.', 'invalid_v');
				}
			}
			$context['model']['v'] = $context['old_model']['v'] + 1;
		});

		return $this;
	}

	public function model_name()
	{
		$field_names = func_get_args();

		foreach ($field_names as $field_name) {
			vnbiz_assure_valid_name($field_name);

			$this->schema->add_field($field_name, 'model_name');
		}

		return $this;
	}

	public function model_id()
	{
		$field_names = func_get_args();

		foreach ($field_names as $field_name) {
			vnbiz_assure_valid_name($field_name);

			$this->schema->add_field($field_name, 'model_id');

			$this->web_secure_id($field_name);
		}

		return $this;
	}

	private function time_at()
	{
		$this->datetime('created_at', 'updated_at');
		$this->web_readonly('created_at', 'updated_at');

		$model_name = $this->schema->model_name;
		$func_created_at = function (&$context) {
			$model = &$context['model'];
			// $model['created_at'] = date("Y-m-d H:i:s");
			$model['created_at'] = floor(microtime(true) * 1000);
			unset($model['updated_at']);
		};

		$this->db_before_create($func_created_at);

		$func_updated_at = function (&$context) {
			$model = &$context['model'];
			// $model['updated_at'] = date("Y-m-d H:i:s");
			$model['updated_at'] = floor(microtime(true) * 1000);
			unset($model['created_at']);
		};
		$this->db_before_update($func_updated_at);

		return $this;
	}

	public function default($values)
	{
		$func_set_default_values = function (&$context) use ($values) {
			$model = &$context['model'];
			foreach ($values as $key => $value) {
				if (!isset($model[$key])) {
					$model[$key] = $value;
				}
			}
		};

		$this->db_before_create($func_set_default_values);

		return $this;
	}

	public function text_search()
	{
		$field_names = func_get_args();
		$model_name = $this->schema->model_name;

		if ($this->schema()->text_search) {
			throw new VnBizError('Text search already defined');
		}
		$this->schema()->text_search = $field_names;

		vnbiz_add_action('sql_gen_index', function (&$context) use ($model_name, $field_names) {
			isset($context['sql']) ?: $context['sql'] = '';

			$index_name = join('_', $field_names);
			$index_name = substr($index_name, 0, 30) . '_' . md5($index_name);

			$fields = join(',', $field_names);
			if (!vnbiz_sql_table_index_exists($model_name, $index_name)) {
				$context['sql'] .= "
					CREATE FULLTEXT INDEX $index_name ON `$model_name` ($fields);
				";
			}
		});

		return $this;
	}

	public function has_history($remove_on_delete = true)
	{
		$model_name = $this->schema->model_name;

		$this->schema()->has_history = true;

		$this->db_after_update(function (&$context) use ($model_name) {
			$model = &$context['old_model'];
			$c = [
				'model_name' => 'history',
				'model' => [
					'model_id' => $model['id'],
					'model_name' => $model_name,
					'model_json' => json_encode($model)
				],
				'in_trans' => true
			];
			vnbiz_do_action('model_create', $c);
		});

		if ($remove_on_delete) {
			$this->db_after_delete(function (&$context) use ($model_name) {
				$model = &$context['old_model'];
				R::exec('DELETE FROM `history` WHERE model_name=? AND model_id=?', [$model_name, $model['id']]);
			});
		}

		return $this;
	}

	public function has_trash()
	{
		$this->schema()->has_trash = true;
		$this->bool('is_trash');

		$func_set_default_is_trash = function (&$context) {
			if (!isset($context['model']['is_trash'])) {
				$context['model']['is_trash'] = false;
			}
		};

		$this->db_before_create($func_set_default_is_trash);

		$func_filter_update = function (&$context) {
			$meta = $context['meta'] ?? [];
			$include_trash = $meta['include_trash'] ?? false;
			if ($include_trash) {
				return;
			}

			if (!isset($context['filter'])) {
				$context['filter'] = [];
			}
			$context['filter']['is_trash'] = false;
		};

		$this->db_before_update($func_filter_update);
		$this->db_before_find($func_filter_update);

		return $this;
	}

	public function has_reviews()
	{
		// $model_name = $this->schema->model_name;
		$this->schema()->has_reviews = true;

		$this->int('review_count', 'review_count_1', 'review_count_2', 'review_count_3', 'review_count_4', 'review_count_5')
			->no_update('review_count', 'review_count_1', 'review_count_2', 'review_count_3', 'review_count_4', 'review_count_5');

		$this->float('review_rate')
			->no_update('review_rate');

		$this->db_before_create(function (&$context) {
			$context['model']['review_count'] = 0;
			$context['model']['review_count_1'] = 0;
			$context['model']['review_count_2'] = 0;
			$context['model']['review_count_3'] = 0;
			$context['model']['review_count_4'] = 0;
			$context['model']['review_count_5'] = 0;
			$context['model']['review_rate'] = 0;
		});

		$this->index('review_index', ['review_rate', 'review_count']);

		return $this;
	}


	public function has_tags()
	{
		$this->schema()->has_tags = true;
		$model_name = $this->schema->model_name;
		$func_set_tags = function (&$context) use ($model_name) {
			$old_model = vnbiz_get_var($context['old_model'], []);
			$new_model = vnbiz_get_var($context['model'], []);
			$model_id = vnbiz_get_var($new_model['id'], vnbiz_get_var($old_model['id'], null));

			$new_tags = [];

			$input_tags = vnbiz_get_var($new_model['@tags'], []);
			if (is_string($input_tags)) {
				$input_tags = explode(',', $input_tags);
			}

			foreach ($input_tags as $tag) {
				if (!is_string($tag)) {
					throw new VnbizError('Tag must be string');
				}
				$tag = trim($tag);
				if (strlen($tag) > 0) {
					$new_tags[] = strtolower($tag);
				}
			}

			$old_tags = vnbiz_model_get_tags($model_name, $model_id);
			$old_tags = array_diff($old_tags, $new_tags);

			vnbiz_model_remove_tags($model_name, $model_id, $old_tags);
			vnbiz_model_set_tags($model_name, $model_id, $new_tags);
		};

		$this->db_after_update($func_set_tags);
		$this->db_after_create($func_set_tags);

		$func_get_model_tags = function (&$context) use ($model_name) {
			$model_id = $context['model']['id'];

			$context['model']['@tags'] = vnbiz_model_get_tags($model_name, $model_id);
		};


		$this->db_after_fetch($func_get_model_tags);


		$func_delete_model_tags = function (&$context) use ($model_name) {
			$old_model = vnbiz_get_var($context['old_model'], []);
			$model_id = $old_model['id'];

			vnbiz_model_remove_all_tags($model_name, $model_id);
		};


		$this->db_after_delete($func_delete_model_tags);

		$before_db_before_exec = function (&$context) use ($model_name) {
			$tags = [];
			if (isset($context['filter']) && isset($context['filter']['@tags'])) {
				$tags = $context['filter']['@tags'];
			}
			if (sizeof($tags) > 0) {
				$sql_query_conditions = &$context['sql_query_conditions'];
				$sql_query_params = &$context['sql_query_params'];
				$sql_query_conditions[] = "(id IN (SELECT mt.model_id FROM modeltag mt INNER JOIN tag t ON t.id=mt.tag_id AND mt.model_name=? AND t.name IN (" . R::genSlots($tags) . ") GROUP BY mt.model_id HAVING COUNT(t.id)=? ))";
				$sql_query_params[] = $model_name;
				array_push($sql_query_params, ...$tags);
				$sql_query_params[] = sizeof($tags);
			}

			// var_dump($context);
		};

		$this->db_before_find($before_db_before_exec);
		$this->db_before_count($before_db_before_exec);

		return $this;
	}

	// public function has_tags() {
	// 	$this->schema()->has_tags = true;

	// 	$this->schema->add_field('tags', 'tags');

	// 	$this->before_create();
	// 	$this->db_before_update();
	// 	$this->db_after_delete();
	// }

	public function has_comments($comment_enable = true /*default*/)
	{
		$model_name = $this->schema->model_name;
		$this->schema()->has_comments = true;

		$this->bool('comment_enable');

		$this->back_ref_count('number_of_comments', 'comment', 'model_id', [
			'model_name' => $model_name
		]);

		$this->db_before_create(function (&$context) use ($comment_enable) {
			if (isset($context) && isset($context['model'])) {
				if (!isset($context['model']['comment_enable'])) {
					$context['model']['comment_enable'] = $comment_enable;
				}
			}
		});

		return $this;
	}

	public function has_usermarks()
	{
		$model_name = $this->schema->model_name;

		$mark_types = func_get_args();

		// $this->schema->mark_types = $mark_types;

		$default = [];

		foreach ($mark_types as $mark_type) {
			vnbiz_assure_valid_name($mark_type);

			$this->int('number_of_' . $mark_type);
			$default['number_of_' . $mark_type] = 0;
		}
		$this->schema()->has_usermarks = $mark_types;
		$this->default($default);

		vnbiz_add_action("db_after_create_usermark", function (&$context) use ($model_name, $mark_types) {
			$ref_model_name = $context['model']['model_name'];

			if ($ref_model_name !== $model_name) {
				return;
			}

			$mark_type = $context['model']['mark_type'];

			if (!in_array($mark_type, $mark_types)) {
				throw new VnbizError("Invalid mark type $mark_type", 'invalid_model');
			}


			if ($ref_model_name == $model_name) {
				$count = vnbiz_model_count('usermark', [
					'model_name' => $model_name,
					'mark_type' => $mark_type
				]);

				vnbiz_model_update($model_name, [
					'id' => $context['model']['model_id']
				], [
					"number_of_$mark_type" => $count
				], [
					'skip_db_actions' => true
				]);
			}
		});


		vnbiz_add_action("db_after_delete_usermark", function (&$context) use ($model_name, $mark_types) {
			$ref_model_name = $context['old_model']['model_name'];
			$mark_type = $context['old_model']['mark_type'];

			if ($ref_model_name !== $model_name) {
				return;
			}

			if (!in_array($mark_type, $mark_types)) {
				throw new VnbizError("Invalid mark type $mark_type", 'invalid_model');
			}

			if ($ref_model_name == $model_name) {
				$count = vnbiz_model_count('usermark', [
					'model_name' => $model_name,
					'mark_type' => $mark_type
				]);

				vnbiz_model_update($model_name, [
					'id' => $context['old_model']['model_id']
				], [
					"number_of_$mark_type" => $count
				], [
					'skip_db_actions' => true
				]);
			}
		});


		vnbiz_add_action("db_after_find_$model_name", function (&$context) use ($model_name, $mark_types) {
			if (isset($context['meta']) && isset($context['meta']['ref']) && $context['meta']['ref']) {
				$models = &$context['models'];

				$map = [];

				foreach ($models as &$model) {
					$map[$model['id']] = &$model;
				}

				//TODO: CAN BE COMBINE IN TO ONE QUERY 
				foreach ($mark_types as $mark_type) {
					$ids = array_map(function (&$model) use (&$map, $mark_type) {
						$model['@' . $mark_type . '_by_me'] = false;
						return $model['id'];
					}, $models);

					$user = vnbiz_user();
					if ($user && sizeof($ids) > 0) {
						$params = array_merge([$model_name, $user['id'], $mark_type], $ids);

						$rows = R::find('usermark', ' model_name=? AND created_by=? AND mark_type=? AND model_id IN (' . R::genSlots($ids) . ')', $params);
						$rows = R::beansToArray($rows);

						foreach ($rows as $row) {
							$model['@' . $mark_type . '_by_me'] = true;
						}
					}
				}
			}
		});

		return $this;
	}

	public function require()
	{
		$field_names = func_get_args();

		$func_validate_create = function ($context) use ($field_names) {
			$model = $context['model'];

			foreach ($field_names as $field_name) {
				if (isset($model[$field_name])) {
					if (is_string($model[$field_name]) && strlen($model[$field_name]) == 0) {
						throw new VnBizError("Field $field_name must have value", 'invalid_model', [
							$field_name => 'This field is required'
						]);
					}
					if (is_array($model[$field_name]) && sizeof($model[$field_name]) == 0) {
						throw new VnBizError("Field $field_name must have value", 'invalid_model', [
							$field_name => 'This field is required'
						]);
					}
				} else {
					throw new VnBizError("Missing field $field_name", 'invalid_model', [
						$field_name => 'This field is required'
					]);
				}
			}
		};
		$this->db_before_create($func_validate_create);

		$func_validate_update = function ($context) use ($field_names) {
			$model = $context['model'];

			foreach ($field_names as $field_name) {
				if (isset($model[$field_name])) {
					if (is_string($model[$field_name]) && strlen($model[$field_name]) == 0) {
						throw new VnBizError("Field $field_name must have value", 'invalid_model', [
							$field_name => 'This field is required'
						]);
					}
					if (is_array($model[$field_name]) && sizeof($model[$field_name]) == 0) {
						throw new VnBizError("Field $field_name must have value", 'invalid_model', [
							$field_name => 'This field is required'
						]);
					}
				}
				// else {
				// 	throw new VnBizError("Missing field $field_name", 'invalid_model');
				// }
			}
		};
		$this->db_before_update($func_validate_update);

		return $this;
	}

	public function bool()
	{
		$field_names = func_get_args();

		foreach ($field_names as $field_name) {
			vnbiz_assure_valid_name($field_name);

			$this->schema->add_field($field_name, 'bool');
		}

		$func_validate_bool = function (&$context) use ($field_names) {
			if (isset($context['model'])) {
				$model = &$context['model'];

				foreach ($field_names as $field_name) {
					if (isset($model[$field_name])) {
						$value = $model[$field_name];
						if (isset($model[$field_name]) && !is_bool($value)) {
							if (is_string($value)) {
								$model[$field_name] = $value == 'true' || $value == 'TRUE' || $value === '1';
							} else if (is_numeric($value)) {
								$model[$field_name] = $value != 0;
							} else {
								throw new VnBizError("$field_name must be bool", 'invalid_model');
							}
						}
					}
				}
			}

			if (isset($context['filter'])) {
				foreach ($field_names as $field_name) {
					if (isset($context['filter'][$field_name])) {
						$context['filter'][$field_name] = (bool)$context['filter'][$field_name];
					}
				}
			}
		};

		$this->db_before_create($func_validate_bool);
		$this->db_before_update($func_validate_bool);
		$this->db_before_delete($func_validate_bool);
		$this->web_before_create($func_validate_bool);
		$this->web_before_update($func_validate_bool);
		$this->web_before_delete($func_validate_bool);


		$func_convert_bool = function (&$context) use ($field_names) {
			if (isset($context['models'])) {
				foreach ($context['models'] as &$model) {
					foreach ($field_names as $field_name) {
						if (isset($model[$field_name])) {
							$model[$field_name] = (bool)$model[$field_name];
						}
					}
				}
			}
			if (isset($context['model'])) {
				foreach ($field_names as $field_name) {
					if (isset($context['model'][$field_name])) {
						$context['model'][$field_name] = (bool)$context['model'][$field_name];
					}
				}
			}
			if (isset($context['old_model'])) {
				foreach ($field_names as $field_name) {
					if (isset($context['old_model'][$field_name])) {
						$context['old_model'][$field_name] = (bool)$context['old_model'][$field_name];
					}
				}
			}
		};
		$this->db_after_find($func_convert_bool);
		$this->db_after_update($func_convert_bool);
		$this->db_after_delete($func_convert_bool);

		return $this;
	}

	public function date()
	{
		$field_names = func_get_args();

		foreach ($field_names as $field_name) {
			vnbiz_assure_valid_name($field_name);

			$this->schema->add_field($field_name, 'date');
		}

		$func_validate_strings = function (&$context) use ($field_names) {
			$model = &$context['model'];

			foreach ($field_names as $field_name) {
				if (isset($model[$field_name])) {
					$value = $model[$field_name];
					if (!is_string($value)) {
						throw new VnBizError("$field_name must be string", 'invalid_model');
					}
					// var_dump($value);
					$model[$field_name] = trim($value);
				}
			}
		};

		$this->db_before_create($func_validate_strings);
		$this->db_before_update($func_validate_strings);

		return $this;
	}

	public function string()
	{
		$field_names = func_get_args();

		foreach ($field_names as $field_name) {
			vnbiz_assure_valid_name($field_name);

			$this->schema->add_field($field_name, 'string');
		}

		$func_validate_strings = function (&$context) use ($field_names) {
			$model = &$context['model'];

			foreach ($field_names as $field_name) {
				if (isset($model[$field_name])) {
					$value = $model[$field_name];
					if (!is_string($value)) {
						throw new VnBizError("$field_name must be string", 'invalid_model');
					}
					// var_dump($value);
					$model[$field_name] = trim($value);
				}
			}
		};

		$this->db_before_create($func_validate_strings);
		$this->db_before_update($func_validate_strings);

		return $this;
	}

	public function email()
	{
		$field_names = func_get_args();

		foreach ($field_names as $field_name) {
			vnbiz_assure_valid_name($field_name);

			$this->schema->add_field($field_name, 'email');
		}

		$func_validate_strings = function (&$context) use ($field_names) {
			$model = &$context['model'];

			foreach ($field_names as $field_name) {
				if (isset($model[$field_name])) {
					$value = $model[$field_name];
					if (!is_string($value)) {
						throw new VnBizError("$field_name must be string", 'invalid_model', [$field_name => 'Must be a string.']);
					}
					// var_dump($value);
					if ($value && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
						throw new VnBizError("$field_name must be email", 'invalid_model', [$field_name => 'Invalid email format.']);
					}
				}
			}
		};

		$this->db_before_create($func_validate_strings);
		$this->db_before_update($func_validate_strings);

		return $this;
	}

	public function slug()
	{
		$field_names = func_get_args();

		foreach ($field_names as $field_name) {
			vnbiz_assure_valid_name($field_name);

			$this->schema->add_field($field_name, 'slug');
		}

		$func_validate_strings = function (&$context) use ($field_names) {
			$model = &$context['model'];

			foreach ($field_names as $field_name) {
				if (isset($model[$field_name])) {
					$value = $model[$field_name];
					if (preg_match('/^[a-z][a-z0-9_-]*$/', $value) == false) {
						throw new VnBizError("$field_name must be slug", 'invalid_model');
					}
					// var_dump($value);
					// $model[$field_name] = trim($value);
				}
			}
		};

		$this->db_before_create($func_validate_strings);
		$this->db_before_update($func_validate_strings);

		return $this;
	}

	public function text()
	{
		$field_names = func_get_args();

		foreach ($field_names as $field_name) {
			vnbiz_assure_valid_name($field_name);

			$this->schema->add_field($field_name, 'text');
		}

		$func_validate_text = function (&$context) use ($field_names) {
			$model = &$context['model'];

			foreach ($field_names as $field_name) {
				if (isset($model[$field_name])) {
					$value = vnbiz_get_key($model, $field_name);
					if (isset($model[$field_name]) && $value !== null && !is_string($value)) {
						throw new VnBizError("$field_name must be string", 'invalid_model');
					}
				}
			}
		};

		$this->db_before_create($func_validate_text);
		$this->db_before_update($func_validate_text);

		return $this;
	}

	public function json()
	{
		$field_names = func_get_args();

		foreach ($field_names as $field_name) {
			vnbiz_assure_valid_name($field_name);

			$this->schema->add_field($field_name, 'json');
		}

		$func_validate_json = function (&$context) use ($field_names) {
			$model = &$context['model'];

			foreach ($field_names as $field_name) {
				if (isset($model[$field_name])) {
					$value = vnbiz_get_key($model, $field_name);
					if ($value == null) {
						unset($model[$field_name]);
						continue;
					}
					if (is_string($value)) {
						$arr = json_decode($value, true);
						if ($arr === false) {
							throw new VnBizError("$field_name must be json", 'invalid_model');
						}
					} else if (is_array($value) || is_object($value)) {
						$model[$field_name] = json_encode($value);
					} else {
						throw new VnBizError("$field_name must be json", 'invalid_model');
					}
				}
			}
		};

		$this->db_before_create($func_validate_json);
		$this->db_before_update($func_validate_json);

		// $this->web_after_find(function (&$context) use ($field_names) {
		// 	$model = &$context['model'];

		// 	foreach ($field_names as $field_name) {
		// 		if (isset($model[$field_name])) {
		// 			$value = vnbiz_get_key($model, $field_name);
		// 			if (is_string($value)) {
		// 				//validate json, if it invalid, set to {}.
		// 				// $arr = json_decode($value, true);
		// 				// if ($arr === false) {
		// 				// 	$model[$field_name] = '{}';
		// 				// }
		// 			} else {
		// 				$model[$field_name] = json_encode($value);
		// 			}
		// 		}
		// 	}
		// });

		// // after create find, update, delete => convert json to array
		$func_convert_to_json_string = function (&$context) use ($field_names) {
			if (isset($context['models'])) {
				foreach ($context['models'] as &$model) {
					foreach ($field_names as $field_name) {
						if (isset($model[$field_name]) && is_array($model[$field_name])) {
							$model[$field_name] = json_encode($model[$field_name]);
						}
					}
				}
			}
			if (isset($context['model'])) {
				foreach ($field_names as $field_name) {
					if (isset($context['model'][$field_name]) && is_array($context['model'][$field_name])) {
						$context['model'][$field_name] = json_encode($context['model'][$field_name]);
					}
				}
			}
			if (isset($context['old_model'])) {
				foreach ($field_names as $field_name) {
					if (isset($context['old_model'][$field_name]) && is_array($context['old_model'][$field_name])) {
						$context['old_model'][$field_name] = json_encode($context['old_model'][$field_name]);
					}
				}
			}
		};

		$this->web_after_create($func_convert_to_json_string);
		$this->web_after_find($func_convert_to_json_string);
		$this->web_after_update($func_convert_to_json_string);
		$this->web_after_delete($func_convert_to_json_string);

		$this->db_after_fetch(function (&$context) use ($field_names) {
			$model = &$context['model'];

			foreach ($field_names as $field_name) {
				if (isset($model[$field_name])) {
					$value = vnbiz_get_key($model, $field_name);
					if (is_string($value)) {
						$arr = json_decode($value, true);
						if ($arr === false) {
							$model[$field_name] = [
								'vnbiz_invalid_json' =>  true,
								'original_value' => $value
							];
							// throw new VnBizError("$field_name must be json", 'invalid_model');
						} else {
							$model[$field_name] = $arr;
						}
					}
				}
			}
		});



		return $this;
	}

	public function int()
	{
		$field_names = func_get_args();

		foreach ($field_names as $field_name) {
			vnbiz_assure_valid_name($field_name);

			$this->schema->add_field($field_name, 'int');
		}

		$func_validate_int = function (&$context) use ($field_names) {
			$model = &$context['model'];

			foreach ($field_names as $field_name) {
				if (isset($model[$field_name])) {
					$value = $model[$field_name];
					if (isset($model[$field_name]) && is_string($value)) {
						$model[$field_name] = intval($value);
						$value = $model[$field_name];
					}
					if (isset($model[$field_name]) && !is_int($value)) {
						throw new VnBizError("$field_name must be int", 'invalid_model');
					}
				}
			}
		};

		$this->db_before_create($func_validate_int);
		$this->db_before_update($func_validate_int);


		$func_alter = function (&$context) use ($field_names) {
			if (isset($context['models'])) {
				$models = &$context['models'];
				foreach ($field_names as $field_name) {
					foreach ($models as &$model) {
						if (isset($model[$field_name]) && is_string($model[$field_name])) {
							$model[$field_name] = intval($model[$field_name]);
						}
					}
				}
			}
			if (isset($context['old_model'])) {
				$model = &$context['old_model'];
				foreach ($field_names as $field_name) {
					if (isset($model[$field_name]) && is_string($model[$field_name])) {
						$model[$field_name] = intval($model[$field_name]);
					}
				}
			}
			if (isset($context['model'])) {
				$model = &$context['model'];
				foreach ($field_names as $field_name) {
					if (isset($model[$field_name]) && is_string($model[$field_name])) {
						$model[$field_name] = intval($model[$field_name]);
					}
				}
			}
		};
		$this->db_after_find($func_alter);
		$this->db_after_update($func_alter);
		$this->db_after_delete($func_alter);
		return $this;
	}

	public function uint()
	{
		$field_names = func_get_args();

		foreach ($field_names as $field_name) {
			vnbiz_assure_valid_name($field_name);

			$this->schema->add_field($field_name, 'uint');
		}

		$func_validate_int = function (&$context) use ($field_names) {
			$model = &$context['model'];

			foreach ($field_names as $field_name) {
				if (isset($model[$field_name])) {
					$value = $model[$field_name];
					if (isset($model[$field_name]) && is_string($value)) {
						$model[$field_name] = intval($value);
						$value = $model[$field_name];
					}
					if (isset($model[$field_name]) && !is_int($value)) {
						throw new VnBizError("$field_name must be int", 'invalid_model');
					}
					if ($value < 0) {
						throw new VnBizError("$field_name must be uint, greater than zero", 'invalid_model');
					}
				}
			}
		};

		$this->db_before_create($func_validate_int);
		$this->db_before_update($func_validate_int);


		$func_alter = function (&$context) use ($field_names) {
			if (isset($context['models'])) {
				$models = &$context['models'];
				foreach ($field_names as $field_name) {
					foreach ($models as &$model) {
						if (isset($model[$field_name]) && is_string($model[$field_name])) {
							$model[$field_name] = intval($model[$field_name]);
						}
					}
				}
			}
			if (isset($context['old_model'])) {
				$model = &$context['old_model'];
				foreach ($field_names as $field_name) {
					if (isset($model[$field_name]) && is_string($model[$field_name])) {
						$model[$field_name] = intval($model[$field_name]);
					}
				}
			}
			if (isset($context['model'])) {
				$model = &$context['model'];
				foreach ($field_names as $field_name) {
					if (isset($model[$field_name]) && is_string($model[$field_name])) {
						$model[$field_name] = intval($model[$field_name]);
					}
				}
			}
		};
		$this->db_after_find($func_alter);
		$this->db_after_update($func_alter);
		$this->db_after_delete($func_alter);

		return $this;
	}

	public function float()
	{
		$field_names = func_get_args();

		foreach ($field_names as $field_name) {
			vnbiz_assure_valid_name($field_name);

			$this->schema->add_field($field_name, 'float');
		}

		$func_validate_float = function (&$context) use ($field_names) {
			$model = &$context['model'];

			foreach ($field_names as $field_name) {
				if (isset($model[$field_name])) {
					if (!is_numeric($model[$field_name])) {
						throw new VnBizError("$field_name must be float", 'invalid_model', [
							$field_name => 'Must be a number. but ' . $model[$field_name] . ' is provided.'
						]);
					}
					if (is_string($model[$field_name])) {
						$model[$field_name] = floatval($model[$field_name]);
					}
				}
			}
		};

		$this->db_before_create($func_validate_float);
		$this->db_before_update($func_validate_float);
		$this->web_before_create($func_validate_float);
		$this->web_before_update($func_validate_float);

		//convert to double after create, update, find, delete
		$func_alter_float = function (&$context) use ($field_names) {
			if (isset($context['models'])) {
				$models = &$context['models'];
				foreach ($models as &$model) {
					foreach ($field_names as $field_name) {
						if (isset($model[$field_name]) && is_string($model[$field_name])) {
							$model[$field_name] = floatval($model[$field_name]);
						}
					}
				}
			}

			if (isset($context['old_model'])) {
				$model = &$context['old_model'];
				foreach ($field_names as $field_name) {
					if (isset($model[$field_name]) && is_string($model[$field_name])) {
						$model[$field_name] = floatval($model[$field_name]);
					}
				}
			}

			if (isset($context['model'])) {
				$model = &$context['model'];
				foreach ($field_names as $field_name) {
					if (isset($model[$field_name]) && is_string($model[$field_name])) {
						$model[$field_name] = floatval($model[$field_name]);
					}
				}
			}
		};
		$this->db_after_find($func_alter_float);
		$this->db_after_update($func_alter_float);
		$this->db_after_delete($func_alter_float);

		return $this;
	}


	public function enum($field_name, $options, $default_value = null)
	{
		vnbiz_assure_valid_name($field_name);
		$this->schema->add_field($field_name, 'enum');
		$this->schema->set_field($field_name, [
			'options' => $options
		]);

		$func_validate_enum = function (&$context) use ($field_name, $default_value) {
			$model = &$context['model'];

			if (isset($model[$field_name])) {
				$value = $model[$field_name];

				if (!is_string($value)) {
					throw new VnBizError("$field_name must be enum<string>", 'invalid_model');
				}
			} else {
				$model[$field_name] = $default_value;
			}
		};

		$this->db_before_create($func_validate_enum);
		$this->db_before_update($func_validate_enum);

		return $this;
	}

	public function unique($index_key, $field_names)
	{
		$model_name = $this->schema->model_name;

		vnbiz_add_action('sql_gen_index', function (&$context) use ($model_name, $index_key, $field_names) {
			$sql_field_names = array_map(function ($name) {
				return "`$name`";
			}, $field_names);

			if (isset($GLOBALS['VNBIZ_NAMESPACES']) && $model_name != 'namespace') {
				$sql_field_names = '`ns`,' . join(",", $sql_field_names);
			} else {
				$sql_field_names = join(",", $sql_field_names);
			}

			isset($context['sql']) ?: $context['sql'] = '';
			if (!vnbiz_sql_table_index_exists($model_name, $index_key)) {
				$context['sql'] .= "
					CREATE UNIQUE INDEX `$index_key` ON `$model_name` ($sql_field_names);
				";
			}
		});

		return $this;
	}

	public function index($index_key, $field_names)
	{
		$model_name = $this->schema->model_name;

		vnbiz_add_action('sql_gen_index', function (&$context) use ($model_name, $index_key, $field_names) {
			$sql_field_names = array_map(function ($name) {
				return "`$name`";
			}, $field_names);

			if (isset($GLOBALS['VNBIZ_NAMESPACES']) && $model_name != 'namespace') {
				$sql_field_names = '`ns`,' . join(",", $sql_field_names);
			} else {
				$sql_field_names = join(",", $sql_field_names);
			}

			isset($context['sql']) ?: $context['sql'] = '';

			if (!vnbiz_sql_table_index_exists($model_name, $index_key)) {
				$context['sql'] .= "
					CREATE INDEX `$index_key` ON `$model_name` ($sql_field_names);
				";
			}
		});

		return $this;
	}


	public function datetime()
	{
		$field_names = func_get_args();

		foreach ($field_names as $field_name) {
			vnbiz_assure_valid_name($field_name);

			$this->schema->add_field($field_name, 'datetime');
		}

		//TODO: validate datetime

		$func_validate_datetime = function (&$context) use ($field_names) {
			$model = &$context['model'];

			foreach ($field_names as $field_name) {
				if (isset($model[$field_name])) {
					$value = $model[$field_name];
					if (!is_int($value)) {
						if (is_string($value) && preg_match("/^[0-9]*$/i", $value)) {
							$model[$field_name] = intval($value);

							// $date_time = new DateTime($value, new DateTimeZone('UTC') );
							// $model[$field_name] = $date_time->getTimestamp();
							// if (preg_match("/^[0-9]*$/i", $value)) {
							// } else {
							// 	$time = new \DateTime($value, new \DateTimeZone('UTC'));
							// 	$model[$field_name] = (int)$time->format('Uv');
							// }
							// ;	
						} else {
							throw new VnBizError("$field_name must be datetime<int>", 'invalid_model', [
								$field_name => 'Must be a datetime.'
							]);
						}
					}
				} else {
					$model[$field_name] = NULL;
				}
			}
		};

		$this->web_before_create($func_validate_datetime);
		$this->web_before_update($func_validate_datetime);
		$this->db_before_create($func_validate_datetime);
		$this->db_before_update($func_validate_datetime);

		$func_alter_datetime = function (&$context) use ($field_names) {
			if (isset($context['models'])) {
				$models = &$context['models'];
				foreach ($field_names as $field_name) {
					foreach ($models as &$model) {
						if (isset($model[$field_name]) && is_string($model[$field_name])) {
							$model[$field_name] = intval($model[$field_name]);
						}
					}
				}
			}
			if (isset($context['old_model'])) {
				$model = &$context['old_model'];
				foreach ($field_names as $field_name) {
					if (isset($model[$field_name]) && is_string($model[$field_name])) {
						$model[$field_name] = intval($model[$field_name]);
					}
				}
			}
			if (isset($context['model'])) {
				$model = &$context['model'];
				foreach ($field_names as $field_name) {
					if (isset($model[$field_name]) && is_string($model[$field_name])) {
						$model[$field_name] = intval($model[$field_name]);
					}
				}
			}
		};
		$this->db_after_find($func_alter_datetime);
		$this->db_after_update($func_alter_datetime);
		$this->db_after_delete($func_alter_datetime);

		return $this;
	}


	public function status($field_name, $status_flow, $default_value)
	{
		vnbiz_assure_valid_name($field_name);

		$this->schema->set_field($field_name, [
			'type' => 'status',
			'options' => array_keys($status_flow)
		]);

		$func_validate_status = function (&$context) use ($field_name, $default_value) {
			$model = &$context['model'];


			if (isset($model[$field_name])) {
				$value = $model[$field_name];

				if (!is_string($value)) {
					throw new VnBizError("$field_name must be status<string>", 'invalid_model');
				}
			} else {
				$model[$field_name] = $default_value;
			}
		};

		$this->db_before_create($func_validate_status);

		$func_validate_status_flow = function (&$context) use ($field_name, $status_flow) {
			$model = &$context['model'];

			if (isset($model[$field_name])) {
				$value = $model[$field_name];

				if (!is_string($value)) {
					throw new VnBizError("$field_name must be status<string>", 'invalid_model');
				}

				if (isset($context['old_model']) && isset($context['old_model'][$field_name])) {
					$old_value = $context['old_model'][$field_name];

					if ($old_value === $value) {
						unset($model[$field_name]);
					} else {
						if (!in_array($value, $status_flow[$old_value])) {
							throw new VnBizError("$field_name can't change from $old_value to $value", 'invalid_status_flow');
						}
					}
				}
			}
		};
		$this->db_before_update($func_validate_status_flow);

		return $this;
	}

	/**
	 * @param string $field_name
	 * @param string $ref_model_name
	 * @param callable $fn_permission_check returns true == has permission
	 */
	function ref($field_name, $ref_model_name, $fn_permission_check = null)
	{
		$model_name = $this->schema->model_name;

		vnbiz_assure_valid_name($field_name);

		if (!isset(vnbiz()->models()[$ref_model_name])) {
			throw new VnBizError("$ref_model_name doesn't exist", 'no_such_model');
		}

		$this->schema->set_field($field_name, [
			'type' => 'ref',
			'model_name' => $ref_model_name
		]);

		$this->schema->back_refs[$ref_model_name] = 1;

		$this->web_secure_id($field_name);

		// if (!isset($this->schema->back_refs[$ref_model_name])) {
		// 	$this->schema->back_refs[$ref_model_name]['back_ref'] = [];
		// }

		// $this->schema->back_refs[$ref_model_name]['back_ref'][$model_name] = 1;

		$this->db_before_create(function (&$context) use ($field_name, $ref_model_name) {
			if (isset($context['model']) && isset($context['model'][$field_name])) {
				$context['ref_field_name'] = $field_name;
				vnbiz_do_action("model_new_ref_$ref_model_name", $context);
			}
		});
		$this->db_before_update(function (&$context) use ($field_name, $ref_model_name) {
			if (isset($context['model']) && isset($context['model'][$field_name])) {
				//$context['model'][$field_name] != $context['old_model'][$field_name]
				if (isset($context['old_model']) && isset($context['old_model'][$field_name]) && $context['old_model'][$field_name] != $context['model'][$field_name]) {
					$context['ref_field_name'] = $field_name;
					vnbiz_do_action("model_new_ref_$ref_model_name", $context);
				}

				// $context['ref_field_name'] = $field_name;
				// vnbiz_do_action("model_new_ref_$ref_model_name", $context);
			}
		});

		// with web_before_create & web_before_update, validate if the ref is valid (user has permissions & ref model exists)
		$assure_ref_id = function (&$context) use ($field_name, $ref_model_name, $fn_permission_check) {
			if (isset($context['model']) && isset($context['model'][$field_name])) {
				$ref_id = $context['model'][$field_name];

				if ($ref_id === '') {
					unset($context['model'][$field_name]);
					return;
				}

				$model = vnbiz_model_find_one($ref_model_name, [
					'id' => $ref_id
				]);
				if ($model) {
					return;
				} else {
					throw new VnBizError("Invalid $field_name, model doesn't exist", 'invalid_model');
				}
			}
		};

		$this->db_before_create($assure_ref_id);
		$this->db_before_update($assure_ref_id);


		$check_ref_permission = function () use ($field_name, $ref_model_name, $fn_permission_check) {
			if (isset($context['model']) && isset($context['model'][$field_name])) {
				$ref_id = $context['model'][$field_name];

				if ($ref_id === '') {
					unset($context['model'][$field_name]);
					return;
				}

				if ($fn_permission_check) {
					if ($fn_permission_check($ref_id)) {
						return;
					} else {
						throw new VnBizError("Invalid $field_name, missing permissions", 'permission', [
							$field_name => 'Missing permissions.'
						], null, 403);
					}
				}

				$find_context = [
					'action' => "model_find", // when we support model count, we ultize.
					'model_name' => $ref_model_name,
					'filter' => [
						'id' => $ref_id
					]
				];

				vnbiz_do_action('web_model_find', $find_context);

				// makesure user has permission to view
				if (sizeof($find_context['models']) == 0) {
					throw new VnBizError("Invalid $field_name, model doesn't exist or missing permissions", 'invalid_model');
				}
			}
		};
		$this->web_before_create($check_ref_permission);
		$this->web_before_update($check_ref_permission);

		return $this;
	}

	function no_create()
	{
		$field_names = func_get_args();

		$func_validate = function (&$context) use ($field_names) {
			$model_name = $this->schema->model_name;
			$model = vnbiz_get_var($context['model'], []);

			if (sizeof($field_names) == 0) {
				throw new VnBizError("Model is not allow to create $model_name", 'invalid_operator');
				return;
			}

			foreach ($field_names as $field_name) {
				if (isset($model[$field_name])) {
					throw new VnBizError("Field is not allow to create $model_name.$field_name", 'invalid_operator');
				}
			}
		};

		$this->db_before_create($func_validate);

		return $this;
	}

	function no_update()
	{
		$field_names = func_get_args();

		foreach ($field_names as $field_name) {
			if (!isset($this->schema()->schema[$field_name])) {
				$this->schema()->schema[$field_name] = [];
			}
			$this->schema()->schema[$field_name]['meta'] = [
				'readonly' => true
			];
		}

		$func_validate = function (&$context) use ($field_names) {
			$model_name = $this->schema->model_name;
			$model = vnbiz_get_var($context['model'], []);

			if (sizeof($field_names) == 0) {
				throw new VnBizError("Model is not allow to update $model_name", 'invalid_operator');
				return;
			}

			foreach ($field_names as $field_name) {
				if (isset($model[$field_name])) {
					throw new VnBizError("Field is not allow to update $model_name.$field_name", 'invalid_operator');
				}
			}
		};

		$this->db_before_update($func_validate);

		return $this;
	}

	function back_ref_count($field_name, $ref_model_name, $ref_field_name, $filter = [])
	{
		$model_name = $this->schema->model_name;

		vnbiz_assure_valid_name($field_name);
		vnbiz_assure_valid_name($ref_field_name);

		vnbiz_assure_model_name_exists($model_name);

		$this->int($field_name);
		$this->no_update($field_name);

		$this->db_before_create(function (&$context) use ($field_name) {
			$context['model'][$field_name] = 0;
		});

		$increase = function (&$context) use ($field_name, $model_name, $ref_model_name, $ref_field_name, $filter) {
			$id = null;
			if (isset($context['model']) && isset($context['model'][$ref_field_name])) {
				$id = $context['model'][$ref_field_name];
			}

			if ($id !== null) {
				$count_filter = array_merge($filter, [$ref_field_name => $id]);
				$count = vnbiz_model_count($ref_model_name, $count_filter, true, 'LOCK IN SHARE MODE');
				try {
					vnbiz_model_update($model_name, [
						'id' => $id
					], [
						$field_name => $count
					], [
						'skip_db_actions' => true
					], true);
				} catch (\Exception $e) {
					trigger_error('back_ref_count error, ' . $e->getMessage(), E_USER_ERROR);
				}
			}
		};

		vnbiz_add_action('db_after_commit_create_' . $ref_model_name, $increase);

		$recount = function (&$context) use ($field_name, $model_name, $ref_model_name, $ref_field_name, $filter) {

			$id = null;
			$old_id = null;
			if (isset($context['model']) && isset($context['model'][$ref_field_name])) {
				$id = $context['model'][$ref_field_name];
			}
			if (isset($context['old_model']) && isset($context['old_model'][$ref_field_name])) {
				$old_id = $context['old_model'][$ref_field_name];
			}

			if (isset($context['model'])) {
				// on ref with: filter is empty or updated-model contains filter's fields
				$has_ref_id = $id !== null;
				$updated_model_contains_filter = vnbiz_array_has_one_of_keys($context['model'], array_keys($filter));

				//($has_ref_id && $id !== $old_id) = changed ref_id from one to another.
				if (($has_ref_id && $updated_model_contains_filter) || ($has_ref_id && $id !== $old_id)) {
					// update count
					$count_filter = array_merge($filter, [$ref_field_name => $id]);
					$count = vnbiz_model_count($ref_model_name, $count_filter, true, 'LOCK IN SHARE MODE');
					try {
						vnbiz_model_update($model_name, [
							'id' => $id
						], [
							$field_name => $count
						], [
							'skip_db_actions' => true
						], true);
					} catch (\Exception $e) {
						throw $e;
					}
				} else {
					$updated_model_contains_filter = vnbiz_array_has_one_of_keys($context['model'], array_keys($filter));
				}
			}

			// $old_model[id] == $model[id] => no need to recount (counted in update)
			// 
			if ($old_id && $old_id !== $id) {
				$count_filter = array_merge($filter, [$ref_field_name => $old_id]);
				$count = vnbiz_model_count($ref_model_name, $count_filter, true, 'LOCK IN SHARE MODE');
				try {
					vnbiz_model_update($model_name, [
						'id' => $old_id
					], [
						$field_name => $count
					], [
						'skip_db_actions' => true
					], true);
				} catch (\Exception $e) {
					trigger_error('back_ref_count error, ' . $e->getMessage(), E_USER_ERROR);
				}
			}
		};

		vnbiz_add_action('db_after_commit_update_' . $ref_model_name, $recount);
		vnbiz_add_action('db_after_commit_delete_' . $ref_model_name, $recount);

		return $this;
	}

	// function no_update() {
	// 	$model_name = $this->schema->model_name();

	// 	$func_stop_update = function () use ($model_name) {
	// 		throw new VnBizError("$model_name can't be updated", 'invalid_model');
	// 	};

	// 	$this->db_before_update($func_stop_update);

	// 	return $this;
	// }

	function no_delete()
	{
		$model_name = $this->schema->model_name();

		$func_stop_delete = function () use ($model_name) {
			throw new VnBizError("$model_name can't be deleted", 'invalid_model');
		};

		$this->db_before_delete($func_stop_delete);

		return $this;
	}

	function author()
	{

		//remove all author fields if exists. definition must be after;
		$func_set_create_author = function (&$context) {
			unset($context['model']['updated_by']);

			$user = vnbiz_user();

			if ($user) {
				$context['model']['created_by'] = $user['id'];
			}
		};
		$this->db_before_create($func_set_create_author);

		$func_set_update_author = function (&$context) {
			unset($context['model']['created_by']);

			$user = vnbiz_user();

			if ($user) {
				$context['model']['updated_by'] = $user['id'];
			}
		};

		$this->db_before_update($func_set_update_author);

		$this->ref('created_by', 'user');
		$this->ref('updated_by', 'user');

		$this->web_readonly('created_by', 'updated_by');

		$this->web_before_create(function (&$context) {
			$user = vnbiz_user();

			if ($user) {
				$context['model']['created_by'] = $user['id'];
			} else {
				unset($context['model']['created_by']);
			}
		});

		$this->web_before_update(function (&$context) {
			$user = vnbiz_user();

			if ($user) {
				$context['model']['updated_by'] = $user['id'];
			} else {
				unset($context['model']['created_by']);
			}
		});

		return $this;
	}

	function password()
	{
		$field_names = func_get_args();

		foreach ($field_names as $field_name) {
			vnbiz_assure_valid_name($field_name);

			$this->schema->add_field($field_name, 'password');
		}

		$func_hash_password = function (&$context) use ($field_names) {
			// unset($context['model']['updated_by']);

			foreach ($field_names as $field_name) {
				if (isset($context['model'][$field_name])) {
					$info = password_get_info($context['model'][$field_name]);
					if ($info['algo'] === null) {
						$context['model'][$field_name] = password_hash($context['model'][$field_name], PASSWORD_DEFAULT);
					}
				}
			}
		};

		//careful: _begin_
		$this->db_begin_create($func_hash_password);
		$this->db_begin_update($func_hash_password);

		$func_unset_password = function (&$context) use ($field_names) {
			if (isset($context['models'])) {
				foreach ($field_names as $field_name) {
					foreach ($context['models'] as &$model) {
						if (isset($model[$field_name])) {
							$model[$field_name] =  substr($model[$field_name], -6);
						}
					}
				}
			}

			// remove password in model, old model
			if (isset($context['model'])) {
				foreach ($field_names as $field_name) {
					if (isset($context['model'][$field_name])) {
						// last 6 characters;
						$context['model'][$field_name] =  substr($context['model'][$field_name], -6);
					}
				}
			}

			if (isset($context['old_model'])) {
				foreach ($field_names as $field_name) {
					if (isset($context['old_model'][$field_name])) {
						$context['old_model'][$field_name] = substr($context['old_model'][$field_name], -6);
					}
				}
			}
		};

		$this->web_after_find($func_unset_password);
		$this->web_after_create($func_unset_password);
		$this->web_after_update($func_unset_password);
		$this->web_after_delete($func_unset_password);

		return $this;
	}
}
