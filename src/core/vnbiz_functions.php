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

function vnbiz_has_key($array, $keys)
{
    if (!is_array($keys)) {
        throw new VnBizError("Key list must be array");
    }

    if ($array === null) {
        return false;
    }

    if (!is_array($array)) {
        throw new VnBizError("Value is not array");
    }

    foreach ($keys as $key) {
        if (!isset($array[$key])) {
            return false;
        }
        $array = $array[$key];
    }
    return true;
}

function vnbiz_random_string($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}

function vnbiz_array_contains_array($model, $filter)
{
    foreach ($filter as $key => $value) {
        if (!isset($model[$key])) {
            return false;
        }
        if ($model[$key] != $value) {
            return false;
        }
    }
    return true;
}

function vnbiz_now()
{
    date_default_timezone_set("UTC");
    return  date("Y-m-d H:i:s");
}
function vnbiz_html($text)
{
    return htmlspecialchars($text);
}

global $VNBIZ_HASH_ID_INSTANCE;
//secure this
$VNBIZ_HASH_ID_INSTANCE = new \Hashids\Hashids('VnBizS3cr3t',  10, 'abcdefghijklmnopqrstuvwxyz');
function vnbiz_encrypt_id($id)
{
    global $VNBIZ_HASH_ID_INSTANCE;
    if (is_numeric($id)) {
        return $VNBIZ_HASH_ID_INSTANCE->encode($id);;
    }
    return $id;
}
function vnbiz_encrypt_ids($ids)
{
    global $VNBIZ_HASH_ID_INSTANCE;
    if ($ids == null) {
        return null;
    }

    if (is_array($ids)) {
        $arr = [];
        foreach ($ids as $id) {
            $arr[] = vnbiz_encrypt_id($id);
        }
        return $arr;
    }
    if (is_string($ids)) {
        return vnbiz_encrypt_id($ids);
    }
    return $ids;
}

function vnbiz_decrypt_id($id)
{
    global $VNBIZ_HASH_ID_INSTANCE;
    if ($id == null) {
        return null;
    }
    if (is_numeric($id)) {
        return $id;
    }
    $result = $VNBIZ_HASH_ID_INSTANCE->decode($id);
    if (sizeof($result) == 0) {
        throw new VnBizError("Invalid id", 'invalid_id', ['id' => $id]);
    }
    return $result[0] ?? null;
}

