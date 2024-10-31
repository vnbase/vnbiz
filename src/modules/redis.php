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
    vnbiz_add_action('web_after', function (&$context) {

    });
}

function vnbiz_redis() {
    if (isset($GLOBALS['REDIS_CON'])) {
        return $GLOBALS['REDIS_CON'];
    }

    $redis = new \Redis();
    $GLOBALS['REDIS_CON'] = $redis;

    $redis->pconnect(REDIS_HOST, REDIS_PORT);

    if (defined("REDIS_PASSWORD")) {
        if (REDIS_PASSWORD) {
            $redis->auth([
                'user' => REDIS_PORT,
                'pass' => REDIS_PASSWORD
            ]);
        }
    }

    return $redis;
}