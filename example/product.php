<?php

use VnBiz\VnBizError;

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
    ->has_v()
    ->has_editing_by()
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
    ->ref('product_id', 'product')
    ->no_delete()
    ->has_trash()
    ->string('name')
    ->text('description')
    ->s3_image('photo', [600])
    ->uint('vnd_price', 'usd_price') // adding price
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
        'auto_status' => true,
        'max_redeem' => 0
    ])
    ->has_history()
    ->bool('auto_status')
    ->string('promotion_code')
    ->string('name')
    ->text('description', 'note')
    ->s3_image('photo', [600])
    ->ref('product', 'product')
    ->ref('product_id', 'product')
    ->datetime('start_at', 'end_at')
    ->enum('status', ['active', 'inactive'], 'inactive')
    ->author()
    ->has_v()
    ->uint('max_redeem')
    ->back_ref_count('redeem_count', 'productorderpromotion', 'productpromotion_id', ['productorder_status' => ['ordered']])
    ->db_end_update(function (&$context) {
        L()->debug('productpromotion db_end_update', $context);
        $model = $context['model'];
        $old_model = $context['old_model'];
        $max_redeem = isset($model['max_redeem']) ? $model['max_redeem'] : $old_model['max_redeem'];
        if ($max_redeem == 0) {
            return;
        }
        if (isset($model['redeem_count']) && $model['redeem_count'] > $max_redeem) {
            throw new VnBizError('Invalid redeem_count, redeem_count must be [less than or equal] to max_redeem:' . $max_redeem, 'redeem_exceed', [
                'redeem_count' => $model['redeem_count']
            ], null, 400);
        }
    })
    ->has_editing_by()
    ->require('created_by', 'promotion_code')
    ->unique('unique_promotion_code', ['promotion_code'])
    // ->index('fast', ['product_id', 'status'])
    ->text_search('name', 'description')
    ->write_permission_or(['productpromotion_write'], function (&$context) {
        return false;
    })
;


vnbiz_model_add('productorder')
    ->no_delete()
    ->default([
        'datascope' => '.1.',
        'status' => 'draft', 
        'status_ship' => 'draft', 
        'status_payment' => 'draft'
    ])
    ->author()
    ->has_history()
    ->has_datascope(function (&$context) { // read permission
        // use for non login user
        // customer can view if they are the owner (having productorder_access_token or created_by = current_user_id);

        if (isset($context['filter'])) {
            $filter = $context['filter'];
            if (isset($filter['created_by']) && $filter['created_by'] == vnbiz_user_id()) {
                return true;
            }
        }
        return false;
    }, function (&$context) { // write permissions.
        // anyone login can create;
        return true;
    })
    ->string('marketing_source_id')
    ->ref('contact_id', 'contact')
    ->text('customer_note', 'worker_note')
    ->status('status', [
        'draft' => ['ordered', 'cancelled'],
        'ordered' => ['draft', 'cancelled'],
        'cancelled' => [],
    ], 'draft')
    ->enum('status_ship', [
        'draft',
        'notified',
        'shipping',
        'shipping_back',
        'shipping_backed',
    ], 'draft')
    ->enum('status_payment', [
        'draft',
        'waiting',
        'paid',
        'refunded',
    ], 'draft')
    ->uint('total_vnd_price')
    ->require('created_by')
    ->write_field_permission_or(['status'], ['super', 'productorder_write'], function (&$context) {
        if (isset($context['model'])) {
            $model = $context['model'];
            if (isset($model['created_by']) && $model['created_by'] == vnbiz_user_id()) {
                return true;
            }
        }
        return false;
    })
    ->db_begin_update(function (&$context) {
        if (vnbiz_user_has_permissions('super', 'productorder_write')) {
            return;
        }
        // customer can't update status when:
        // - status is cancelled
        // - status_ship is not [draft, notified]
        
        $old_model = $context['old_model'];
        if ($old_model['status'] === 'completed') {
            throw new VnBizError('Customer cannot update completed order', 'invalid_status');
        }

        if ($old_model['status_ship'] !== 'draft' && $old_model['status_ship'] !== 'notified') {
            throw new VnBizError('Customer cannot update order that is shipping', 'invalid_status');
        }
    })
    ->write_field_permission_or(['contact_id'], ['productorder_write'], function (&$context) {
        // customer can only refer to their own contact
        if (isset($context['model'])) {
            $model = $context['model'];
            if (isset($model['contact_id'])) {
                $contact = vnbiz_model_find_one('contact', ['id' => $model['contact_id']]);
                if ($contact && $contact['created_by'] == vnbiz_user_id()) {
                    return true;
                }
            }
        }
        return false;
    })
    ->write_field_permission_or(['status_payment', 'status_ship'], ['super', 'productorder_write'], function (&$context) {
        return false;
    })
