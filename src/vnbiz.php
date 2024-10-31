<?php

include __DIR__ . '/base/rb.php';
include __DIR__ . '/base/div.php';
include __DIR__ . '/base/jwt.php';

include __DIR__ . '/base/hashids/src/Math/MathInterface.php';
include __DIR__ . '/base/hashids/src/HashidsInterface.php';
include __DIR__ . '/base/hashids/src/Math/Bc.php';
include __DIR__ . '/base/hashids/src/Math/Gmp.php';
include __DIR__ . '/base/hashids/src/HashidsException.php';
include __DIR__ . '/base/hashids/src/Hashids.php';

include __DIR__ . '/vnbiz_functions.php';
include __DIR__ . '/vnbiz_model.php';
include __DIR__ . '/vnbiz_core.php';

include __DIR__ . '/modules/systemconfig.php';
include __DIR__ . '/modules/user.php';
include __DIR__ . '/modules/usermark.php';
include __DIR__ . '/modules/tag.php';
include __DIR__ . '/modules/comment.php';
include __DIR__ . '/modules/review.php';
include __DIR__ . '/modules/sql.php';
include __DIR__ . '/modules/history.php';
include __DIR__ . '/modules/s3.php';
include __DIR__ . '/modules/template.php';
include __DIR__ . '/modules/email.php';
include __DIR__ . '/modules/jsonschema.php';
include __DIR__ . '/modules/typescriptschema.php';
include __DIR__ . '/modules/oauth.php';
include __DIR__ . '/modules/notification.php';
include __DIR__ . '/modules/redis.php';
include __DIR__ . '/modules/monitor.php';


date_default_timezone_set("UTC");
ini_set('session.cookie_samesite', 'None');
session_start();

vnbiz()
	->init_modules('systemconfig', 'user', 'usermark', 'comment', 'tag', 'review', 'history', 's3', 'template', 'email', 'oauth', 'notification', 'redis', 'monitor');


vnbiz_add_action('service_sys_schema', function (&$context) {
	$models = [];
	foreach (vnbiz()->models() as $model_name=>$model) {
		$properties = [];
		foreach ($model->schema()->schema as $field_name=>$field_def) {
			$properties[] = [
				'field_name' => $field_name,
				'meta' => $field_def
			];
		}
		$models[] = [
			'model_name' => $model_name,
			'meta' => [
				'has_tags' => $model->schema()->has_tags,
				'has_comments' => $model->schema()->has_comments,
				'has_history' => $model->schema()->has_history,
				'has_reviews' => $model->schema()->has_reviews,
				'has_usermarks' => $model->schema()->has_usermarks,
				'back_refs' => $model->schema()->back_refs,
				'text_search' => $model->schema()->text_search,
			],
			'properties' => $properties
		];
	}
	$context['models'] = $models;
});