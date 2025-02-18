<?php

function vnbiz_init_module_namespace()
{
    vnbiz_model_add('namespace')
        ->default([
            'slug' => 'ns' . vnbiz_random_string()
        ])
        ->slug('slug')
        ->field('name', 'model_name')
        ->field('description', 'text')
        ->unique("unique", ['slug'])
    ;
}
