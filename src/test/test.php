<?php

// include(__DIR__ . "/../../../vendor/autoload.php");
// require_once '/var/www/vendor/autoload.php';
include(__DIR__ . "/../vnbiz.php");


vnbiz()
	->init_db_mysql('mysql8', 'root', 'rootpass', 'vnbiz_dev')
	->init_aws('minioroot', 'rootpass', 'ap-southeast-1', 'vnbizbucket', "minio:9000", 'http');

vnbiz_model_add('config')
	->s3_image('logo', [50], [200, 50], [50, 2000]);

vnbiz_model_add('project')
	->default([
		'phase' => 1
	])
	->string('name')
	->text('description')
	->status('status', [
		'draft' => ['active', 'deleted'],
		'active' => ['deleted'],
		'deleted' => ['active']
	], 'draft')
	->int('phase')
	->s3_image('logo', [300], [800])
	// ->no_delete()
	->has_usermarks('like')
	->has_comments()
	->has_tags()
	->author()
	->require('created_by', 'name')
	->back_ref_count('task_count', 'task', 'project_id', [])
	->back_ref_count('task_new_count', 'task', 'project_id', ['state' => 'new'])
	->back_ref_count('task_working_count', 'task', 'project_id', ['state' => 'working'])
    ->text_search('name', 'description')
    ;

vnbiz_model_add('member')
	->ref('user_id', 'user')
	->ref('project_id', 'project')
	->enum('role', ['owner', 'worker'], 'owner')
	->author()
	->require('created_by')
	// ->default_filter(function () {
	// 	$user = vnbiz_user();
	// 	if (!$user) {
	// 		throw new VnBizError('Login required', 'permission');
	// 	}

	// 	if (vnbiz_user_has_permissions('super')) {
	// 		return [];
	// 	}

	// 	return [
	// 		'user_id' => $user['id']
	// 	];
	// })
;



vnbiz_model_add('task')
	->string('name')
	->text('description')
	->int('phase')
	->datetime('due_date')
	->ref('project_id', 'project')
	->ref('parent_id', 'task')
	->ref('worker_id', 'user')
	->back_ref_count('subtask_count', 'task', 'parent_id')
	->enum('state', ['new', 'working', 'on_hold', 'pre_review', 'review', 'done'], 'new')
	->status('status', [
		'draft' => ['active', 'deleted'],
		'active' => ['deleted'],
		'deleted' => ['active']
	], 'draft')
	->datetime('working_at', 'on_hold_at', 'pre_preview_at', 'review_at', 'done_at')
	->no_delete()
	->author()
	->has_tags()
	->db_before_update(function (&$context) {
		$old_model = &$context['old_model'];
		$model = &$context['model'];
		if (isset($model['state']) && $model['state'] != 'new') {
			$model[$model['state'] . '_at'] = date("Y-m-d H:i:s");
		}
	})
	->has_usermarks('like')
	->require('created_by', 'name', 'project_id', 'created_by')
    ->has_history()
    ->text_search('name', 'description');
// -> commentable()


// site

vnbiz_model_add('website')
	->text('title')
	->author()
	->require('title', 'created_by')
	;

vnbiz_model_add('webpost')
	->string('type')
	->slug('slug')
	->ref('parent_id', 'webpost')
	// ->md5_of('slugmd5', ['slug'])
	->enum('status', ['draft', 'review', 'public'], 'draft')
	->text('title', 'description', 'content')
	->has_usermarks('like')
	->has_comments()
	->has_tags()
	->author()
	->has_tags()
	->has_history()
	->require('title', 'created_by')
    ->text_search('title', 'description', 'content');
	// ->index('uniqueslugmd5', ['slugmd5'])
	;
vnbiz()->start();
