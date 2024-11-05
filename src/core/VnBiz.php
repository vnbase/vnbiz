<?php

namespace VnBiz;

use Exception, R;
use RedBeanPHP\RedException\SQL as SQLException;
use SimpleXMLElement;
use VnBiz\Model;

class VnBiz
{
	private static $_instance = null;

	public $actions = null;

	private $models = [];

	private $app_name;

	private function __construct()
	{
		$this->actions = new Actions();
	}

	public static function instance()
	{
		if (self::$_instance == null) {
			self::$_instance = new VnBiz();
		}

		return self::$_instance;
	}

	public function getAppName()
	{
		return $this->app_name;
	}

	public function init_app($app_name, $token_secret)
	{
		$this->app_name = $app_name;
		define('VNBIZ_TOKEN_SECRET', $token_secret);
		return $this;
	}


	public function restful()
	{
		// Specify domains from which requests are allowed
		header('Access-Control-Allow-Origin: *');

		// Specify which request methods are allowed
		header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');

		// Additional headers which may be sent along with the CORS request
		header('Access-Control-Allow-Headers: *');

		if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
			// Set the age to 1 day to improve speed/caching.
			header('Access-Control-Max-Age: 86400');
			return;
		}

		$context = array_merge($_GET, $_POST);
		$json = json_decode(file_get_contents('php://input'), true);

		if ($json) {
			$context = array_merge($context, $json);
		}

		if (isset($_FILES['model'])) {
			isset($context['model']) ?: $context['model'] = [];
			foreach ($_FILES['model']['name'] as $field_name => $file_name) {
				if (is_string($file_name)) {
					if ($_FILES['model']['error'][$field_name] === 0) {
						$context['model'][$field_name] = [
							'file_name' => $file_name,
							'file_type' => $_FILES['model']['type'][$field_name],
							'file_size' => $_FILES['model']['size'][$field_name],
							'file_path' => $_FILES['model']['tmp_name'][$field_name]
						];
					}
				} else {
					throw new VnBizError('Unsupported multiple file upload', 'invalid_model');
				}
			}
		}

		$result = [
			'code' => 'no_such_action'
		];


		try {
			vnbiz_do_action('web_before', $context);

			$action = vnbiz_get_var($context['action'], '');
			switch ($action) {
				case 'model_create':
					vnbiz_do_action('web_model_create', $context);
					$result = $context;
					$result['code'] = 'success';
					break;
				case 'model_update':
					vnbiz_do_action('web_model_update', $context);
					$result = $context;
					$result['code'] = 'success';
					break;
				case 'model_count':
					throw new VnBizError("Operator is not supported", "unsupported");
					vnbiz_do_action('web_model_count', $context);
					$result = $context;
					$result['code'] = 'success';
					break;
				case 'model_find':
					vnbiz_do_action('web_model_find', $context);
					$result = $context;
					$result['code'] = 'success';
					break;
				case 'model_delete':
					vnbiz_do_action('web_model_delete', $context);
					$result = $context;
					$result['code'] = 'success';
					break;
				default:
					if (vnbiz_str_starts_with($action, 'service_')) {
						vnbiz_do_action($action, $context);

						if (isset($context['models'])) {
							$arr = [];
							foreach ($context['models'] as &$model) {
								if (isset($model['@model_name'])) {
									$arr[$model['@model_name']] = $arr[$model['@model_name']] ?? [];
									$arr[$model['@model_name']][] = &$model;
								}
							}
							foreach ($arr as $model_name => $data) {
								$c = ['models' => &$data];
								vnbiz_do_action("web_after_model_find_$model_name", $c);
							}
						}
						$result = $context;
						if ($result['code'] !== 'success') {
							http_response_code(400);
						}
					} else {
						$result['message'] = "No action name '$action'";
					}
			}
		} catch (VnbizError $e) {
			http_response_code($e->http_status());

			$result = [
				'code' => $e->get_status(),
				'error' => $e->getMessage(),
				'error_fields' => $e->get_error_fields(),
				'stack' => $e->getTraceAsString()
			];
		} catch (Exception $e) {
			http_response_code(500);
			$result = [
				'code' => 'error',
				'error' => $e->getMessage(),
				'stack' => $e->getTraceAsString()
			];
		};


