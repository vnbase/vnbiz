<?php

use VnBiz\VnBizError;

function vnbiz_get_var(&$var, $default = null)
{
    return isset($var) ? $var : $default;
}
function vnbiz_get_key(&$var, $key, $default = null)
{
    if (isset($var) && isset($var[$key]) && $var[$key] != null) {
        return $var[$key];
    }
    return $default;
}

function vnbiz_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}

function vnbiz_array_contains_array($model, $filter) {
    foreach($filter as $key=>$value) {
        if (!isset($model[$key])) {
            return false;
        }
        if ($model[$key] != $value) {
            return false;
        }
    }
    return true;
}

function vnbiz_now() {
    date_default_timezone_set("UTC");
    return  date("Y-m-d H:i:s");
}
function vnbiz_html($text) {
	return htmlspecialchars($text);
}

global $VNBIZ_HASH_ID_INSTANCE;
$VNBIZ_HASH_ID_INSTANCE = new \Hashids\Hashids('VnBizS3cr3t',  10, 'abcdefghijklmnopqrstuvwxyz');
function vnbiz_encrypt_id($id) {
    global $VNBIZ_HASH_ID_INSTANCE;
    if (is_numeric($id)) {
        return $VNBIZ_HASH_ID_INSTANCE->encode($id);;
    }
    return $id;
}
function vnbiz_encrypt_ids($ids) {
    global $VNBIZ_HASH_ID_INSTANCE;
    if (is_array($ids)) {
    	$arr = [];
    	foreach($ids as $id) {
    		$arr[] = vnbiz_encrypt_id($id);
    	}
    	return $arr;
    }
    return $ids;
}

function vnbiz_decrypt_id($id) {
    global $VNBIZ_HASH_ID_INSTANCE;
    if (is_numeric($id)) {
        return $id;
    }
    $result = $VNBIZ_HASH_ID_INSTANCE->decode($id);
    if (sizeof($result) == 0) {
        throw new VnBizError("Invalid id", 'invalid_id', ['id' => $id]);
    }
    return $result[0] ?? null;
}

function vnbiz_decrypt_ids($ids) {
    global $VNBIZ_HASH_ID_INSTANCE;
    if (is_array($ids)) {
    	$arr = [];
    	foreach($ids as $id) {
    		$arr[] = vnbiz_decrypt_id($id);
    	}
    	return $arr;
    }
    return $ids;
}

function vnbiz()
{
    return VnBiz\VnBiz::instance();
}

function vnbiz_model($model_name)
{
    $models = vnbiz()->models();
    if (!isset($models[$model_name])) {
        throw new VnBiz\VnBizError('Invalid model name', 'invalid_model_name');
    }
    return $models[$model_name];
}

function vnbiz_model_add($model_name)
{
    return vnbiz()->add_model($model_name);
}

function vnbiz_get_model_field_names($model_name)
{
    $models = vnbiz()->models();

    if (!isset($models[$model_name])) {
        return [];
    }

    return array_keys($models[$model_name]->get_schema_details());
}

function vnbiz_handle_restful()
{
    return vnbiz()->handle_restful();
}
function vnbiz_handle_restful_xml()
{
    return vnbiz()->handle_restful_xml();
}

// function vnbiz_model_name_exists($model_name) {

// }

function vnbiz_assure_model_name_exists($model_name)
{
    $models = vnbiz()->models();
    if (!isset($models[$model_name])) {
        throw new VnBiz\VnBizError('Invalid model name', 'invalid_model_name');
    }
}

function vnbiz_do_action($name, &$context)
{
    return vnbiz()->actions()->do_action($name, $context);
}

function vnbiz_add_action($name, $func)
{
    return vnbiz()->actions()->add_action($name, $func);
}

function vnbiz_model_create($model_name, $model, $in_trans = false)
{
    vnbiz_assure_model_name_exists($model_name);

    $context = [
        // 'model_name' => $model_name,
        'model' => $model,
        'in_trans' => $in_trans
    ];
    vnbiz_do_action("model_create_$model_name", $context);
    return $context['model'];
}

function vnbiz_model_search(&$context)
{
    $model_name = $context['model_name'];
    vnbiz_assure_model_name_exists($model_name);
    vnbiz_do_action("model_find_$model_name", $context);
}

function vnbiz_model_count($model_name, $filter = [])
{
    vnbiz_assure_model_name_exists($model_name);

    $context = [
        'filter' => $filter
    ];
    vnbiz_do_action("model_count_$model_name", $context);

    return $context['count'];
}

function vnbiz_model_find($model_name, $filter = [], $meta = ['limit' => 10, 'offset' => 0])
{
    vnbiz_assure_model_name_exists($model_name);

    $context = [
        // 'model_name' => $model_name,
        'filter' => $filter,
        'meta' =>  $meta
    ];
    vnbiz_do_action("model_find_$model_name", $context);
    return $context['models'];
}

function vnbiz_model_find_one($model_name, $filter = [], $meta = [])
{
    vnbiz_assure_model_name_exists($model_name);
    $meta['limit'] = 1;
    $meta['offset'] = 0;
    $rows = vnbiz_model_find($model_name, $filter, $meta);
    if (sizeof($rows) > 0) {
        return $rows[0];
    }
    return null;
}

function vnbiz_model_update($model_name, $filter, $model, $meta = [], $in_trans = false)
{
    vnbiz_assure_model_name_exists($model_name);
    $context = [
        // 'model_name' => $model_name,
        'filter' => $filter,
        'model' => $model,
        'meta' => $meta,
        'in_trans' => $in_trans
    ];
    vnbiz_do_action("model_update_$model_name", $context);

    return $context['old_model'];
}