function vnbiz_decrypt_ids($ids)
{
    global $VNBIZ_HASH_ID_INSTANCE;
    if (is_array($ids)) {
        $arr = [];
        foreach ($ids as $id) {
            $arr[] = vnbiz_decrypt_id($id);
        }
        return $arr;
    }
    if (is_string($ids)) {
        return vnbiz_decrypt_id($ids);
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

function vnbiz_model_has_field_name($model_name, $field_name)
{
    $models = vnbiz()->models();

    if (!isset($models[$model_name])) {
        return false;
    }

    return isset($models[$model_name]->get_schema_details()[$field_name]);
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

function vnbiz_has_action($action_name)
{
    return vnbiz()->actions()->has_action($action_name);
}


function vnbiz_model_create($model_name, $model, $in_trans = false)
{
    vnbiz_assure_model_name_exists($model_name);

    $context = [
        'model_name' => $model_name,
        'model' => $model,
        'in_trans' => $in_trans
    ];
    vnbiz_do_action("model_create", $context);
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
        'model_name' => $model_name,
        'filter' => $filter
    ];
    vnbiz_do_action("model_count", $context);

    return $context['count'];
}

function vnbiz_model_find($model_name, $filter = [], $meta = ['limit' => 10, 'offset' => 0])
{
    vnbiz_assure_model_name_exists($model_name);

    $context = [
        'model_name' => $model_name,
        'filter' => $filter,
        'meta' =>  $meta
    ];
    vnbiz_do_action("model_find", $context);
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
        'model_name' => $model_name,
        'filter' => $filter,
        'model' => $model,
        'meta' => $meta,
        'in_trans' => $in_trans
    ];
    vnbiz_do_action("model_update", $context);

    return $context['old_model'];
}

function vnbiz_model_delete($model_name, $filter, $in_trans = false)
{
    vnbiz_assure_model_name_exists($model_name);
    $context = [
        'model_name' => $model_name,
        'filter' => $filter,
        'in_trans' => $in_trans
    ];
    vnbiz_do_action("model_delete", $context);
    return $context['old_model'];
}

function vnbiz_user()
{
    return isset($GLOBALS['vnbiz_user']) ? $GLOBALS['vnbiz_user'] : null;
}
function vnbiz_user_or_throw()
{
    $user = vnbiz_user();
    if (!$user) {
        throw new VnBizError("Login required", 'permission');
    }
    return $user;
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

function vnbiz_http_request($method, $path, $query = [], $body = false, $header = [])
{
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

function vnbiz_http_request_json($method, $path, $query = [], $body = false, $header = [])
{
    $result = vnbiz_http_request($method, $path, $query, $body, $header);
    return json_decode($result);
}

function vnbiz_web_model_create($model_name, $model)
{
    vnbiz_assure_model_name_exists($model_name);

    $context = [
        'model_name' => $model_name,
        'model' => $model
    ];

    vnbiz_do_action('web_model_create', $context);

    return $context['model'];
}
function vnbiz_web_model_update($model_name, $filter, $model)
{
    vnbiz_assure_model_name_exists($model_name);

    $context = [
        'model_name' => $model_name,
        'filter' => $filter,
        'model' => $model
    ];

    vnbiz_do_action('web_model_update', $context);

    return $context['old_model'];
}
function vnbiz_web_model_delete($model_name, $filter)
{
    vnbiz_assure_model_name_exists($model_name);

    $context = [
        'model_name' => $model_name,
        'filter' => $filter
    ];

    vnbiz_do_action('web_model_delete', $context);

    return $context['old_model'];
}

function vnbiz_web_model_find($model_name, $filter = [], $meta = [])
{
    vnbiz_assure_model_name_exists($model_name);

    $context = [
        'model_name' => $model_name,
        'filter' => $filter,
        'meta' => $meta
    ];

    vnbiz_do_action('web_model_find', $context);

    return $context['models'];
}

function vnbiz_web_model_search(&$context)
{
    $model_name = $context['model_name'];
    vnbiz_assure_model_name_exists($model_name);
    vnbiz_do_action('web_model_find', $context);
}

function vnbiz_web_model_find_one($model_name, $filter = [], $meta = [])
{
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

function vnbiz_notification_create($model)
{
    $GLOBALS['vnbiz_permission_skip'] = true;
    $r = vnbiz_model_create(
        'notification',
        $model,
        true
        /** to skip create trans */
    );
    unset($GLOBALS['vnbiz_permission_skip']);
    return $r;
}

function vnbiz_array_to_xml($array, &$simpleXmlElement)
{
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            if (is_numeric($key)) {
                $subnode = $simpleXmlElement->addChild('item');
                $subnode->addAttribute('index', $key);
            } else {
                $subnode = $simpleXmlElement->addChild(str_replace('@', '_', "$key"));
            }
            vnbiz_array_to_xml($value, $subnode);
        } else {
            if (is_numeric($key)) {
                $subnode = $simpleXmlElement->addChild('item', htmlspecialchars("$value"));
                $subnode->addAttribute('index', $key);
            } else {
                $simpleXmlElement->addChild(str_replace('@', '_', "$key"), htmlspecialchars("$value"));
            }
        }
    }
}
/**
 * return [file_name, file_size, file_path, file_type]
 */
function vnbiz_download_file_from_url($url, $max_size_mb)
{
    if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        throw new VnBizError("Invalid file url");
    }
    if (!is_numeric($max_size_mb) || $max_size_mb <= 0) {
        throw new VnBizError("Invalid max size");
    }

    $max_size_bytes = $max_size_mb * 1024 * 1024;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $result = curl_exec($ch);
    $information = curl_getinfo($ch);
    curl_close($ch);

    if ($information['http_code'] != 200) {
        throw new VnBizError("Can't download file from url: $url", 's3_download_error');
    }

    $size = (int) $information['download_content_length'];
    if ($size > $max_size_bytes) {
        throw new VnBizError("File size too large: $size", 's3_file_too_large');
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    $result = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($result, 0, $header_size);
    curl_close($ch);

    preg_match('/filename="([^"]+)"/', $header, $matches);
    $file_name = $matches[1] ?? basename($url);

    $file_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $file_name;

    // Open file handle
    $fp = fopen($file_path, 'w');
    if (!$fp) {
        throw new VnBizError("Unable to open file for writing: $file_path", 'file_write_error');
    }

    // Initialize cURL session for downloading the file
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FILE, $fp); // Write directly to file

    // Set a callback function to limit the download size
    curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($resource, $download_size, $downloaded, $upload_size, $uploaded) use ($max_size_bytes) {
        if ($downloaded > $max_size_bytes) {
            return 1; // Return non-zero to abort the transfer
        }
        return 0;
    });
    curl_setopt($ch, CURLOPT_NOPROGRESS, false);

    curl_exec($ch);
    if (curl_errno($ch)) {
        fclose($fp);
        unlink($file_path);
        throw new VnBizError("Download aborted: file size exceeds limit", 's3_file_too_large');
    }
    curl_close($ch);
    fclose($fp);

    return [
        'file_name' => $file_name,
        'file_size' => filesize($file_path),
        'file_path' => $file_path,
        'file_type' => $information['content_type']
    ];
}

