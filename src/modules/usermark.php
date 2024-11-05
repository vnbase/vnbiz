<?php

use VnBiz\VnBizError;

function vnbiz_init_module_usermark()
{
	vnbiz_model_add('usermark')
		->model_name('model_name')
		->model_id('model_id')
		->string('mark_type')
		->author()
		->no_update()
		->require('created_by', 'mark_type', 'model_name', 'model_id')
		->db_before_create(function ($context) {
			vnbiz_assure_model_name_exists($context['model']['model_name']);
		})
		->unique('usermark_unique', ['model_name', 'model_id', 'mark_type', 'created_by'])
        ->write_permission('super', 'usermark_write')
		;

	// vnbiz_add_action('sql_gen_index', function (&$context) {
	// 	isset($context['sql']) ?: $context['sql'] = '';
	// 	$context['sql'] .= "
	// 		CREATE UNIQUE INDEX IF NOT EXISTS comment_usermark ON `usermark` (model_name, model_id, mark_type, created_by);
	// 	";
	// });
}

