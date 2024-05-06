<?php

use VnBiz\VnBizError;

function vnbiz_init_module_history()
{
    vnbiz_model_add('history')
        ->json('model_json')
        ->model_name('model_name')
        ->model_id('model_id')
        ->author()
        ->no_delete()
        ->no_update()
        ->index('ref_model_index', ['model_name', 'model_id'])
        ->require('model_json', 'model_name', 'model_id')
    ;
}