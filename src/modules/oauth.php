<?php

use VnBiz\VnBizError;
use League\OAuth2\Client\Provider\Google;

function vnbiz_init_module_oauth() {
    $provider = new Google([
        'clientId'     => '1065936865511-oi397kd912idmcn0p9ol3hg9bqv9nbqb.apps.googleusercontent.com',
        'clientSecret' => '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',
        'redirectUri'  => 'https://example.com/callback-url',
        // 'hostedDomain' => 'example.com', // optional; used to restrict access to users on your G Suite/Google Apps for Business accounts
    ]);


    vnbiz_add_action("service_oauth_google_url", function (&$context) use ($provider) {
        if (!$context['params'] || !isset($context['params']['redirectUrl']) ) {
            $context['code'] = 'missing_params';
            $context['error'] = "Missing 'redirectUrl'";
            return;
        }

        $redirect_uri = $context['params']['redirectUrl'];

        $authUrl = $provider->getAuthorizationUrl(['redirect_uri' => $redirect_uri]);

        $context['code'] = 'success';
        $context['error'] = null;
        $context['models'] = [['url' => $authUrl]];
    });

    vnbiz_add_action("service_oauth_google_login", function (&$context) use ($provider) {
        if (!$context['params'] || !isset($context['params']['code']) ) {
            $context['code'] = 'missing_params';
            $context['error'] = "Missing 'code'";
            return;
        }

        $token = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        try {
            $ownerDetails = $provider->getResourceOwner($token);
            $context['code'] = 'success';
            $context['error'] = null;
            $context['models'] = [$ownerDetails];
        } catch (Exception $e) { 
            throw $e;
        }
    });

}