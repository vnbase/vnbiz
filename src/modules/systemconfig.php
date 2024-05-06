<?php

use VnBiz\VnBizError;

function vnbiz_init_module_systemconfig()
{
    vnbiz_model_add('systemconfig')
        ->slug('k')
        ->text('v')
        ->unique('k_unique', ['k'])
        ->read_permission('super', 'config_read')
        ->write_permission('super', 'config_write')
        ;
}

function vnbiz_systemconfig_get($key) {
    $config = vnbiz_model_find_one('systemconfig', ['k' => $key], ['in_trans' => true]);
    if (!$config) {
        return '';
    }
    return $config['v'];
};

function vnbiz_systemconfig_set($key, $value) {
    try {
        vnbiz_model_create('systemconfig', [
            'k' => $key,
            'v' => $value
        ], true);
    } catch (VnBizError $e) {
        if ($e->get_status() == 'model_exists') {
            $config = vnbiz_model_find_one('systemconfig', ['k' => $key], ['in_trans' => true]);
            vnbiz_model_update('systemconfig', ['id' => $config['id']], ['v' => $value], [], true);
        } else {
            throw $e;
        }
    }

    return $value;
};