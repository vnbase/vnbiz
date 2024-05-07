<?php

namespace VnBiz;

use Error, R;

class Schema {
	public $model_name;

	public $schema = [];

	public $has_tags = false; // done

	public $has_comments = false; //done

	public $has_history = false; //done

    public $has_reviews = false;    //TODO

	public $has_trash = false;  // done

	public $back_refs = [];

    public $text_search = false;

	public function __construct($model_name) {
		$this->model_name = $model_name;
	}

	public function model_name() {
		return $this->model_name;
	}

	public function add_field($field_name, $type) {
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

	public function set_field($field_name, $desc) {

		if (isset($this->schema[$field_name])) {
			$this->schema[$field_name] = array_merge($this->schema[$field_name], $desc);
			return;
		}

		$this->schema[$field_name] = $desc;
	}

	public function get_fields_by_type($type) {
		$result = [];
		// echo json_encode($this->schema);
		foreach ($this->schema as $field_name => $field_def) {
			if ($field_def['type'] == $type) {
				$result[$field_name] = $field_def;
			}
		}
		return $result;
	}

	public function get_field_names() {
		return array_keys($this->schema);
	}

	public function crop(&$model) {
		$new_model = [];

		foreach ($this->get_field_names() as $field_name) {
			if (isset($model[$field_name])) {
				$new_model[$field_name] = $model[$field_name];
			}
		}

		return $new_model;
	}
}


class Model {
	private $schema;

	public function __construct($model_name) {
		$this->schema = new Schema($model_name);

		// $this->crop();
		// $this->has_trash();
		$this->time_at();
		$this->id();
	}

	public function schema() {
		return $this->schema;
	}

	public function get_schema_details() {
		return $this->schema->schema;
	}

