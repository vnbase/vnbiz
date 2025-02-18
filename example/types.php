<?php

include_once __DIR__ . '/_base.php';

use VnBiz\VnBizError;

vnbiz_model_add('config')
	->s3_image('logo', [50], [200, 50], [50, 2000]);

vnbiz_model_add('project')
	->default([
		'phase' => 1
	])
	->ui([
		'icon' => 'deployed_code',
		'photo' => 'logo',
		'title' => 'name',
		'user_marks' => ['like' => 'favorite']
		// 'subtitle' => 'email'
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
	->has_trash()
	->no_delete()
	->has_usermarks('like')
	->has_comments()
	->has_tags()
	->author()
	->require('created_by', 'name')
	->back_ref_count('task_count', 'task', 'project_id', [])
	->back_ref_count('task_new_count', 'task', 'project_id', ['state' => 'new'])
	->back_ref_count('task_working_count', 'task', 'project_id', ['state' => 'working'])
	->text_search('name', 'description')
	->find_permission_or(['super', 'project_read'], function (&$context) {
		$user = vnbiz_user();
		if (!$user) {
			return;
		}

		$members = vnbiz_model_find('projectmember', [
			'user_id' => $user['id']
		], ['limit' => 1000]); //TODO: one user has only 1000 projects
		$project_ids = [];
		foreach ($members as $member) {
			$project_ids[] = $member['project_id'];
		}
		//todo: assure filter[project_id] & $project_ids matching
		$filter_ids = null;
		if (isset($context['filter'])) {
			if (isset($context['filter']['id'])) {
				$filter_ids = $context['filter']['id'];
			}
		} else {
			$context['filter'] = [];
		}
		if ($filter_ids === null || empty($filter_ids)) {
			$context['filter']['id'] = $project_ids;
			return true;
		}

		if (is_array($filter_ids)) {
			$not_members = [];
			foreach ($filter_ids as $filter_id) {
				if (!in_array($filter_id, $project_ids)) { // not in the member list
					$not_members[] = $filter_id;
				}
			}
			if (sizeof($not_members) > 0) {
				throw new VnBizError('You are not the projects member.', 'permission', ['id' => $not_members]);
			}
			return true;
		} else {
			foreach ($project_ids as $project_id) {
				if ($project_id == $filter_ids) {
					return true;
				}
			}
			// throw new VnBizError('You are not the project member. Id:' . $filter_ids, 'permission');
		}
		return true;
	})
	->db_after_commit_create(function (&$context) {
		$user = vnbiz_user();
		if (!$user) {
			return;
		}
		vnbiz_model_create('projectmember', [
			'user_id' => $user['id'],
			'project_id' => $context['model']['id'],
			'role' => 'owner'
		]);
	});;

vnbiz_model_add('projectmember')
	->author()
	->enum('role', ['owner', 'worker'], 'owner')
	->ref('user_id', 'user')
	->ref('project_id', 'project')
	->require('created_by')
	->unique('unique_member', ['user_id', 'project_id'])
	->write_permission_or(['super', 'project_write'], function (&$context) {
		$project_id = $context['model']['project_id'];

		// no member
		$count = vnbiz_model_count('projectmember', [
			'project_id' => $project_id
		]);
		if ($count === 0) {
			return true;
		}

		// the user is the owner		
		$user = vnbiz_user();
		if (!$user) {
			return false;
		}
		$count = vnbiz_model_count('projectmember', [
			'project_id' => $project_id,
			'user_id' => $user['id'],
			'role' => 'owner'
		]);
		return $count > 0;
	})
;



vnbiz_model_add('task')
	->ui([
		'icon' => 'task',
		// 'photo' => 'image',
		'title' => 'name',
		'subtitle' => 'status'
	])
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
	->has_v()
	->require('created_by', 'name', 'project_id')
	->has_history()
	->read_permission_or(['super', 'project_read'], function (&$context) {
		$user = vnbiz_user();
		if (!$user) {
			return false;
		}

		if (isset($context['filter']) && isset($context['filter']['project_id'])) {
			$project_id_or_array = $context['filter']['project_id'];
			$count = vnbiz_model_count('projectmember', [
				'project_id' => $project_id_or_array,
				'user_id' => $user['id']
			]);
			if ($count >= (is_array($project_id_or_array) ? sizeof($project_id_or_array) : 1)) {
				return true;
			}
			throw new VnBizError("You are not allowed to access the projects", 'permission', ['project_id' => $count]);
		}
		return false;
	})
	->write_permission_or(['super', 'project_write'], function (&$context) {
		$project_id = $context['model']['project_id'];

		// the user is the owner		
		$user = vnbiz_user();
		if (!$user) {
			return false;
		}
		$count = vnbiz_model_count('projectmember', [
			'project_id' => $project_id,
			'user_id' => $user['id']
		]);
		return $count > 0;
	})
	->text_search('name', 'description');
// -> commentable()


// web

vnbiz_model_add('website')
	->text('title', 'description')
	->s3_image('logo', [32], [50], [200])
	->author()
	->require('title', 'created_by')
;

vnbiz_model_add('webdomain')
	->text('domain')
	->bool('verified')
	->author()
	->ref('website_id', 'website')
	->require('domain', 'created_by', 'website_id')
	->web_before_create(function (&$context) {
		$context['verified'] = false;
	})
	->web_before_create(function (&$context) {
		unset($context['verified']);
	})
	->index('domain', ['verified'])
;

vnbiz_model_add('weblayout')
	->ref('website_id', 'website')
	->string('name')
	->require('name', 'website_id')
	->text('content')
;

vnbiz_model_add('webblock')
	->ref('website_id', 'website')
	->string('name')
	->text('content')
	->unique('unique_name', ['website_id', 'name'])
;

vnbiz_model_add('webpost')
	->string('type', 'language')
	->slug('slug')
	->s3_image('logo', [300], [500])
	->ref('parent_id', 'webpost')
	->ref('weblayout_id', 'weblayout')
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

// marketing



// e-commerce



// crm & marketing

// vnbiz_model_add('customer')
// 	->
// 	;


// vnbiz_model_add('order')
// 	->has_datascope()
// 	->ref('contact_id', 'contact')
// 	->ref('user_id', 'user')
// 	->text('note')
// 	->author()
// 	;