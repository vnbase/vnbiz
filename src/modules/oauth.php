<?php

use VnBiz\VnBizError;
use League\OAuth2\Client\Provider\Google;

function vnbiz_init_module_oauth() {
    $provider = new Google([
        'clientId'     => '{google-client-id}',
        'clientSecret' => '{google-client-secret}',
        'redirectUri'  => 'https://example.com/callback-url',
        'hostedDomain' => 'example.com', // optional; used to restrict access to users on your G Suite/Google Apps for Business accounts
    ]);



}