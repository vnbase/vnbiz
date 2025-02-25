<?php

use VnBiz\VnBizError;

function vnbiz_init_module_redis()
{
    vnbiz_add_action('web_before', function (&$context) {
        $hostname = gethostname();
        $app_name = vnbiz()->getAppName();
        $prefix = "$app_name.$hostname";

        if (isset($context['action'])) {
            if (str_starts_with($context['action'], 'model_')) {
                if (isset($context['model_name'])) {
                    vnbiz_redis()->incr("$prefix." . $context['action'] . '.' . $context['model_name']);
                }
            } else {
                vnbiz_redis()->incr("$prefix." . $context['action']);
            }
        }
    });
    vnbiz_add_action('web_after', function (&$context) {});
}

function vnbiz_redis()
{
    if (isset($GLOBALS['REDIS_CON'])) {
        return $GLOBALS['REDIS_CON'];
    }

    $redis = new \Redis();
    $GLOBALS['REDIS_CON'] = $redis;

    if (defined(REDIS_PORT)) {
        $redis->pconnect(REDIS_HOST, REDIS_PORT);
    } else {
        $redis->pconnect(REDIS_HOST);
    }

    if (defined("REDIS_PASSWORD")) {
        if (defined("REDIS_USERNAME")) {
            $redis->auth([
                'user' => REDIS_USERNAME,
                'pass' => REDIS_PASSWORD
            ]);
        } else {
            $redis->auth([
                'pass' => REDIS_PASSWORD
            ]);
        }
    }

    return $redis;
}

function vnbiz_redis_get_array($key)
{
    $string = vnbiz_redis()->get($key);
    if ($string) {
        return json_decode($string, true);
    }
    return null;
}
function vnbiz_redis_get_arrays($keys)
{
    $strings = vnbiz_redis()->mget($keys);
    if (!$strings) {
        return [];
    }
    $result = [];
    foreach ($strings as $string) {
        if ($string) {
            $result[] = json_decode($string, true);
        } else {
            $result[] = null;
        }
    }
    // error_log("vnbiz_redis_get_arrays: " . json_encode($keys));
    return $result;
}

// Redis key-value, ttl default is 1 hour
function vnbiz_redis_set_array($key, $array, $ttl_seconds = 3600)
{
    L()->debug("REDIS set " . $key);
    return vnbiz_redis()->setEx($key, $ttl_seconds, json_encode($array));
}

// Redis key-value, ttl default is 1 hour
function vnbiz_redis_set_arrays($key_array, $ttl_seconds = 3600)
{
    $result = [];
    foreach ($key_array as $key => $array) {
        $result[] = vnbiz_redis_set_array($key, $array, $ttl_seconds);
    }
    return $result;
}

function vnbiz_redis_del($key)
{
    L()->debug("REDIS del " . $key);
    return vnbiz_redis()->unlink($key);
}

function vnbiz_redis_dels($keys)
{
    L()->debug("REDIS del ", $keys);
    return vnbiz_redis()->unlink($keys);
}
