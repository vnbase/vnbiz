<?php

use VnBiz\VnBizError;

function vnbiz_init_module_notification() {

    vnbiz_model_add('notification')
        ->default([
            'status' => 'new'
        ])
        ->ref('user_id', 'user')
        ->has_trash()
        ->string('title')
        ->text('content')
        ->json('payload')
        ->enum('status', ['new', 'viewed'], 'new')
        ->index('fast-find', ['is_trash', 'user_id', 'created_at'])
        ->require('user_id', 'title')
        ->read_permission_or(['super', 'notification_read'], function (&$context) {	
            $user = vnbiz_user();
            if (!$user) {
                return false;
            }

            if (isset($context['filter']) && isset($context['filter']['user_id'])) {
                return $context['filter']['user_id'] == $user['id'];
            }
            return false;
        })
        ->no_update('title', 'content', 'payload', 'user_id')
        ->read_permission_or_user_id(['super', 'notification_read'], 'user_id')
        ->write_permission_or_user_id(['super', 'notification_write'], 'user_id')
        ;
    vnbiz_model_add('notification_token')
        ->ref('user_id', 'user')
        ->text('token', 'agent')
        ->require('user_id')
        ->index('fast', ['user_id'])
        ->read_permission_or_user_id(['super', 'notification_read'], 'user_id')
        ->write_permission_or_user_id(['super', 'notification_write'], 'user_id')
        ;
}