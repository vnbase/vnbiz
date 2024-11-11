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
            L()->debug('model_create', $context);

            $this->actions()->do_action('db_before_create', $context);

            if (!isset($context['model_name'])) {
                throw new VnBizError('Missing model_name', 'invalid_context');
            }
            if (!isset($context['model']) || !is_array($context['model'])) {
                throw new VnBizError('Missing model', 'invalid_context');
            }

            $model_name = $context['model_name'];
            $schema = $this->models()[$model_name]->schema();



            $in_trans = vnbiz_get_key($context, 'in_trans', false);
            if (!$in_trans) {
                $context['in_trans'] = true;
            }

            !$in_trans && R::begin();
            try {

                $this->actions()->do_action("db_before_create_$model_name", $context);

                $row = R::dispense($model_name);
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
            L()->debug('model_update', $context);

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

            $in_trans = vnbiz_get_key($context, 'in_trans', false);
            if (!$in_trans) {
                $context['in_trans'] = true;
            }

            !$in_trans && R::begin();
            try {
                $find_context = [
                    'model_name' => $context['model_name'],
                    'filter' => $context['filter'],
                    'meta' => [
                        'limit' => 1
                    ],
                    'in_trans' => true,
                    'sql_lock_query' => 'FOR UPDATE'
                ];

                vnbiz_do_action('model_find', $find_context);
                $context['sql_query_conditions'] = $find_context['sql_query_conditions'];
                $context['sql_query_params'] = $find_context['sql_query_params'];

                $rows = isset($find_context['models']) ? $find_context['models'] : [];

                if (sizeof($rows) == 0) {
                    throw new VnBizError('Model do not exist', 'model_not_found');
                }

                $old_model = $rows[0];
                $context['old_model'] = $old_model;
                $context['old_model']['@model_name'] = $model_name;

                $ns = isset($old_model['ns']) ? $old_model['ns'] : '0';
                $id = $context['old_model']['id'];
                $cached_key = "$ns:$model_name:$id";
                vnbiz_redis_del($cached_key);

                $skip_db_actions ?: $this->actions()->do_action("db_before_update_$model_name", $context);

                $row = R::dispense($model_name);
                $row->id = $old_model['id'];
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

    public function add_action_model_sql_gen_update()
    {
        vnbiz_add_action('sql_gen_update_model', function (&$context) {
            if (!isset($context['model_name'])) {
                throw new VnBizError('Missing model_name', 'invalid_context');
            }
            if (!isset($context['model'])) {
                throw new VnBizError('Missing model', 'invalid_context');
            }
            if (!isset($context['old_model'])) {
                throw new VnBizError('Missing old_model', 'invalid_context');
            }
            if (!isset($context['filter']) || !isset($context['filter']['id'])) {
                throw new VnBizError('Missing filter[id]', 'invalid_context');
            }

            $model_name = $context['model_name'];
            $schema = $this->models[$context['model_name']]->schema();

            $row = R::dispense($model_name);
        });
    }

    public function add_action_model_sql_gen_filter()
    {
        vnbiz_add_action('sql_gen_filter', function (&$context) {
            if (!isset($context['model_name'])) {
                throw new VnBizError('Missing model_name', 'invalid_context');
            }
            if (!isset($context['sql_query_conditions'])) {
                $context['sql_query_conditions'] = [];
            }
            if (!isset($context['sql_query_params'])) {
                $context['sql_query_params'] = [];
            }
            $sql_query_conditions = &$context['sql_query_conditions'];
            $sql_query_params = &$context['sql_query_params'];

            $filter = &$context['filter'];
            $model_name = $context['model_name'];
            $meta = vnbiz_get_var($context['meta'], []);
            $schema = $this->models[$context['model_name']]->schema();
            $text_search = vnbiz_get_var($meta['text_search'], null);


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
            }
        });
    }

    public function add_action_model_sql_gen_order()
    {
        vnbiz_add_action('sql_gen_order', function (&$context) {
            if (!isset($context['model_name'])) {
                throw new VnBizError('Missing model_name', 'invalid_context');
            }

            $model_name = $context['model_name'];
            $order_query = [];

            $meta = vnbiz_get_var($context['meta'], []);
            $order = vnbiz_get_var($meta['order'], []);

            $field_names = vnbiz_get_model_field_names($model_name);

            foreach ($field_names as $field_name) {
                if (isset($order[$field_name])) {
                    $order_query[] = $order[$field_name] > 0 ? $field_name : $field_name . ' DESC';
                }
            }

            $order_query = join(', ', $order_query);
            $order_query = $order_query ? ' ORDER BY ' . $order_query : '';

            $context['sql_query_order'] = $order_query;
        });
    }

    public function db_fetch(&$context)
    {
        $model_name = $context['model_name'];
        $meta = vnbiz_get_var($context['meta'], []);
        $sql_query_conditions = &$context['sql_query_conditions'];
        $sql_query_params = &$context['sql_query_params'];
        $sql_query_order = vnbiz_get_var($context['sql_query_order'], '');
        $lock_query = vnbiz_get_var($context['sql_lock_query'], '');

        $limit = vnbiz_get_var($meta['limit'], 100);
        $offset = vnbiz_get_var($meta['offset'], 0);

        if ($limit > 1000) {
            throw new VnBizError('Limit too large', 'unsupported');
        }

        // fetch from cache when filter by [id] or [id, ns] and no other conditions
        if (
            (
                isset($context['filter']) &&  (
                    (sizeof($context['filter']) == 1 && isset($context['filter']['id']) && !empty($context['filter']['id']))
                    || (sizeof($context['filter']) == 2 && isset($context['filter']['id']) && !empty($context['filter']['id']) && isset($context['filter']['ns']))
                )
            )
            && strlen($sql_query_order) == 0 && strlen($lock_query) == 0
        ) {
            $ns = isset($context['filter']['ns']) ? $context['filter']['ns'] : '0';
            if (is_array($ns)) {
                throw new VnBizError('Filter[ns] must be number or array of numbers', 'invalid_context', ['filter' => $context['filter']], null, 500);
            }
            $ids = $context['filter']['id'];
            if (!is_iterable($ids)) {
                $ids = [$ids];
            }
            if (!is_array($ids)) {
                throw new VnBizError('Filter[id] must be number or array of numbers', 'invalid_context', ['filter' => $context['filter']], null, 500);
            }
            // remove duplicate ids && null ids
            // $ids = array_values(array_filter(array_unique($ids), function ($id) {
            //     return is_numeric($id);
            // }));
            // do fetch from cache. If don't have cache, fetch from db and save to cache

            $cached_keys = array_map(function ($id) use ($model_name, $ns) {
                return "$ns:$model_name:$id";
            }, $ids);
            $cache_result = vnbiz_redis_get_arrays($cached_keys);
            $cached_rows = [];

            // load missed rows from db
            $missed_ids = [];
            foreach ($ids as $index=>$id) {
                if (!$cache_result[$index]) {
                    $missed_ids[] = $id;
                } else {
                    $cached_rows[] = $cache_result[$index];
                }
            }

            // TODO: $limit, $offset is not supported?

            if (sizeof($missed_ids) == 0) {
                L()->debug('db_fetch(): all from catch', $missed_ids);
                return $cached_rows;
            }

            L()->debug('db_fetch(): more from sql: ', $missed_ids);

            // L()->debug('db_fetch() R::find() ' . $model_name, $missed_ids);
            $rows = R::find($model_name, 'id IN (' . R::genSlots($missed_ids) . ')', $missed_ids);
            $rows = R::beansToArray($rows);
            foreach ($rows as $row) {
                $id = $row['id'];
                $key = "$ns:$model_name:$id";
                vnbiz_redis_set_array($key, $row);
            }

            return array_merge($cached_rows, $rows);
        }

        $sql_query_conditions = join(' AND ', $sql_query_conditions);
        $sql_query = $sql_query_conditions . ' ' . $sql_query_order . ' LIMIT ? OFFSET ? ' . $lock_query;
        $sql_params = array_merge($sql_query_params, [$limit, $offset]);

        $context['sql_query'] = $sql_query;
        $context['sql_params'] = $sql_params;

        L()->debug("db_fetch(): fetch from sql: $model_name, $sql_query", $sql_params);

        $rows = R::find($model_name, $sql_query, $sql_params);
        $rows = R::beansToArray($rows);

        // do cache thr rows
        $ns = isset($context['filter']['ns']) ? $context['filter']['ns'] : '0';
        foreach ($rows as $row) {
            $id = $row['id'];
            $key = "$ns:$model_name:$id";
            vnbiz_redis_set_array($key, $row);
        }
        return $rows;
    }

    private function add_action_model_find()
    {
        $this->actions()->add_action_one('model_find', function (&$context) {
            L()->debug('model_find ', $context);

            $meta = vnbiz_get_var($context['meta'], []);
            $skip_db_actions = vnbiz_get_var($meta['skip_db_actions'], false);

            $skip_db_actions ?: $this->actions()->do_action('db_before_find', $context);

            if (!isset($context['model_name'])) {
                throw new VnBizError('Missing model_name', 'invalid_context');
            }

            $model_name = $context['model_name'];

            vnbiz_do_action('sql_gen_filter', $context);
            vnbiz_do_action('sql_gen_order', $context);

            $skip_db_actions ?: $this->actions()->do_action("db_before_find_$model_name", $context);

            $ref = vnbiz_get_var($meta['ref'], false);
            $count = vnbiz_get_var($meta['count'], false);


            $rows = $this->db_fetch($context);
            $context['models'] = [];

            foreach ($rows as $row) {
                $row['@model_name'] = $model_name;
                $c = [
                    'model_name' => $model_name,
                    'model' => $row
                ];

                $skip_db_actions ?: $this->actions()->do_action("db_after_fetch_$model_name", $c);

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

                        $skip_db_actions ?: $this->actions()->do_action("db_after_fetch_$ref_model_name", $c);

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
                $sql_query_conditions = &$context['sql_query_conditions'];
                $sql_query_params = &$context['sql_query_params'];
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
            L()->debug('model_delete', $context);

            $this->actions()->do_action('db_before_delete', $context);

            if (!isset($context['model_name'])) {
                throw new VnBizError('Missing model_name', 'invalid_context');
            }

            if (!isset($context['filter']) || $context['filter'] == null) {
                throw new VnBizError('Missing filter', 'invalid_context');
            }

            $model_name = $context['model_name'];
            $field_names = vnbiz_get_model_field_names($model_name);

            $in_trans = vnbiz_get_key($context, 'in_trans', false);
            if (!$in_trans) {
                $context['in_trans'] = true;
            }

            !$in_trans && R::begin();
            try {
                $find_context = [
                    'model_name' => $context['model_name'],
                    'filter' => $context['filter'],
                    'meta' => [
                        'limit' => 1
                    ],
                    'in_trans' => true,
                    'sql_lock_query' => 'FOR UPDATE'
                ];

                vnbiz_do_action('model_find', $find_context);
                $context['sql_query_conditions'] = $find_context['sql_query_conditions'];
                $context['sql_query_params'] = $find_context['sql_query_params'];

                $rows = isset($find_context['models']) ? $find_context['models'] : [];

                if (sizeof($rows) == 0) {
                    throw new VnBizError('Model do not exist', 'model_not_found');
                }

                $context['old_model'] = $rows[0];
                $context['old_model']['@model_name'] = $model_name;

                //TODO: refactor this
                // TODO: Why we can't use $context['fitler']['ns]
                $ns = isset($context['old_model']['ns']) ? $context['old_model']['ns'] : '0';
                $id = $context['old_model']['id'];
                $cached_key = "$ns:$model_name:$id";
                vnbiz_redis_del($cached_key);

                // foreach ($field_names as $field_name) {
                //     if (isset($model[$field_name])) {
                //         $row[$field_name] = $model[$field_name];
                //     }
                // }

                $this->actions()->do_action("db_before_delete_$model_name", $context);
                R::trashBatch($model_name, vnbiz_get_ids($rows));
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
            L()->debug('model_count', $context);

            $meta = vnbiz_get_var($context['meta'], []);
            $this->actions()->do_action('db_before_count', $context);

            if (!isset($context['model_name'])) {
                throw new VnBizError('Missing model_name', 'invalid_context');
            }

            $model_name = $context['model_name'];

            $field_names = vnbiz_get_model_field_names($model_name);

            vnbiz_do_action('sql_gen_filter', $context);
            vnbiz_do_action('sql_gen_order', $context);

            $this->actions()->do_action("db_before_count_$model_name", $context);

            $sql_query_conditions = &$context['sql_query_conditions'];
            $sql_query_params = &$context['sql_query_params'];
            $lock_query = vnbiz_get_var($context['sql_lock_query'], '');


            $sql_query_conditions = join(' AND ', $sql_query_conditions);

            if ($sql_query_conditions) {
                $sql_query_conditions = ' WHERE ' . $sql_query_conditions;
            }

            $count = R::getCell('SELECT COUNT(*) FROM ' . $model_name . $sql_query_conditions . ' ' . $lock_query, $sql_query_params);
            $context['count'] = $count;;

            // if (isset($context['debug']) && $context['debug']) {
            //     if (!is_array($context['debug'])) {
            //         $context['debug'] = [];
            //     }
            //     $context['debug'][] = $context;
            //     $context['debug'][] = ['sql', $model_name, $sql_query_conditions, $sql_query_params];
            // }

            // $context['count'] = R::count($model_name, $sql_query_conditions, $sql_query_params);

            $this->actions()->do_action("db_after_count_$model_name", $context);

            $this->actions()->do_action('db_after_count', $context);
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
        $this->add_action_model_sql_gen_filter();
        $this->add_action_model_sql_gen_order();

        return $this;
    }
}
