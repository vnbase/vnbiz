<?php

use VnBiz\VnBizError;

function vnbiz_init_module_comment()
{
	vnbiz_model_add('comment')
		->ui([
			'icon' => 'comment',
			'photo' => 'image',
			'title' => 'title',
			'subtitle' => 'number_of_subcomments'
		])
		->model_name('model_name')
		->model_id('model_id')
        ->s3_image('image', [50], [800])
		->s3_file('file')
		->string('title')
		->text('content')
		->has_usermarks('like')
		->ref('parent_comment_id', 'comment')
		->back_ref_count('number_of_subcomments', 'comment', 'parent_comment_id')
		->author()
		->require(/*'created_by', */'model_name', 'model_id', 'content', 'created_by')
		->index('ref_model_index', ['model_name', 'model_id'])
		->db_before_create(function (&$context) {
			$model_name = $context['model']['model_name'];
			$id = $context['model']['model_id'];

			$c = [
				'model_name' => $model_name,
				'filter' => [
					'id' => $id
				],
				'meta' => [
					'skip_db_action' => true
				]
			];

			vnbiz_do_action('model_find', $c);

			if (sizeof($c['models']) == 0) {
				throw new VnbizError("Comment's model id doesn't exist, $model_name.$id", 'invalid_model');
			}

			if (!isset($c['models'][0]['comment_enable']) || $c['models'][0]['comment_enable'] == false) {
				throw new VnbizError("Model $model_name.$id comment_enable = 0", 'no_comment');
			}
		});
}
