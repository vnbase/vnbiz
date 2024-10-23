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
        if (!$context['params'] || !isset($context['params']['redirect_url']) ) {
            $context['code'] = 'missing_params';
            $context['error'] = "Missing 'redirect_url'";
            return;
        }

        $redirect_url = $context['params']['redirect_url'];

        $authUrl = $provider->getAuthorizationUrl(['redirect_uri' => $redirect_url]);

        $context['code'] = 'success';
        $context['error'] = null;
        $context['models'] = [['url' => $authUrl]];
    });

    vnbiz_add_action("service_oauth_google_login", function (&$context) use ($provider) {
        if (!$context['params'] || !isset($context['params']['code']) || !isset($context['params']['redirect_url']) ) {
            $context['code'] = 'missing_params';
            $context['error'] = "Missing 'code' or 'redirect_url'";
            return;
        }
        $code = $context['params']['code'];
        $redirect_url = $context['params']['redirect_url'];

        $token = $provider->getAccessToken('authorization_code', [
            'code' => $code,
            'redirect_uri' => $redirect_url
        ]);

        try {
            $ownerDetails = $provider->getResourceOwner($token);
            $context['code'] = 'success';
            $context['error'] = null;
            $context['models'] = [$ownerDetails->toArray()];

            if (!$ownerDetails->getEmail()) {
                throw new Error("Missing email!");
            }

            $user = vnbiz_model_find_one('user', [
                'email' => $ownerDetails->getEmail()
            ]);

            if ($user) {
                if (isset($user['google_sub'])) {
                    if ($user['google_sub'] != $ownerDetails->getId()) {
                        throw new Error("Same user email but diffrent google_sub!");
                    }
                }

                vnbiz_model_update('user', ['id' => $user['id']], ['google_sub' => $ownerDetails->getId()]);
            } else {
                $user = vnbiz_model_create('user', [
                    'email' => $ownerDetails->getEmail(),
                    'google_sub' => $ownerDetails->getId(),
                    'language' => $ownerDetails->getLocale(),
                    'first_name' => $ownerDetails->getFirstName(),
                    'last_name' => $ownerDetails->getLastName(),
                    'alias' => $ownerDetails->getName(),
                    // 'avatar' => $ownerDetails->getAvatar()
                ]);
            }
            $context['models'] = [$user];
            $context['access_token'] = vnbiz_token_sign(['user_id' => $user['id']], 'vnbizsecret');

        } catch (Exception $e) { 
            throw $e;
        }
    });

}
/**
 * 
		{
			"sub": "100976852762216133136",
			"name": "Nam Nguyen Tu",
			"given_name": "Nam",
			"family_name": "Nguyen Tu",
			"picture": "https://lh3.googleusercontent.com/a/ACg8ocLY0chYOm2ioWNz-b77oo_KfbQPBefr_LF0jKv3mfWeMUQYXfgJ=s96-c",
			"email": "nguyentunam@gmail.com",
			"email_verified": true
		}
 */