<?php

include_once __DIR__ . '/_base.php';

vnbiz_model_add('productcategory')
    // ->geo('location')
    ->int('position') // higher position will be displayed first
    ->string('name')
    ->text('description')
    ->enum('status', ['active', 'inactive'], 'active')
    ->s3_image('photo', [900])
    ->author()
    ->require('created_by')
    ->write_permission_or(['product_write'], function (&$context) {
        return false;
    })
    ->text_search('name', 'description')
;

vnbiz_model_add('product')
    ->no_delete()
    ->has_trash()
    // ->geo('location')
    ->string('name')
    ->text('description')
    ->s3_image('photo', [430, 932])
    ->uint('vnd_price', 'usd_price')
    ->enum('status', ['active', 'inactive'], 'active')
    ->has_history()
    ->author()
    ->require('created_by')
    ->write_permission_or(['product_write'], function (&$context) {
        return false;
    })
    ->text_search('name', 'description')
;

vnbiz_model_add('productincategory')
    ->ref('productcategory_id', 'productcategory')
    ->author()
    ->require('created_by', 'productcategory_id')
    ->write_permission_or(['product_write'], function (&$context) {
        return false;
    })
;

vnbiz_model_add('productfile')
    ->int('position') // higher position will be displayed first
    ->ref('product_id', 'product')
    ->s3_file('file')
    ->author()
    ->require('created_by', 'product_id')
    ->index('fast', ['product_id', 'position'])
    ->write_permission_or(['product_write'], function (&$context) {
        return false;
    })
;

vnbiz_model_add('productoption')
    // ->geo('location')
    ->no_delete()
    ->has_trash()
    ->string('name')
    ->text('description')
    ->s3_image('photo', [600])
    ->uint('vnd_price', 'usd_price')
    ->enum('status', ['active', 'inactive'], 'active')
    ->has_history()
    ->author()
    ->require('created_by')
    ->write_permission_or(['product_write'], function (&$context) {
        return false;
    })
    ->text_search('name', 'description')
;

vnbiz_model_add('productpromotion')
    ->no_delete()
    ->has_trash()
    ->default([
        'auto_status' => true
    ])
    ->bool('auto_status')
    ->slug('promotion_code')
    ->string('name')
    ->text('description', 'note')
    ->s3_image('photo', [600])
    ->ref('product', 'product')
    ->ref('product_id', 'product')
    ->datetime('start_at', 'end_at')
    ->enum('status', ['active', 'inactive'], 'inactive')
    ->author()
    ->require('created_by', 'product_id')
    ->unique('unique_promotion_code', ['promotion_code'])
    ->index('fast', ['product_id', 'status'])
    ->text_search('name', 'description')
    ->write_permission_or(['productpromotion_write'], function (&$context) {
        return false;
    })
;


vnbiz_model_add('productorder')
    ->no_delete()
    ->default([
        'datascope' => '.1.'
    ])
    ->author()
    ->has_datascope(function (&$context) { // read permission
        // use for non login user
        // customer can view if they are the owner (having productorder_access_token or created_by = current_user_id);
        if (isset($context['filter'])) {
            if (isset($filter['created_by']) && $filter['created_by'] == vnbiz_user_id()) {
                return true;
            }
        }
        return false;
    }, function (&$context) { // write permissions.
        // use for non login user
        // customer can edit if they are the owner (having productorder_access_token or created_by = current_user_id);
        if (isset($context['model'])) {
            if (isset($model['created_by']) && $model['created_by'] == vnbiz_user_id()) {
                return true;
            }
        }
    })
    ->string('marketing_source_id')
    ->ref('contact_id', 'contact')
    ->text('customer_note', 'worker_note')
    ->enum('status', [
        'new',
        'confirmed',
        'prepared',
        'shipping',
        'shipping_back',
        'delivered',
        'shipping_backed',
        'cancelled'
    ], 'new')
    ->uint('total_vnd_price')
    ->write_permission_or(['productorder_write'], function (&$context) {
        if (isset($model['created_by']) && $model['created_by'] == vnbiz_user_id()) {
            return true;
        }
    })
    ->write_field_permission_or([], ['productorder_write'], function (&$context) {
        if (isset($model['created_by']) && $model['created_by'] == vnbiz_user_id()) {
            return true;
        }
    })
;

vnbiz_model_add('productorderpromotion')
    ->has_trash()
    ->no_delete()
    ->ref('productorder_id', 'productorder')
    ->ref('productpromotion_id', 'productpromotion')
    ->author()
    ->unique('unique', ['productorder_id', 'productpromotion_id'])
    ->write_permission_or(['productorder_write'], function (&$context) {
        return false;
    })
;

vnbiz_model_add('productorderitem')
    ->ref('productorder_id', 'productorder')
    ->ref('product_id', 'product')
    ->uint('quantity')
    ->text('note')
    ->author()
    ->require('created_by', 'productorder_id', 'product_id')
    ->index('fast', ['productorder_id', 'product_id'])
    ->read_permission_or_user_id(['productorder_write'], 'created_by')
    ->write_permission_or_user_id(['productorder_write'], 'created_by')
;