	public function get_model_field_names() {
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

	private function web_secure_id($field_name) {
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
				foreach($context['models'] as &$model) {
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
						foreach($arr as $key=>$value) { // $gt $lt $e
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
				foreach($context['models'] as &$model) {
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

	private function id() {
		$this->model_id('id');
		return $this;
	}

	public function model_name() {
		$field_names = func_get_args();

		foreach ($field_names as $field_name) {
			vnbiz_assure_valid_name($field_name);

			$this->schema->add_field($field_name, 'model_name');
		}

		return $this;
	}

	public function model_id() {
		$field_names = func_get_args();

		foreach ($field_names as $field_name) {
			vnbiz_assure_valid_name($field_name);

			$this->schema->add_field($field_name, 'model_id');

			$this->web_secure_id($field_name);
		}


		return $this;
	}

	public function time_at() {
		$this->datetime('created_at', 'updated_at');

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

	public function default($values) {
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

	public function text_search() {
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

	public function has_history($remove_on_delete = true) {
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

	 public function has_trash() {
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

	public function has_reviews() {
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


	public function has_tags() {
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


		$this->db_after_get($func_get_model_tags);


		$func_delete_model_tags = function (&$context) use ($model_name) {
			$old_model = vnbiz_get_var($context['old_model'], []);
			$model_id = $old_model['id'];

			vnbiz_model_remove_all_tags($model_name, $model_id);
		};


		$this->db_after_delete($func_delete_model_tags);
		
		$before_db_before_exec = function (&$context) use ($model_name) {
			$db_context = &$context['db_context'];
			$tags = [];
			if (isset($context['filter']) && isset($context['filter']['@tags'])) {
				$tags = $context['filter']['@tags'];
			}
			if (sizeof($tags) > 0) {
				$query = &$db_context['conditions_query'];
				$param = &$db_context['conditions_param'];
				$query[] = "(id IN (SELECT mt.model_id FROM modeltag mt INNER JOIN tag t ON t.id=mt.tag_id AND mt.model_name=? AND t.name IN (" . R::genSlots( $tags ) . ") GROUP BY mt.model_id HAVING COUNT(t.id)=? ))";
				$param[] = $model_name;
				array_push($param, ...$tags);
				$param[] = sizeof($tags);
			}
			
			// var_dump($context);
		};
		
		vnbiz_add_action("db_before_find_exe_$model_name", $before_db_before_exec);
		vnbiz_add_action("db_before_count_exe_$model_name", $before_db_before_exec);

		return $this;
	}

	public function web_before_create($func) {
		$model_name = $this->schema->model_name;

		vnbiz_add_action("web_before_model_create_$model_name", $func);

		return $this;
	}

	public function web_before_update($func) {
		$model_name = $this->schema->model_name;

		vnbiz_add_action("web_before_model_update_$model_name", $func);

		return $this;
	}

	public function web_before_delete($func) {
		$model_name = $this->schema->model_name;

		vnbiz_add_action("web_before_model_delete_$model_name", $func);

		return $this;
	}

	public function web_before_find($func) {
		$model_name = $this->schema->model_name;

		vnbiz_add_action("web_before_model_find_$model_name", $func);

		return $this;
	}

	public function web_after_create($func) {
		$model_name = $this->schema->model_name;

		vnbiz_add_action("web_after_model_create_$model_name", $func);

		return $this;
	}

	public function web_after_update($func) {
		$model_name = $this->schema->model_name;

		vnbiz_add_action("web_after_model_update_$model_name", $func);

		return $this;
	}

	public function web_after_delete($func) {
		$model_name = $this->schema->model_name;

		vnbiz_add_action("web_after_model_delete_$model_name", $func);

		return $this;
	}

	public function web_after_find($func) {
		$model_name = $this->schema->model_name;

		vnbiz_add_action("web_after_model_find_$model_name", $func);

		return $this;
	}

	public function db_begin_create($func) {
		$model_name = $this->schema->model_name;

		vnbiz_add_action("db_before_create", function (&$context) use ($func, $model_name) {
			if (isset($context['model_name']) && $context['model_name'] == $model_name) {
				$func($context);
			}
		});

		return $this;
	}

	public function db_begin_update($func) {
		$model_name = $this->schema->model_name;

		vnbiz_add_action("db_before_update", function (&$context) use ($func, $model_name) {
			if (isset($context['model_name']) && $context['model_name'] == $model_name) {
				$func($context);
			}
		});

		return $this;
	}

	public function db_begin_find($func) {
		$model_name = $this->schema->model_name;

		vnbiz_add_action("db_before_find", function (&$context) use ($func, $model_name) {
			if (isset($context['model_name']) && $context['model_name'] == $model_name) {
				$func($context);
			}
		});

		return $this;
	}

	public function db_begin_delete($func) {
		$model_name = $this->schema->model_name;

		vnbiz_add_action("db_before_delete", function (&$context) use ($func, $model_name) {
			if (isset($context['model_name']) && $context['model_name'] == $model_name) {
				$func($context);
			}
		});

		return $this;
	}

	public function db_before_create($func) {
		$model_name = $this->schema->model_name;

		vnbiz_add_action("db_before_create_$model_name", $func);

		return $this;
	}

	public function db_after_create($func) {
		$model_name = $this->schema->model_name;
		vnbiz_add_action("db_after_create_$model_name", $func);

		return $this;
	}

	public function db_after_commit_create($func) {
		$model_name = $this->schema->model_name;
		vnbiz_add_action("db_after_commit_create_$model_name", $func);

		return $this;
	}

	public function db_after_get($func) {
		$model_name = $this->schema->model_name;
		vnbiz_add_action("db_after_get_$model_name", $func);

		return $this;
	}

	public function db_before_find($func) {
		$model_name = $this->schema->model_name;
		vnbiz_add_action("db_before_find_$model_name", $func);

		return $this;
	}

	public function db_after_find($func) {
		$model_name = $this->schema->model_name;
		vnbiz_add_action("db_after_find_$model_name", $func);

		return $this;
	}

	public function db_before_update($func) {
		$model_name = $this->schema->model_name;
		vnbiz_add_action("db_before_update_$model_name", $func);

		return $this;
	}

	public function db_after_update($func) {
		$model_name = $this->schema->model_name;
		vnbiz_add_action("db_after_update_$model_name", $func);

		return $this;
	}

	public function db_after_commit_update($func) {
		$model_name = $this->schema->model_name;
		vnbiz_add_action("db_after_commit_update_$model_name", $func);

		return $this;
	}

	public function db_before_delete($func) {
		$model_name = $this->schema->model_name;
		vnbiz_add_action("db_before_delete_$model_name", $func);

		return $this;
	}

	public function db_after_delete($func) {
		$model_name = $this->schema->model_name;
		vnbiz_add_action("db_after_delete_$model_name", $func);

		return $this;
	}

	public function db_after_commit_delete($func) {
		$model_name = $this->schema->model_name;
		vnbiz_add_action("db_after_commit_delete_$model_name", $func);

		return $this;
	}

	// public function has_tags() {
	// 	$this->schema()->has_tags = true;

	// 	$this->schema->add_field('tags', 'tags');

	// 	$this->before_create();
	// 	$this->db_before_update();
	// 	$this->db_after_delete();
	// }

	public function has_comments($comment_enable = true /*default*/) {
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

	public function has_usermarks() {
		$model_name = $this->schema->model_name;

		$mark_types = func_get_args();

		$this->schema->mark_types = $mark_types;

		$default = [];

		foreach ($mark_types as $mark_type) {
			vnbiz_assure_valid_name($mark_type);

			$this->int('number_of_' . $mark_type);
			$default['number_of_' . $mark_type] = 0;
		}
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

	public function require() {
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

	public function bool() {
		$field_names = func_get_args();

		foreach ($field_names as $field_name) {
			vnbiz_assure_valid_name($field_name);

			$this->schema->add_field($field_name, 'bool');
		}

		$func_validate_bool = function (&$context) use ($field_names) {
			$model = &$context['model'];

			foreach ($field_names as $field_name) {
				if (isset($model[$field_name])) {
					$value = $model[$field_name];
					if (isset($model[$field_name]) && !is_bool($value)) {
						if (is_string($value)) {
							$model[$field_name] = $value === 'true' || $value === 'TRUE' || $value === '1';
						} else if (is_numeric($value)) {
							$model[$field_name] = $value != 0;
						} else {
							throw new VnBizError("$field_name must be bool", 'invalid_model');
						}
					}
				}
			}
		};

		$this->db_before_create($func_validate_bool);
		$this->db_before_update($func_validate_bool);

		return $this;
	}

	public function date() {
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

	public function string() {
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

	public function slug() {
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
					if(preg_match('/^[a-z][a-z0-9_-]*$/', $value) == false) {
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

	public function text() {
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

    public function json() {
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

        return $this;
    }

	public function int() {
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

		return $this;
	}

	public function uint() {
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

		return $this;
	}

	public function float() {
		$field_names = func_get_args();

		foreach ($field_names as $field_name) {
			vnbiz_assure_valid_name($field_name);

			$this->schema->add_field($field_name, 'float');
		}

		$func_validate_float = function (&$context) use ($field_names) {
			$model = &$context['model'];

			foreach ($field_names as $field_name) {
				if (isset($model[$field_name]) && !is_numeric($$model[$field_name])) {
					throw new VnBizError("$field_name must be float", 'invalid_model');
				}
			}
		};

		$this->db_before_create($func_validate_float);
		$this->db_before_update($func_validate_float);

		return $this;
	}


	public function enum($field_name, $options, $default_value = null) {
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

	public function unique($index_key, $field_names) {
		$model_name = $this->schema->model_name;

		vnbiz_add_action('sql_gen_index', function (&$context) use ($model_name, $index_key, $field_names) {
			$sql_field_names = array_map(function ($name) {
				return "`$name`";
			}, $field_names);

			$sql_field_names = join(",", $sql_field_names);

			isset($context['sql']) ?: $context['sql'] = '';

			if (!vnbiz_sql_table_index_exists($model_name, $index_key)) {
				$context['sql'] .= "
					CREATE UNIQUE INDEX `$index_key` ON `$model_name` ($sql_field_names);
				";
			}
		});

		return $this;
	}

	public function index($index_key, $field_names) {
		$model_name = $this->schema->model_name;

		vnbiz_add_action('sql_gen_index', function (&$context) use ($model_name, $index_key, $field_names) {
			$sql_field_names = array_map(function ($name) {
				return "`$name`";
			}, $field_names);

			$sql_field_names = join(",", $sql_field_names);

			isset($context['sql']) ?: $context['sql'] = '';

			if (!vnbiz_sql_table_index_exists($model_name, $index_key)) {
				$context['sql'] .= "
					CREATE INDEX `$index_key` ON `$model_name` ($sql_field_names);
				";
			}
		});

		return $this;
	}


	public function datetime() {
		$field_names = func_get_args();

		foreach ($field_names as $field_name) {
			vnbiz_assure_valid_name($field_name);

			$this->schema->add_field($field_name, 'uint');
		}

		//TODO: validate datetime

		$func_validate_datetime = function (&$context) use ($field_name) {
			$model = &$context['model'];

			if (isset($model[$field_name])) {
				$value = $model[$field_name];
				if (is_string($value)) {
					// $date_time = new DateTime($value, new DateTimeZone('UTC') );
					// $model[$field_name] = $date_time->getTimestamp();
					if (preg_match("/^[0-9]*$/i", $value)) {

					} else {
						$time = new \DateTime($value, new \DateTimeZone('UTC'));
						$model[$field_name] = (int)$time->format('Uv');
					}
					// ;	
				}
			} else {
				$model[$field_name] = NULL;
			}
		};

		$this->db_before_create($func_validate_datetime);
		$this->db_before_update($func_validate_datetime);
		
		$func_alter_datetime = function (&$context) use ($field_name) {
			$models = &$context['models'];
			
			foreach ($models as &$model) {
				if (is_string($model[$field_name])) {
					$model[$field_name] = intval($model[$field_name]);
				}
			}
		};
		
		$this->db_after_find($func_alter_datetime);

		return $this;
	}


	public function status($field_name, $status_flow, $default_value) {
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

	function ref($field_name, $ref_model_name) {
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

		return $this;
	}

	function no_create() {
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

	function no_update() {
		$field_names = func_get_args();

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

	function back_ref_count($field_name, $ref_model_name, $ref_field_name, $filter = []) {
		$model_name = $this->schema->model_name;

		vnbiz_assure_valid_name($field_name);
		vnbiz_assure_valid_name($ref_field_name);

		vnbiz_assure_model_name_exists($model_name);

		$this->int($field_name);
		$this->no_update($field_name);

		$this->db_before_create(function (&$context) use($field_name) {
			$context['model'][$field_name] = 0;
		});

		$recount = function (&$context) use ($field_name, $model_name, $ref_model_name, $ref_field_name, $filter) {

			$id = null;
			$old_id = null;
			if (isset($context['model']) && isset($context['model'][$ref_field_name])) {
				$id = $context['model'][$ref_field_name];
			}
			if (isset($context['old_model']) && isset($context['old_model'][$ref_field_name])) {
				$old_id = $context['old_model'][$ref_field_name];
			}

			if ($id && vnbiz_array_contains_array($context['model'], $filter)) {
				$count_filter = array_merge($filter, [$ref_field_name => $id]);
				$count = vnbiz_model_count($ref_model_name, $count_filter);
				try {
					vnbiz_model_update($model_name, [
						'id' => $id
					], [
						$field_name => $count
					], [
						'skip_db_actions' => true
					]);
				} catch (\Exception $e) {
					// echo $model_name . '#' . $id . "#";
					trigger_error('back_ref_count error, ' . $e->getMessage(), E_USER_ERROR);
				}
			}
			if ($old_id && $old_id !== $id && vnbiz_array_contains_array($context['old_model'], $filter)) {
				$count_filter = array_merge($filter, [$ref_field_name => $old_id]);
				$count = vnbiz_model_count($ref_model_name, $count_filter);
				try {
					vnbiz_model_update($model_name, [
						'id' => $old_id
					], [
						$field_name => $count
					], [
						'skip_db_actions' => true
					]);
				} catch (\Exception $e) {
					trigger_error('back_ref_count error, ' . $e->getMessage(), E_USER_ERROR);
				}
			}
		};

		vnbiz_add_action('db_after_commit_create_' . $ref_model_name, $recount);
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

	function no_delete() {
		$model_name = $this->schema->model_name();

		$func_stop_delete = function () use ($model_name) {
			throw new VnBizError("$model_name can't be deleted", 'invalid_model');
		};

		$this->db_before_delete($func_stop_delete);

		return $this;
	}

	function author() {

		$this->ref('created_by', 'user');
		$this->ref('updated_by', 'user');

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

		return $this;
	}

	function password() {
		$field_names = func_get_args();
		$model_name = $this->schema->model_name();

		foreach ($field_names as $field_name) {
			vnbiz_assure_valid_name($field_name);

			$this->schema->add_field($field_name, 'password');
		}

		$func_hash_password = function (&$context) use ($field_names) {
			unset($context['model']['updated_by']);

			foreach ($field_names as $field_name) {
				if (isset($context['model'][$field_name])) {
					$info = password_get_info($context['model'][$field_name]);
					if ($info['algo'] === null) {
						$context['model'][$field_name] = password_hash($context['model'][$field_name], PASSWORD_DEFAULT);
					}
				}
			}
		};

		$this->db_before_create($func_hash_password);
		$this->db_before_update($func_hash_password);

		$func_unset_password = function (&$context) use ($field_names) {
			foreach($context['models'] as &$model) {
				foreach ($field_names as $field_name) {
					if (isset($model[$field_name])) {
						unset($model[$field_name]);
					}
				}
			}
		};

		$this->web_after_find($func_unset_password);

		return $this;
	}

	function s3_image($field_name, ...$sizes) {
		$this->schema->add_field($field_name, 'image');

		$image_sizes = [];
		for($x = 0; $x < sizeof($sizes); $x++) {
			$image_sizes[$x] = $sizes[$x];
			sizeof($image_sizes[$x]) > 1 ?: $image_sizes[$x][1] = $image_sizes[$x][0];
		}
		array_unshift($image_sizes, ['', '']);

		$upload_file = function (&$context) use ($field_name, $sizes) {
			if (isset($context['model']) && isset($context['model'][$field_name])) {
				$file_name = $context['model'][$field_name]['file_name'];
				$file_size = $context['model'][$field_name]['file_size'];
				$file_path = $context['model'][$field_name]['file_path'];
				$file_type = $context['model'][$field_name]['file_type'];

				$context['temp_files'] = [];
				
				// $image = new \Gmagick($file_path);
				$image = new \VnbizImage($file_path);

				$s3_model = [
					'name' => $file_name,
					'size' => $file_size,
					'type' => $file_type,
					'path_0' => $file_path,
					'is_image' => true,
					'width' => $image->get_width(),
					'height' => $image->get_Height(),
				];

				for($i=1; $i < 10; $i++) {
					if ($i - 1 < sizeof($sizes)) {
						$size = $sizes[$i - 1];
						sizeof($size) > 1 ?: $size[1] = $size[0];

						$new_file = $file_path . '_' . $i;

						$image->scale($new_file, $size[0], $size[1]);
						//TODO; resizeimage
						$s3_model['path_' . $i] = $new_file;
						$context['temp_files'][] = $new_file;
					}
				}

				$result = vnbiz_model_create('s3', $s3_model);
				$context['model'][$field_name] = $result['id'];
			}
		};

		$this->db_begin_create($upload_file);
		$this->db_begin_update($upload_file);

		$delete_files = function (&$context) {
			if (isset($context['temp_files'])) {
				foreach($context['temp_files'] as $path) {
					try {
						unlink($path);
					} catch (\Exception $e) {

					}
				}
			}
		};

		$this->db_after_commit_create($delete_files);
		$this->db_after_commit_update($delete_files);

		$this->db_after_get(function (&$context) use ($field_name, $image_sizes){
			if (isset($context['model'][$field_name])) {
				$s3 = vnbiz_model_find_one('s3', ['id' => $context['model'][$field_name]]);
				$context['model']['@' . $field_name] = &$s3;
				if ($context['model']['@' . $field_name]) {
					$sizes = $image_sizes;
					$sizes[0] = [$s3['width'], $s3['height']];
					$context['model']['@' . $field_name]['@image_sizes'] = $sizes;
				}
			}
		});

		return $this;
	}
	
	public function s3_file($field_name) {
		$this->schema->add_field($field_name, 'file');

		$upload_file = function (&$context) use ($field_name) {
			if (isset($context['model']) && isset($context['model'][$field_name])) {
				$file_name = $context['model'][$field_name]['file_name'];
				$file_size = $context['model'][$field_name]['file_size'];
				$file_path = $context['model'][$field_name]['file_path'];
				$file_type = $context['model'][$field_name]['file_type'];
				$result = vnbiz_model_create('s3', [
					'name' => $file_name,
					'size' => $file_size,
					'type' => $file_type,
					'path_0' => $file_path
				]);
				$context['model'][$field_name] = $result['id'];
			}
		};

		$this->db_begin_create($upload_file);
		$this->db_begin_update($upload_file);

		$this->db_after_get(function (&$context) use ($field_name){
			if (isset($context['model'][$field_name])) {
				$s3 = vnbiz_model_find_one('s3', ['id' => $context['model'][$field_name]]);
				$context['model']['@' . $field_name] = &$s3;
			}
		});

		return $this;
	}

	public function create_permission(...$permissions) {
		$this->web_before_create(function (&$context) use ($permissions) {
			vnbiz_assure_user_has_permissions(...$permissions);
		});
		return $this;
	}

	public function create_permission_or($permissions, $func) {
		$this->web_before_create(function (&$context) use ($permissions, $func) {
			
			if (vnbiz_user_has_permissions(...$permissions)) {
				return;
			}

			if ($func($context)) {
				return;
			}
			
			throw new VnBizError("Require permissions: " . implode(',', $permissions), 'permission');
		});
		return $this;
	}

	public function update_permission(...$permissions) {
		$this->web_before_update(function (&$context) use ($permissions) {
			vnbiz_assure_user_has_permissions(...$permissions);
		});
		return $this;
	}

	public function update_permission_or($permissions, $func) {
		$this->web_before_update(function (&$context) use ($permissions, $func) {
			
			if (vnbiz_user_has_permissions(...$permissions)) {
				return;
			}

			if ($func($context)) {
				return;
			}
			
			throw new VnBizError("Require permissions: " . implode(',', $permissions), 'permission');
		});
		return $this;
	}

	public function delete_permission(...$permissions) {
		$this->web_before_delete(function (&$context) use ($permissions) {
			vnbiz_assure_user_has_permissions(...$permissions);
		});
		return $this;
	}

	public function delete_permission_or($permissions, $func) {
		$this->web_before_delete(function (&$context) use ($permissions, $func) {
			
			if (vnbiz_user_has_permissions(...$permissions)) {
				return;
			}

			if ($func($context)) {
				return;
			}
			
			throw new VnBizError("Require permissions: " . implode(',', $permissions), 'permission');
		});
		return $this;
	}

	public function find_permission(...$permissions) {
		$this->web_before_find(function (&$context) use ($permissions) {
			vnbiz_assure_user_has_permissions(...$permissions);
		});
		return $this;
	}

	public function find_permission_or($permissions, $func) {
		$this->web_before_find(function (&$context) use ($permissions, $func) {
			
			if (vnbiz_user_has_permissions(...$permissions)) {
				return;
			}

			if ($func($context)) {
				return;
			}
			
			throw new VnBizError("Require permissions: " . implode(',', $permissions), 'permission');
		});
		return $this;
	}


	public function read_permission(...$permissions) {
		$this->find_permission(...$permissions);
		return $this;
	}

	public function read_permission_or($permissions, $func) {
		$this->find_permission_or($permissions, $func);
		return $this;
	}

	public function write_permission(...$permissions) {
		$this->create_permission(...$permissions);
		$this->update_permission(...$permissions);
		$this->delete_permission(...$permissions);
		return $this;
	}

	public function write_permission_or($permissions, $func) {
		$this->create_permission_or($permissions, $func);
		$this->update_permission_or($permissions, $func);
		$this->delete_permission_or($permissions, $func);
		return $this;
	}

	public function read_field_permission($fields, $permissions) {
		$this->web_after_find(function (&$context) use ($fields, $permissions) {
			$models = &$context['models'];
			if (vnbiz_user_has_permissions(...$permissions) == false) {
				foreach($models as &$model) {
					foreach($fields as $field) {
						unset($model[$field]);
					}
				}
			}
		});
		return $this;
	}

	public function write_field_permission($fields, $permissions) {
		$this->web_before_find(function (&$context) use ($fields, $permissions) {
			$model = &$context['model'];
			if (vnbiz_user_has_permissions(...$permissions) == false) {
				foreach($fields as $field) {
					if (isset($model[$field])) {
						throw new VnBizError('Field ' .  $field . ' need permisison to write: ' . implode(',' , $permissions), 'permission');
					}
				}
			}
		});
		return $this;
	}

	public function read_field_permission_or($fields, $permissions, $func) {
		$this->web_after_find(function (&$context) use ($fields, $permissions, $func) {
			$models = &$context['models'];
			
			if (vnbiz_user_has_permissions(...$permissions) == false) {
				foreach($models as &$model) {
					if (!$func($model)) {
						foreach($fields as $field) {
							unset($model[$field]);
						}
					}
				}
			}
		});
		return $this;
	}

	public function write_field_permission_or($fields, $permissions, $func) {
		$this->web_before_find(function (&$context) use ($fields, $permissions, $func) {
			$model = &$context['model'];
			if (vnbiz_user_has_permissions(...$permissions) == false) {
				if (!$func($model)) {
					foreach($fields as $field) {
						if (isset($model[$field])) {
							throw new VnBizError('Field ' .  $field . ' need permisison to write: ' . implode(',' , $permissions), 'permission');
						}
					}
				}
			}
		});
		return $this;
	}
}