;

vnbiz_model_add('productorderpromotion')
    ->has_trash()
    ->no_delete()
    ->no_update('productorder_id', 'productpromotion_id')
    ->status('productorder_status', [
        'draft' => ['ordered', 'cancelled'],
        'ordered' => ['draft', 'cancelled'],
        'cancelled' => [],
    ], 'draft')
    ->copyValue100('productorder_status', 'productorder_id', 'productorder', 'status')
    ->ref('productorder_id', 'productorder', function (&$context) {
        if (vnbiz_user_has_permissions('super', 'productorder_write')) {
            return true;
        }
        $user_id = vnbiz_user_id();
        
        if ($user_id && isset($context['model']['productorder_id'])) {
            $productorder = vnbiz_model_find_one('productorder', ['id' => $context['model']['productorder_id']]);
            if ($productorder['created_by'] == $user_id) {
                return true;
            }
        }
        return false;
    })
    ->ref('productpromotion_id', 'productpromotion')
    ->author()
    ->unique('unique', ['productorder_id', 'productpromotion_id'])
    // ->write_permission_or(['productorder_write'], function (&$context) {
    //     return false;
    // })
    ->read_permission_or_user_id(['productorder_write'], 'created_by')
    ->write_permission_or_user_id(['productorder_write'], 'created_by')
    ->db_begin_create(function (&$context) {
        $productorder = vnbiz_model_find_one('productorder', ['id' => $context['model']['productorder_id']]);
        if ($productorder['status'] !== 'draft') {
            throw new VnBizError('Cannot add promotion when order is not in draft', 'invalid_status');
        }
        $productpromotion = vnbiz_model_find_one('productpromotion', ['id' => $context['model']['productpromotion_id']]);
        if ($productpromotion['status'] !== 'active') {
            throw new VnBizError('Cannot add inactive promotion', 'invalid_status', [
                'productpromotion_id' => $context['model']['productpromotion_id']
            ]);
        }
    })
    ->db_begin_update(function (&$context) {
        $old_model = $context['old_model'];
        $productorder = vnbiz_model_find_one('productorder', ['id' => $old_model['productorder_id']]);
        if ($productorder['status'] !== 'draft') {
            throw new VnBizError('Cannot update promotion when order is not in draft, current=' . $productorder['status'], 'invalid_status');
        }
    })
;

vnbiz_model_add('productorderitem')
    ->no_update('productorder_id', 'product_id')
    ->ref('productorder_id', 'productorder', function (&$context) {
        if (vnbiz_user_has_permissions('super', 'productorder_write')) {
            return true;
        }
        $user_id = vnbiz_user_id();
        if ($user_id && isset($context['model']['productorder_id'])) {
            $productorder = vnbiz_model_find_one('productorder', ['id' => $context['model']['productorder_id']]);
            if ($productorder['created_by'] == $user_id) {
                return true;
            }
        }
        return false;
    })
    ->status('productorder_status', [
        'draft' => ['ordered', 'cancelled'],
        'ordered' => ['draft', 'cancelled'],
        'cancelled' => [],
    ], 'draft')
    ->copyValue100('productorder_status', 'productorder_id', 'productorder', 'status')
    ->ref('product_id', 'product')
    ->ref('productoption_id', 'productoption')
    ->uint('quantity')
    ->text('note')
    ->author()
    ->require('created_by', 'productorder_id', 'product_id')
    ->index('fast', ['productorder_id', 'product_id'])
    ->read_permission_or_user_id(['productorder_write'], 'created_by')
    ->write_permission_or_user_id(['productorder_write'], 'created_by')
    ->db_begin_create(function (&$context) {
        $productorder = vnbiz_model_find_one('productorder', ['id' => $context['model']['productorder_id']]);
        if ($productorder['status'] !== 'draft') {
            throw new VnBizError('Cannot add promotion when order is not in draft', 'invalid_status');
        }
    })
    ->db_begin_update(function (&$context) {
        $old_model = $context['old_model'];
        $productorder = vnbiz_model_find_one('productorder', ['id' => $old_model['productorder_id']]);
        if ($productorder['status'] !== 'draft') {
            throw new VnBizError('Cannot update promotion when order is not in draft', 'invalid_status');
        }
    })
;

//
// vnbiz_add_action('service_db_init_default', function (&$context) {