function vnbiz_model_delete($model_name, $filter, $in_trans = false)
{
    vnbiz_assure_model_name_exists($model_name);
    $context = [
        // 'model_name' => $model_name,
        'filter' => $filter,
        'in_trans' => $in_trans
    ];
    vnbiz_do_action("model_delete_$model_name", $context);
    return $context['old_model'];
}

function vnbiz_user()
{
    return isset($GLOBALS['vnbiz_user']) ? $GLOBALS['vnbiz_user'] : null;
}

function vnbiz_user_has_permissions()
{
    if (isset($GLOBALS['vnbiz_user_permissions'])) {
        foreach (func_get_args() as $per) {
            // var_dump($GLOBALS['vnbiz_user_permissions']);
            if (isset($GLOBALS['vnbiz_user_permissions'][$per])) {
                return true;
            }
        }
    }
    return false;
}

function vnbiz_assure_user_has_permissions(...$permissions)
{
    if (vnbiz_user_has_permissions(...$permissions)) {
        return;
    }
    
    throw new VnBizError("Require permissions: " . implode(',', $permissions), 'permission');
}

function vnbiz_do_service($service_name, $params = [])
{
    $context = [
        'params' => $params
    ];
    vnbiz_do_action("service_$service_name", $context);

    if (!isset($context['code'])) {
        throw new VnBiz\VnBizError("Service not found service_$service_name", 'invalid_service_name');
    }

    if (!isset($context['code']) || $context['code'] !== 'success') {
        throw new VnBiz\VnBizError("Service service_$service_name returns error", $context['code']);
    }

    return vnbiz_get_var($context['models'], null);
}

function vnbiz_str_starts_with($string, $startString)
{
    $len = strlen($startString);
    return (substr($string, 0, $len) === $startString);
}


function vnbiz_assure_valid_name($name)
{
    if (!preg_match("/^([a-z][a-z0-9_]*)$/", $name)) {
        throw new Error("Invalid name `$name`");
    }
}

function vnbiz_http_request($method, $path, $query = [], $body = false, $header = []) {
    $curl  = curl_init();
    curl_setopt($curl, CURLOPT_URL, $path . http_build_query($query));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

    switch ($method) {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            break;
            // default:
            //      curl_setopt($curl, CURLOPT_G, 1);
    }
    if ($body) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
    }

    $result = curl_exec($curl);
    curl_close($curl);

    return $result;
}

function vnbiz_http_request_json($method, $path, $query = [], $body = false, $header = []) {
    $result = vnbiz_http_request($method, $path, $query, $body, $header);
    return json_decode($result);
}

function vnbiz_web_model_create($model_name, $model) {
    vnbiz_assure_model_name_exists($model_name);

    $context = [
        'model_name' => $model_name,
        'model' => $model
    ];

    vnbiz_do_action('web_model_create', $context);

    return $context['model'];
}
function vnbiz_web_model_update($model_name, $filter, $model) {
    vnbiz_assure_model_name_exists($model_name);

    $context = [
        'model_name' => $model_name,
        'filter' => $filter,
        'model' => $model
    ];

    vnbiz_do_action('web_model_update', $context);

    return $context['old_model'];
}
function vnbiz_web_model_delete($model_name, $filter) {
    vnbiz_assure_model_name_exists($model_name);

    $context = [
        'model_name' => $model_name,
        'filter' => $filter
    ];

    vnbiz_do_action('web_model_delete', $context);

    return $context['old_model'];
    
}

function vnbiz_web_model_find($model_name, $filter = [], $meta = []) {
    vnbiz_assure_model_name_exists($model_name);

    $context = [
        'model_name' => $model_name,
        'filter' => $filter,
        'meta' => $meta
    ];

    vnbiz_do_action('web_model_find', $context);

    return $context['models'];
}

function vnbiz_web_model_search(&$context) {
    $model_name = $context['model_name'];
    vnbiz_assure_model_name_exists($model_name);
    vnbiz_do_action('web_model_find', $context);
}

function vnbiz_web_model_find_one($model_name, $filter = [], $meta = []) {
    vnbiz_assure_model_name_exists($model_name);

	$meta['limit'] = 1;
    $context = [
        'model_name' => $model_name,
        'filter' => $filter,
        'meta' => $meta
    ];

    vnbiz_do_action('web_model_find', $context);

    return sizeof($context['models']) > 0 ? $context['models'][0] : null;
}


function vnbiz_web_user()
{
    $user = vnbiz_user();
    if (!$user) {
        return null;
    }
    $c = ['models' => [$user]];
    vnbiz_do_action("web_after_model_find_user", $c);
    return $c['models'][0];
}

function vnbiz_notification_create($model) {
    $GLOBALS['vnbiz_permission_skip'] = true;
    $r = vnbiz_model_create('notification', $model, true /** to skip create trans */);
    unset($GLOBALS['vnbiz_permission_skip']);
    return $r;
}

function vnbiz_array_to_xml($array, &$simpleXmlElement) {
    foreach( $array as $key => $value ) {
        if( is_array($value) ) {
            if(is_numeric($key) ){
                $subnode = $simpleXmlElement->addChild('item');
                $subnode->addAttribute('index', $key);
            } else {
                $subnode = $simpleXmlElement->addChild(str_replace('@', 'A', "$key"));
            }
            vnbiz_array_to_xml($value, $subnode);
        } else {
            if(is_numeric($key) ) {
                $subnode = $simpleXmlElement->addChild('item', htmlspecialchars("$value"));
                $simpleXmlElement->addChild(str_replace('@', 'A', "$key"),htmlspecialchars("$value"));
            } else {
                $simpleXmlElement->addChild(str_replace('@', 'A', "$key"),htmlspecialchars("$value"));
            }
        }
     }
}