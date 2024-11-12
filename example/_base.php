<?php


vnbiz_model_add('campaign')
    ->ui([
        'icon' => 'campaign',
        // 'photo' => 'image',
        'title' => 'name',
        'subtitle' => 'description'
    ])
    ->string('name')
    ->text('description')
    ->datetime('start_at', 'end_at')
    ->author()
    ->require('name', 'created_by')
    ->text_search('name', 'description');;

vnbiz_model_add('contact')
    ->ui([
        'icon' => 'perm_contact_calendar',
        'photo' => 'photo',
        'title' => 'display_name',
        'subtitle' => 'dob'
    ])
    ->s3_image('photo', [50], [200])
    ->text('description')
    ->text('occupation')
    ->string('display_name', 'first_name', 'last_name', 'phone')
    ->email('email')
    ->enum('gender', ['male', 'female', 'other'], 'other')
    ->string('languages')
    ->date('dob')
    ->json('address')
    ->ref('campaign_id', 'campaign')
    ->string('source_id')
    ->author()
    ->index('campaign_source', ['campaign_id', 'source_id'])
    ->text_search('description', 'display_name', 'first_name', 'last_name', 'email', 'phone');
