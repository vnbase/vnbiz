<?php

use VnBiz\VnBizError;

function vnbiz_init_module_tag()
{
	vnbiz_model_add('tag')
		->string('name')
		->author()
		->require('name')
        ->text_search('name')
		->unique("unique_tag_name", ['name']);

	vnbiz_model_add('modeltag')
		->model_name('model_name')
		->model_id('model_id')
		->string('tag_id')
		->author()
		->require('model_name', 'model_id', 'tag_id')
		->unique("unique_modeltag", ['model_name', 'model_id', 'tag_id'])
        ->read_permission('super', 'tag_read')
        ->write_permission('super', 'tag_write')
		;


	// vnbiz_add_action('sql_gen_index', function (&$context) {
	// 	isset($context['sql']) ?: $context['sql'] = '';
	// 	$context['sql'] .= "
	// 		CREATE UNIQUE INDEX IF NOT EXISTS tag_unique ON `tag` (`name`);
	// 	";
	// });
}

function vnbiz_tag_to_id($tag)
{
	$tag = trim($tag);
	if (strlen($tag) == 0) {
		throw new VnbizError("Tag can't be empty");
	}
	$tag = mb_strtolower($tag);
	//TODO: optimize this
	try {
		$c = [
			'name' => $tag
		];
		vnbiz_model_create('tag', $c, true);
	} catch (Exception $e) {
		// trigger_error($e);
	}

	$model = R::findOne('tag', ' name = ? ', [$tag]);

	return $model->id;
}

function vnbiz_tags_to_ids($tags)
{
	return array_map(function ($tag) {
		return vnbiz_tag_to_id($tag);
	}, $tags);
}

function vnbiz_model_set_tags($model_name, $model_id, $tags)
{
	vnbiz_assure_valid_name($model_name);
	$tag_ids = vnbiz_tags_to_ids($tags);

	//TODO: optimize this
	foreach ($tag_ids as $tag_id) {
		try {
			$c = [
				'model_name' => $model_name,
				'model_id' => strval($model_id),
				'tag_id' => strval($tag_id)
			];
			vnbiz_model_create('modeltag', $c, true);
		} catch (Exception $e) {
			// trigger_error($e);
		}
	}
}

function vnbiz_model_remove_tags($model_name, $model_id, $tags)
{
	vnbiz_assure_valid_name($model_name);

	//TODO: optimize this
	$tag_ids = vnbiz_tags_to_ids($tags);
	foreach ($tag_ids as $tag_id) {
		$c = [
			'model_name' => $model_name,
			'model_id' => strval($model_id),
			'tag_id' => strval($tag_id)
		];
		vnbiz_model_delete('modeltag', $c, true);
	}
}

function vnbiz_model_remove_all_tags($model_name, $model_id)
{
	vnbiz_assure_valid_name($model_name);

	//TODO: optimize this
	R::exec('DELETE FROM modeltag WHERE model_name=? AND model_id=?', [$model_name, $model_id]);
}

function vnbiz_model_get_tags($model_name, $model_id)
{
	$tags = R::getAll('SELECT tag.name as name FROM tag INNER JOIN modeltag ON modeltag.tag_id = tag.id WHERE modeltag.model_name=? AND modeltag.model_id=?', [$model_name, $model_id]);
	return array_map(function ($row) {
		return $row['name'];
	}, $tags);
}