function vnbiz_generate_thumbnail($source_file_path, $thumbnail_path, $width, $height, $quality = 80)
{
    try {
        $imagick = new Imagick($source_file_path);

        // Resize the image while maintaining aspect ratio
        $imagick->thumbnailImage($width, $height, true);

        // Generate a temporary file path for the thumbnail
        // $thumbnail_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . '' . basename($source_file_path);

        $imagick->setImageFormat('jpeg');
        $imagick->setImageCompressionQuality($quality);
        // Save the thumbnail
        $imagick->writeImage($thumbnail_path);

        // Clear resources
        $imagick->clear();
        $imagick->destroy();

        return $thumbnail_path;
    } catch (Exception $e) {
        error_log("Error generating thumbnail: " . $e->getMessage());
        return false;
    }
}

function vnbiz_unique_text()
{
    $bytes = random_bytes(16);
    return bin2hex($bytes);
}

function vnbiz_namespace_id()
{
    if (isset($GLOBALS['vnbiz_namespace_id'])) {
        return $GLOBALS['vnbiz_namespace_id'];
    }
    if (isset($_SERVER['HTTP_X_NAMESPACE'])) {
        $ns_id = vnbiz_decrypt_id($_SERVER['HTTP_X_NAMESPACE']);
        if ($ns_id) {
            $GLOBALS['vnbiz_namespace_id'] = $ns_id;
            return $ns_id;
        }
    }
    if (isset($_GET['ns'])) {
        $ns_id = vnbiz_decrypt_id($_GET['ns']);
        if ($ns_id) {
            $GLOBALS['vnbiz_namespace_id'] = $ns_id;
            return $ns_id;
        }
    }
    if (isset($_POST['ns'])) {
        $ns_id = vnbiz_decrypt_id($_POST['ns']);
        if ($ns_id) {
            $GLOBALS['vnbiz_namespace_id'] = $ns_id;
            return $ns_id;
        }
    }
    throw new Error("Namespace is missing");
    return 0;
}

function vnbiz_module_namespace_enabled() {
    return isset($GLOBALS['vnbiz_namespace_id']);
}

function vnbiz_get_ids($array) {
    $ids = [];
    foreach ($array as $item) {
        if (isset($item['id'])) {
            $ids[] = $item['id'];
        }
    }
    return $ids;
}