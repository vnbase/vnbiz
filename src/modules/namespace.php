<?php

function vnbiz_init_module_namespace()
{
    vnbiz_model_add('namespace')
        ->field('name', 'model_name')
        ->field('description', 'text')
    ;
}
