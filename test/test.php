<?php

use VnBiz\VnBizError;

include_once(__DIR__ . "/../vendor/autoload.php");
include_once(__DIR__ . "/../src/vnbiz.php");

vnbiz()
	->init_app('test', 'THE_TK_SECRET')
	// ->init_db_mysql('10.10.0.3', 'vnbase_test', 'vnbase_test', 'vnbase_test')
	->init_db_mysql('mysql8', 'root', 'rootpass', 'vnbiz_dev')
	->init_aws('minioroot', 'rootpass', 'ap-southeast-1', 'vnbizbucket', "minio:9000", 'http')
	->init_oauth_google('1065936865511-k69f16m125i0r2f5vdtc1dau7kcrm71f.apps.googleusercontent.com', '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')
	->init_redis('redis7')
	->use_namespaces()
;

vnbiz()
	->init_modules(
		'systemconfig',
		'user',
		'usermark',
		'comment',
		'tag',
		'review',
		'history',
		's3',
		'template',
		'email',
		'oauth',
		'notification',
		'redis',
		'monitor',
		'useractivity',
		'datascope'
	);

include_once(__DIR__ . "/../example/types.php");


vnbiz_model_add('testmodelb')
	->no_update()
	->no_delete()
	->ref('user_id', 'user')
	->ref('project_id', 'project')
	->back_ref_count('testmodela_count', 'testmodela', 'testmodelb_id')
	->string("name");

vnbiz_model_add('testhas')
	->has_v()
	->has_history()
	->has_trash()
	->has_reviews()
	->has_tags()
	->has_comments()
	->has_usermarks('like')
	->author()
;

vnbiz_model_add('testmodela')
	->ui([
		'icon' => 'person',
		'photo' => 'avatar',
		'title' => 'alias',
		'subtitle' => 'email'
	])
	->default([
		'string_1' => 'string_1_default'
	])
	->model_id('model_id_1', 'model_id_2')
	->string('string_1', 'string_2')
	->bool('bool_1', 'bool_2')
	->date('date_1', 'date_2')
	->datetime('datetime_1', 'datetime_2')
	->email('mail_1', 'mail_2')
	->slug('slug_1', 'slug_2')
	->text('text_1', 'text_2')
	->json('json_1', 'json_2')
	->int('int_1', 'int_2')
	->uint('uint_1', 'uint_2')
	->float('float_1', 'float_2')
	->enum('enum_1', ['value_1', 'value_2'], 'value_1')
	->status('status', [
		'status_1' => ['status_2'],
		'status_2' => ['status_1'],
		'status_3' => []
	], 'status_1')
	->require('string_1')
	->ref('testmodelb_id', 'testmodelb')
	->unique('require_1', ['string_1', 'int_1'])
	->author()
	->password('password_1', "password_2")
	->s3_image('image_1', [50], [100, 100])
	->s3_file('file_1')
	->index('index_name_1', ['string_1', 'int_1'])
	->text_search('text_1', 'text_2')

;


vnbiz()->start();
