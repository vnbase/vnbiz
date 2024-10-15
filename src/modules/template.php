<?php

use VnBiz\VnBizError;
use divengine\div;

function vnbiz_init_module_template()
{
    vnbiz_model_add('template')
        ->string('name') //TODO: validate
        ->string('language')
        ->text('content')
        ->text('note')
        ->author()
        ->require('name', 'language')
        ->unique('unique_email_name_language', ['name', 'language'])
        ->has_history()
        ->read_permission('super', 'template_read')
        ->write_permission('super', 'template_write')
    ;

}

function vnbiz_render($template_name, $language, $data, $default = '') {
    $model = vnbiz_model_find_one('template', [
        'name' => $template_name, 
        'language' => $language
    ]);
    
    $template = $default;
    
    if ($model && isset($model['content'])) {
        $template = $model['content'];
    }

    return '' . (new div($template, $data));
}