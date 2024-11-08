<?php
// new trait named Model_permission
namespace VnBiz;

use Exception, R;
use RedBeanPHP\RedException\SQL as SQLException;
use SimpleXMLElement;
use VnBiz\Model;

trait VnBiz_sql
{
    private function add_action_model_create()
    {

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
    }
    private function add_action_model_update()
    {

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
    }

    private function add_action_model_find()
    {
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
            $sql_query_conditions = [];
            $sql_query_params = [];
            $order_query = [];

            $schema = $this->models[$model_name]->schema();

            if ($text_search && $schema->text_search) {
                $fields = array_map(function ($item) {
                    return '`' . $item . '`';
                }, $schema->text_search);
                $fields = join(',', $fields);
                $text_condition = "MATCH (" . $fields . ') AGAINST(?)';

                $sql_query_conditions[] = $text_condition;
                $sql_query_params[] = $text_search;
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
                                    $sql_query_params[] = $datascope . '%';
                                }
                            }
                            $sql_query_conditions[] = join('OR', $or_query);
                        } else {
                            $sql_query_conditions[] = "datascope LIKE ?";
                            $sql_query_params[] = $value . '%';
                        }

                        continue;
                    }

                    if (is_array($value)) {
                        if (isset($value[0])) {
                            $sql_query_conditions[] = "$field_name IN (" . R::genSlots($value) . ")";
                            array_push($sql_query_params, ...$value);
                        } else {
                            if (array_key_exists('$gt', $value)) {
                                $sql_query_conditions[] = "$field_name > ?";
                                $sql_query_params[] = $value['$gt'];
                            }
                            if (array_key_exists('$gte', $value)) {
                                $sql_query_conditions[] = "$field_name >= ?";
                                $sql_query_params[] = $value['$gte'];
                            }
                            if (array_key_exists('$lt', $value)) {
                                $sql_query_conditions[] = "$field_name < ?";
                                $sql_query_params[] = $value['$lt'];
                            }
                            if (array_key_exists('$lte', $value)) {
                                $sql_query_conditions[] = "$field_name <= ?";
                                $sql_query_params[] = $value['$lte'];
                            }
                        }
                    } else {
                        $sql_query_conditions[] = "$field_name=?";
                        $sql_query_params[] = $value;
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
                'sql_query_conditions' => &$sql_query_conditions,
                'sql_query_params' => &$sql_query_params
            ];

            vnbiz_do_action("db_before_find_exe_$model_name", $context);
            unset($context['db_context']);

            $sql_query_conditions = join(' AND ', $sql_query_conditions);

            $sql_query = $sql_query_conditions . $order_query . ' LIMIT ? OFFSET ?';

            $sql_params = array_merge($sql_query_params, [$limit, $offset]);

            if (isset($context['debug']) && $context['debug']) {
                if (!is_array($context['debug'])) {
                    $context['debug'] = [];
                }
                $context['debug'][] = $context;
                $context['debug'][] = ['sql', $model_name, $sql_query, $sql_params];
            }
            // printf("sql_query: %s %s\n", $sql_query, json_encode($sql_params));
            $rows = R::find($model_name, $sql_query, $sql_params);
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
                $number_of_rows = R::count($model_name, $sql_query_conditions, $sql_query_params);
                $context['meta']['count'] = $number_of_rows;
            }


            $skip_db_actions ?: $this->actions()->do_action("db_after_find_$model_name", $context);

            $skip_db_actions ?: $this->actions()->do_action('db_after_find', $context);
        });
    }

    private function add_action_model_delete()
    {

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
            $sql_query_conditions = [];
            $sql_query_params = [];

            $field_names = vnbiz_get_model_field_names($model_name);
            foreach ($field_names as $field_name) {
                if (isset($filter[$field_name])) {
                    $sql_query_conditions[] = "$field_name=?";
                    $sql_query_params[] = $filter[$field_name];
                }
            }

            $sql_query_conditions = join(' AND ', $sql_query_conditions) . ' LIMIT 1';

            $in_trans = vnbiz_get_key($context, 'in_trans', false);
            if (!$in_trans) {
                $context['in_trans'] = true;
            }

            !$in_trans && R::begin();
            try {
                $rows = R::find($model_name, $sql_query_conditions, $sql_query_params);

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
    }
    private function add_action_model_count()
    {
        $this->actions()->add_action_one('model_count', function (&$context) {
            $meta = vnbiz_get_var($context['meta'], []);
            $this->actions()->do_action('db_before_count', $context);

            if (!isset($context['model_name'])) {
                throw new VnBizError('Missing model_name', 'invalid_context');
            }

            $model_name = $context['model_name'];
            $filter = vnbiz_get_var($context['filter'], []);

            $text_search = vnbiz_get_var($meta['text_search'], null);
            $sql_query_conditions = [];
            $sql_query_params = [];

            $schema = $this->models[$model_name]->schema();

            if ($text_search && $schema->text_search) {
                $fields = array_map(function ($item) {
                    return '`' . $item . '`';
                }, $schema->text_search);
                $fields = join(',', $fields);
                $text_condition = "MATCH (" . $fields . ') AGAINST(?)';

                $sql_query_conditions[] = $text_condition;
                $sql_query_params[] = $text_search;
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
                                    $sql_query_params[] = $datascope . '%';
                                }
                            }
                            $sql_query_conditions[] = join('OR', $or_query);
                        } else {
                            $sql_query_conditions[] = "datascope LIKE ?";
                            $sql_query_params[] = $value . '%';
                        }

                        continue;
                    }

                    if (is_array($value)) {
                        if (isset($value[0])) {
                            $sql_query_conditions[] = "$field_name IN (" . R::genSlots($value) . ")";
                            array_push($sql_query_params, ...$value);
                        } else {
                            if (array_key_exists('$gt', $value)) {
                                $sql_query_conditions[] = "$field_name > ?";
                                $sql_query_params[] = $value['$gt'];
                            }
                            if (array_key_exists('$gte', $value)) {
                                $sql_query_conditions[] = "$field_name >= ?";
                                $sql_query_params[] = $value['$gte'];
                            }
                            if (array_key_exists('$lt', $value)) {
                                $sql_query_conditions[] = "$field_name < ?";
                                $sql_query_params[] = $value['$lt'];
                            }
                            if (array_key_exists('$lte', $value)) {
                                $sql_query_conditions[] = "$field_name <= ?";
                                $sql_query_params[] = $value['$lte'];
                            }
                        }
                    } else {
                        $sql_query_conditions[] = "$field_name=?";
                        $sql_query_params[] = $value;
                    }
                }

                if (isset($order[$field_name])) {
                    $order_query[] = $order[$field_name] > 0 ? $field_name : $field_name . ' DESC';
                }
            }

            $this->actions()->do_action("db_before_find_$model_name", $context);


            $context['db_context'] = [
                'sql_query_conditions' => &$sql_query_conditions,
                'sql_query_params' => &$sql_query_params
            ];

            vnbiz_do_action("db_before_count_exe_$model_name", $context);
            unset($context['db_context']);

            $sql_query_conditions = join(' AND ', $sql_query_conditions);

            $this->actions()->do_action("db_before_count_$model_name", $context);

            if (isset($context['debug']) && $context['debug']) {
                if (!is_array($context['debug'])) {
                    $context['debug'] = [];
                }
                $context['debug'][] = $context;
                $context['debug'][] = ['sql', $model_name, $sql_query_conditions, $sql_query_params];
            }

            $context['count'] = R::count($model_name, $sql_query_conditions, $sql_query_params);

            $this->actions()->do_action("db_after_count_$model_name", $context);

            $this->actions()->do_action('db_after_count', $context);
        });
    }

    public function add_action_model_sql_filter() {
        vnbiz_add_action('db_before_find_exe', function (&$context) {
            $db_context = vnbiz_get_var($context['db_context'], []);
            $sql_query_conditions = vnbiz_get_var($db_context['sql_query_conditions'], []);
            $sql_query_params = vnbiz_get_var($db_context['sql_query_params'], []);

            $sql_query = $sql_query_conditions . ' LIMIT ? OFFSET ?';

            $sql_params = array_merge($sql_query_params, [$limit, $offset]);

            $context['sql_query'] = $sql_query;
            $context['sql_params'] = $sql_params;
        });
    }

    public function init_db_mysql($servername = 'localhost', $username = "", $password = "", $dbname = '')
    {
        R::setup("mysql:host=$servername;dbname=$dbname", $username, $password); //for both mysql or mariaDB
        R::freeze(true); //will freeze redbeanphp

        $this->add_action_model_create();
        $this->add_action_model_update();
        $this->add_action_model_find();
        $this->add_action_model_delete();
        $this->add_action_model_count();
        $this->add_action_model_sql_filter();

        return $this;
    }
}