		vnbiz_do_action('web_after', $context);
		return $result;
	}

	public function handle_restful()
	{
		ob_start();
		$result = $this->restful();
		$output = ob_get_clean();
		if ($output) {
			error_log($output);
		}

		unset($result['params']);

		header('Content-Type: application/json');
		echo json_encode($result, JSON_UNESCAPED_SLASHES);
		return;
	}

	public function handle_restful_xml()
	{
		ob_start();
		$result = $this->restful();
		$output = ob_get_clean();
		if ($output) {
			error_log($output);
		}
		unset($result['params']);

		$xml = new SimpleXMLElement('<?xml version="1.0"?><data></data>');
		vnbiz_array_to_xml($result, $xml);
		header("Content-type: text/xml; charset=utf-8");
		echo $xml->asXML();
		return;
	}

	public function init_aws($access_key_id, $access_key_secret, $region, $s3_bucket, $host = "", $scheme = 'https')
	{
		define('AWS_ACCESS_KEY_ID', $access_key_id);
		define('AWS_ACCESS_KEY_SECRET', $access_key_secret);
		define('AWS_REGION', $region);
		define('AWS_S3_BUCKET', $s3_bucket);
		define('AWS_S3_SCHEME', $scheme);

		if ($host) {
			define('AWS_S3_HOST', $host);
		} else {
			define('AWS_S3_HOST', "s3.$region.amazonaws.com");
		}
		return $this;
	}

	public function init_mailer($host, $username, $password, $port = 465)
	{
		define('MAILER_SMTP_HOST', $host);
		define('MAILER_SMTP_PORT', $port);
		define('MAILER_SMTP_USERNAME', $username);
		define('MAILER_SMTP_PASSWORD', $password);

		return $this;
	}

	public function init_redis($host, $username = null, $password = null, $port = 6379)
	{
		define('REDIS_HOST', $host);
		define('REDIS_PORT', $port);
		define('REDIS_USERNAME', $username);
		define('REDIS_PASSWORD', $password);

		return $this;
	}

	public function init_db_mysql($servername = 'localhost', $username = "", $password = "", $dbname = '')
	{
		R::setup("mysql:host=$servername;dbname=$dbname", $username, $password); //for both mysql or mariaDB
		R::freeze(true); //will freeze redbeanphp

		$this->actions()->add_action_one('model_create', function (&$context) {

			$this->actions()->do_action('db_before_create', $context);

			if (!isset($context['model_name'])) {
				throw new VnBizError('Missing model_name', 'invalid_context');
			}
			if (!isset($context['model']) || !is_array($context['model'])) {
				throw new VnBizError('Missing model', 'invalid_context');
			}

			$model_name = $context['model_name'];
			$schema = $this->models()[$model_name]->schema();

			$row = R::dispense($model_name);

			$in_trans = vnbiz_get_key($context, 'in_trans', false);
			if (!$in_trans) {
				$context['in_trans'] = true;
			}

			!$in_trans && R::begin();
			try {

				$this->actions()->do_action("db_before_create_$model_name", $context);

				$row->import($schema->crop($context['model']));

				$id = R::store($row);

				$context['model']['id'] = $id;
				$context['model']['@model_name'] = $model_name;
				$this->actions()->do_action("db_after_create_$model_name", $context);

				!$in_trans && R::commit();
				!$in_trans && ($context['in_trans'] = false);
			} catch (SQLException $e) {
				!$in_trans && R::rollback();
				!$in_trans && ($context['in_trans'] = false);
				if (method_exists($e, 'getSqlState') && $e->getSqlState() === '23000') { // dupplicated
					throw new VnBizError('Model already exists', 'model_exists');
				} else {
					if (method_exists($e, 'getSqlState')) {
						error_log("SQL_ERROR: " . $e->getSqlState());
					}
					throw $e;
				}
			}

			$this->actions()->do_action("db_after_commit_create_$model_name", $context);

			$this->actions()->do_action('db_after_create', $context);
		});

		$this->actions()->add_action_one('model_update', function (&$context) {
			$meta = vnbiz_get_var($context['meta'], []);
			$skip_db_actions = vnbiz_get_var($meta['skip_db_actions'], false);

			$skip_db_actions ?: $this->actions()->do_action('db_before_update', $context);

			if (!isset($context['model_name'])) {
				throw new VnBizError('Missing model_name', 'invalid_context');
			}
			if (!is_array($context['model'])) {
				throw new VnBizError('Missing model', 'invalid_context');
			}
			if (!isset($context['filter']) || !isset($context['filter']['id'])) {
				throw new VnBizError('Missing filter[id]', 'invalid_context');
			}

			$model_name = $context['model_name'];
			$schema = $this->models()[$model_name]->schema();
			$filter = $context['filter'];

			$in_trans = vnbiz_get_key($context, 'in_trans', false);
			if (!$in_trans) {
				$context['in_trans'] = true;
			}

			!$in_trans && R::begin();
			try {
				$row = R::findOne($model_name, 'id=?', [$filter['id']]);

				if (!$row || $row['id'] == 0) {
					throw new VnBizError('Model do not exist', 'model_not_found');
				}
				$context['old_model'] = $row->export();
				$context['old_model']['@model_name'] = $model_name;

				$skip_db_actions ?: $this->actions()->do_action("db_before_update_$model_name", $context);

				// $row->import($context['model']);
				$row->import($schema->crop($context['model']));
				R::store($row);

				$skip_db_actions ?: $this->actions()->do_action("db_after_update_$model_name", $context);

				!$in_trans && R::commit();
				!$in_trans && ($context['in_trans'] = false);
			} catch (Exception $e) {
				!$in_trans && R::rollback();
				!$in_trans && ($context['in_trans'] = false);
				throw $e;
			}

			$this->actions()->do_action("db_after_commit_update_$model_name", $context);

			$skip_db_actions ?: $this->actions()->do_action('db_after_update', $context);
		});

		$this->actions()->add_action_one('model_delete', function (&$context) {
			$this->actions()->do_action('db_before_delete', $context);

			if (!isset($context['model_name'])) {
				throw new VnBizError('Missing model_name', 'invalid_context');
			}

			if (!isset($context['filter']) || $context['filter'] == null) {
				throw new VnBizError('Missing filter', 'invalid_context');
			}


			$model_name = $context['model_name'];
			$filter = $context['filter'];
			$conditions_query = [];
			$conditions_param = [];

			$field_names = vnbiz_get_model_field_names($model_name);
			foreach ($field_names as $field_name) {
				if (isset($filter[$field_name])) {
					$conditions_query[] = "$field_name=?";
					$conditions_param[] = $filter[$field_name];
				}
			}

			$conditions_query = join(' AND ', $conditions_query) . ' LIMIT 1';

			$in_trans = vnbiz_get_key($context, 'in_trans', false);
			if (!$in_trans) {
				$context['in_trans'] = true;
			}

			!$in_trans && R::begin();
			try {
				$rows = R::find($model_name, $conditions_query, $conditions_param);

				if (sizeof($rows) == 0) {
					throw new VnBizError('Model do not exist', 'model_not_found');
				}
				$context['old_model'] = R::beansToArray($rows)[0];
				$context['old_model']['@model_name'] = $model_name;

				$field_names = vnbiz_get_model_field_names($model_name);
				foreach ($field_names as $field_name) {
					if (isset($model[$field_name])) {
						$row[$field_name] = $model[$field_name];
					}
				}

				$this->actions()->do_action("db_before_delete_$model_name", $context);
				R::trashAll($rows);
				$this->actions()->do_action("db_after_delete_$model_name", $context);

				!$in_trans && R::commit();
				!$in_trans && ($context['in_trans'] = false);
			} catch (Exception $e) {
				!$in_trans && R::rollback();
				!$in_trans && ($context['in_trans'] = false);
				throw $e;
			}

			$this->actions()->do_action("db_after_commit_delete_$model_name", $context);

			$this->actions()->do_action('db_after_delete', $context);
		});

		$this->actions()->add_action_one('model_count', function (&$context) {
			$meta = vnbiz_get_var($context['meta'], []);
			$this->actions()->do_action('db_before_count', $context);

			if (!isset($context['model_name'])) {
				throw new VnBizError('Missing model_name', 'invalid_context');
			}

			$model_name = $context['model_name'];
			$filter = vnbiz_get_var($context['filter'], []);

			$text_search = vnbiz_get_var($meta['text_search'], null);
			$conditions_query = [];
			$conditions_param = [];

			$schema = $this->models[$model_name]->schema();

			if ($text_search && $schema->text_search) {
				$fields = array_map(function ($item) {
					return '`' . $item . '`';
				}, $schema->text_search);
				$fields = join(',', $fields);
				$text_condition = "MATCH (" . $fields . ') AGAINST(?)';

				$conditions_query[] = $text_condition;
				$conditions_param[] = $text_search;
			}

			$field_names = vnbiz_get_model_field_names($model_name);
			foreach ($field_names as $field_name) {
				if (isset($filter[$field_name])) {
					$value = $filter[$field_name];

					if ($field_name === 'datascope') {
						if (is_array($value)) {
							$or_query = [];
							if (isset($value[0])) {
								foreach ($value as $datascope) {
									$or_query[] = "(datascope LIKE ?)";
									$conditions_param[] = $datascope . '%';
								}
							}
							$conditions_query[] = join('OR', $or_query);
						} else {
							$conditions_query[] = "datascope LIKE ?";
							$conditions_param[] = $value . '%';
						}

						continue;
					}

					if (is_array($value)) {
						if (isset($value[0])) {
							$conditions_query[] = "$field_name IN (" . R::genSlots($value) . ")";
							array_push($conditions_param, ...$value);
						} else {
							if (array_key_exists('$gt', $value)) {
								$conditions_query[] = "$field_name > ?";
								$conditions_param[] = $value['$gt'];
							}
							if (array_key_exists('$gte', $value)) {
								$conditions_query[] = "$field_name >= ?";
								$conditions_param[] = $value['$gte'];
							}
							if (array_key_exists('$lt', $value)) {
								$conditions_query[] = "$field_name < ?";
								$conditions_param[] = $value['$lt'];
							}
							if (array_key_exists('$lte', $value)) {
								$conditions_query[] = "$field_name <= ?";
								$conditions_param[] = $value['$lte'];
							}
						}
					} else {
						$conditions_query[] = "$field_name=?";
						$conditions_param[] = $value;
					}
				}

				if (isset($order[$field_name])) {
					$order_query[] = $order[$field_name] > 0 ? $field_name : $field_name . ' DESC';
				}
			}

			$this->actions()->do_action("db_before_find_$model_name", $context);


			$context['db_context'] = [
				'conditions_query' => &$conditions_query,
				'conditions_param' => &$conditions_param
			];

			vnbiz_do_action("db_before_count_exe_$model_name", $context);
			unset($context['db_context']);

			$conditions_query = join(' AND ', $conditions_query);

			$this->actions()->do_action("db_before_count_$model_name", $context);

			$context['count'] = R::count($model_name, $conditions_query, $conditions_param);

			$this->actions()->do_action("db_after_count_$model_name", $context);

			$this->actions()->do_action('db_after_count', $context);
		});

		$this->actions()->add_action_one('model_find', function (&$context) {
			$meta = vnbiz_get_var($context['meta'], []);
			$skip_db_actions = vnbiz_get_var($meta['skip_db_actions'], false);

			$skip_db_actions ?: $this->actions()->do_action('db_before_find', $context);

			if (!isset($context['model_name'])) {
				throw new VnBizError('Missing model_name', 'invalid_context');
			}

			$model_name = $context['model_name'];
			$filter = vnbiz_get_var($context['filter'], []);
			$order = vnbiz_get_var($meta['order'], []);
			$limit = vnbiz_get_var($meta['limit'], 100);
			$text_search = vnbiz_get_var($meta['text_search'], null);
			$offset = vnbiz_get_var($meta['offset'], 0);
			$ref = vnbiz_get_var($meta['ref'], false);
			$count = vnbiz_get_var($meta['count'], false);
			$conditions_query = [];
			$conditions_param = [];
			$order_query = [];

			$schema = $this->models[$model_name]->schema();

			if ($text_search && $schema->text_search) {
				$fields = array_map(function ($item) {
					return '`' . $item . '`';
				}, $schema->text_search);
				$fields = join(',', $fields);
				$text_condition = "MATCH (" . $fields . ') AGAINST(?)';

				$conditions_query[] = $text_condition;
				$conditions_param[] = $text_search;
			}

			$field_names = vnbiz_get_model_field_names($model_name);
			foreach ($field_names as $field_name) {
				if (isset($filter[$field_name])) {
					$value = $filter[$field_name];

					if ($field_name === 'datascope') {
						if (is_array($value)) {
							$or_query = [];
							if (isset($value[0])) {
								foreach ($value as $datascope) {
									$or_query[] = "(datascope LIKE ?)";
									$conditions_param[] = $datascope . '%';
								}
							}
							$conditions_query[] = join('OR', $or_query);
						} else {
							$conditions_query[] = "datascope LIKE ?";
							$conditions_param[] = $value . '%';
						}

						continue;
					}

					if (is_array($value)) {
						if (isset($value[0])) {
							$conditions_query[] = "$field_name IN (" . R::genSlots($value) . ")";
							array_push($conditions_param, ...$value);
						} else {
							if (array_key_exists('$gt', $value)) {
								$conditions_query[] = "$field_name > ?";
								$conditions_param[] = $value['$gt'];
							}
							if (array_key_exists('$gte', $value)) {
								$conditions_query[] = "$field_name >= ?";
								$conditions_param[] = $value['$gte'];
							}
							if (array_key_exists('$lt', $value)) {
								$conditions_query[] = "$field_name < ?";
								$conditions_param[] = $value['$lt'];
							}
							if (array_key_exists('$lte', $value)) {
								$conditions_query[] = "$field_name <= ?";
								$conditions_param[] = $value['$lte'];
							}
						}
					} else {
						$conditions_query[] = "$field_name=?";
						$conditions_param[] = $value;
					}
				}

				if (isset($order[$field_name])) {
					$order_query[] = $order[$field_name] > 0 ? $field_name : $field_name . ' DESC';
				}
			}
			$order_query = join(', ', $order_query);
			$order_query = $order_query ? ' ORDER BY ' . $order_query : '';

			$skip_db_actions ?: $this->actions()->do_action("db_before_find_$model_name", $context);

			$context['db_context'] = [
				'conditions_query' => &$conditions_query,
				'conditions_param' => &$conditions_param
			];

			vnbiz_do_action("db_before_find_exe_$model_name", $context);
			unset($context['db_context']);

			$conditions_query = join(' AND ', $conditions_query);

			$sql_query = $conditions_query . $order_query . ' LIMIT ? OFFSET ?';
			// $context['sql'] = [];
			// $context['sql'][] = [$sql_query, array_merge($conditions_param, [$limit, $offset])];
			// error_log($sql_query);
			$rows = R::find($model_name, $sql_query, array_merge($conditions_param, [$limit, $offset]));
			$rows = R::beansToArray($rows);
			$context['models'] = [];

			foreach ($rows as $row) {
				$row['@model_name'] = $model_name;
				$c = [
					'model_name' => $model_name,
					'model' => $row
				];

				$skip_db_actions ?: $this->actions()->do_action("db_after_get_$model_name", $c);

				$context['models'][] = $c['model'];
			}

			if ($ref) {
				$ref_fields = $this->models()[$model_name]->schema()->get_fields_by_type("ref");

				$ref_data = [];

				foreach ($context['models'] as $model) { // $ref_data = ['model_name' => [1,3,4]]
					foreach ($ref_fields as $ref_field_name => $ref_def) {
						$ref_model = $ref_def['model_name'];

						isset($ref_data[$ref_model]) ?: $ref_data[$ref_model] = [];

						if (isset($model[$ref_field_name])) {
							$ref_data[$ref_model][] = $model[$ref_field_name];
						}
					}
				}

				foreach ($ref_data as $ref_model_name => $ids) {  // $ref_data = ['model_name' => [1 => [..]]]
					if (sizeof($ids) == 0) {
						continue;
					}
					$rows = R::find($ref_model_name, 'id IN (' . R::genSlots($ids) . ')', $ids);
					$rows = R::beansToArray($rows);
					$models = [];

					foreach ($rows as $row) {
						$row['@model_name'] = $ref_model_name;
						$c = [
							'model_name' => $ref_model_name,
							'model' => $row
						];

						$skip_db_actions ?: $this->actions()->do_action("db_after_get_$ref_model_name", $c);

						$models[$row['id']] = $c['model'];
					}
					$ref_data[$ref_model_name] = $models;
				}



				foreach ($context['models'] as &$model) {
					foreach ($ref_fields as $ref_field_name => $ref_def) {
						$ref_model = $ref_def['model_name'];

						$ref_value = null;
						if (isset($model[$ref_field_name]) && isset($ref_data[$ref_model][$model[$ref_field_name]])) {
							$ref_value = $ref_data[$ref_model][$model[$ref_field_name]];
						}
						$model['@' . $ref_field_name] = $ref_value;
					}
				}
			}

			if ($count) {
				$number_of_rows = R::count($model_name, $conditions_query, $conditions_param);
				$context['meta']['count'] = $number_of_rows;
			}


			$skip_db_actions ?: $this->actions()->do_action("db_after_find_$model_name", $context);

			$skip_db_actions ?: $this->actions()->do_action('db_after_find', $context);
		});

		return $this;
	}

	public function start()
	{
		$context = [];
		vnbiz_do_action('vnbiz_before_start', $context);


		vnbiz_add_action('web_model_create', function (&$context) {
			if (!isset($context['model_name'])) {
				throw new VnBizError('Missing model_name', 'invalid_context');
			}
			$model_name = $context['model_name'];
			vnbiz_do_action("web_before_model_create_$model_name", $context);
			vnbiz_do_action("model_create_$model_name", $context);
			vnbiz_do_action("web_after_model_create_$model_name", $context);
		});
		vnbiz_add_action('web_model_count', function (&$context) {
			if (!isset($context['model_name'])) {
				throw new VnBizError('Missing model_name', 'invalid_context');
			}
			$model_name = $context['model_name'];
			vnbiz_do_action("web_before_model_count_$model_name", $context);
			vnbiz_do_action("model_count_$model_name", $context);
			vnbiz_do_action("web_after_model_count_$model_name", $context);
		});
		vnbiz_add_action('web_model_find', function (&$context) {
			if (!isset($context['model_name'])) {
				throw new VnBizError('Missing model_name', 'invalid_context');
			}
			$model_name = $context['model_name'];
			vnbiz_do_action("web_before_model_find_$model_name", $context);
			vnbiz_do_action("model_find_$model_name", $context);

			$models = &$context['models'] ?? [];
			$ref_fields = vnbiz_model($model_name)->schema()->get_fields_by_type("ref");
			$rows = [];
			foreach ($models as &$model) {
				foreach ($ref_fields as $ref_field_name => $ref_def) {
					if (isset($model['@' . $ref_field_name]) && $model['@' . $ref_field_name]) {
						$rows[$ref_field_name] = $rows[$ref_field_name] ?? [];
						$rows[$ref_field_name][] = &$model['@' . $ref_field_name];
					}
				}
			}
			foreach ($ref_fields as $ref_field_name => $ref_def) {
				$ref_model_name = $ref_def['model_name'];
				if (isset($rows[$ref_field_name]) && sizeof($rows[$ref_field_name]) > 0) {
					$c = ['models' => $rows[$ref_field_name]];
					vnbiz_do_action("web_after_model_find_$ref_model_name", $c);
				}
			}
			vnbiz_do_action("web_after_model_find_$model_name", $context);
		});
		vnbiz_add_action('web_model_update', function (&$context) {
			if (!isset($context['model_name'])) {
				throw new VnBizError('Missing model_name', 'invalid_context');
			}
			$model_name = $context['model_name'];
			vnbiz_do_action("web_before_model_update_$model_name", $context);
			vnbiz_do_action("model_update_$model_name", $context);
			vnbiz_do_action("web_after_model_update_$model_name", $context);
		});
		vnbiz_add_action('web_model_delete', function (&$context) {
			if (!isset($context['model_name'])) {
				throw new VnBizError('Missing model_name', 'invalid_context');
			}
			$model_name = $context['model_name'];
			vnbiz_do_action("web_before_model_delete_$model_name", $context);
			vnbiz_do_action("model_delete_$model_name", $context);
			vnbiz_do_action("web_after_model_delete_$model_name", $context);
		});




		$actions = $this->actions();
		foreach (array_keys(($this->models())) as $model_name) {
			$actions->add_action("model_create_$model_name", function (&$context) use ($actions, $model_name) {
				$context['model_name'] = $model_name;

				$actions->do_action("model_before_create_$model_name", $context);
				$actions->do_action('model_create', $context);
				$actions->do_action("model_after_create_$model_name", $context);
			});
			$actions->add_action("model_update_$model_name", function (&$context) use ($actions, $model_name) {
				$context['model_name'] = $model_name;

				$actions->do_action("model_before_update_$model_name", $context);
				$actions->do_action('model_update', $context);
				$actions->do_action("model_after_update_$model_name", $context);
			});

			$actions->add_action("model_find_$model_name", function (&$context) use ($actions, $model_name) {
				$context['model_name'] = $model_name;

				$actions->do_action("model_before_find_$model_name", $context);
				$actions->do_action('model_find', $context);
				$actions->do_action("model_after_find_$model_name", $context);
			});

			$actions->add_action("model_count_$model_name", function (&$context) use ($actions, $model_name) {
				$context['model_name'] = $model_name;

				$actions->do_action("model_before_count_$model_name", $context);
				$actions->do_action('model_count', $context);
				$actions->do_action("model_after_count_$model_name", $context);
			});

			$actions->add_action("model_delete_$model_name", function (&$context) use ($actions, $model_name) {
				$context['model_name'] = $model_name;

				$actions->do_action("model_before_delete_$model_name", $context);
				$actions->do_action('model_delete', $context);
				$actions->do_action("model_after_delete_$model_name", $context);
			});
		}

		vnbiz_do_action('vnbiz_after_start', $context);
	}

	public function init_modules()
	{
		$module_names = func_get_args();

		foreach ($module_names as $module_name) {
			if (is_callable("vnbiz_init_module_$module_name")) {
				call_user_func("vnbiz_init_module_$module_name");
			} else {
				throw new VnBizError("vnbiz: No such module name: $module_name");
			}
		}

		return $this;
	}

	public function add_model($model_name)
	{
		if (isset($this->models[$model_name])) {
			throw new VnBizError("vnbiz: model already existed: $model_name");
		}

		$model = new Model($model_name);
		$this->models[$model_name] = $model;

		return $model;
	}

	public function actions()
	{
		return $this->actions;
	}

	public function models()
	{
		return $this->models;
	}
}
