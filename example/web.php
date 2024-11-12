<?php

include_once __DIR__ . '/_base.php';
include_once __DIR__ . '/product.php';

use VnBiz\VnBizError;

//TODO: has editing
vnbiz_model_add('productpage')
    ->string('name')
    ->text('description')
    ->s3_image('logo', [300])
    ->s3_image('photo', [500])
    ->enum('status', ['active', 'inactive'], 'active')
    ->author()
    ->require('created_by')
    ->write_permission_or(['productpage_write'], function (&$context) {
        return false;
    })
    ->text_search('name', 'description')
;