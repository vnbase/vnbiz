<?php

namespace VnBiz;

use R;
use VnBiz\Model;

class VnBiz
{
	use VnBiz_sql;
	use VnBiz_restful;

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

	/**
	 * $namespace == null: all namespaces are allowed
	 */
	public function use_namespaces($namespaces = null)
	{
		if ($namespaces) {
			$GLOBALS['VNBIZ_NAMESPACES'] = [];
			foreach ($namespaces as $namespace) {
				$GLOBALS['VNBIZ_NAMESPACES'][$namespace] = true;
			}
			return;
		}
		$GLOBALS['VNBIZ_NAMESPACES'] = true;
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

	public function init_oauth_google($client_id, $client_secret)
	{
		define('OAUTH_GOOGLE_CLIENT_ID', $client_id);
		define('OAUTH_GOOGLE_CLIENT_SECRET', $client_secret);
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
