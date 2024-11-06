<?php

use VnBiz\VnBizError;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\GoogleUser;
use PhpParser\Node\Expr\Throw_;

function vnbiz_init_module_oauth()
{

    vnbiz_add_action("web_before", function (&$context) {
        if (isset($context['action'])) {
            return;
        }

        // if (isset($_GET['response_type']) && isset($_GET['client_id']) && isset($_GET['redirect_uri'])) {
        //     if ($_GET['response_type'] === 'code') {
        //         $context['action'] = "service_oauth_google_url";
        //         $context['params'] = [
        //             'redirect_url' => $_GET['redirect_uri']
        //         ];
        //         return;
        //     }
        // }

        if (isset($_POST['grant_type'])) {
            if ($_POST['grant_type'] === 'password') {
                if (isset($_POST['username']) && isset($_POST['password'])) {
                    $context['action'] = "service_user_login";
                    $context['params'] = [
                        'username' => $_POST['username'],
                        'password' => $_POST['password']
                    ];
                }
            } else if ($_POST['grant_type'] === 'refresh_token') {
                if (isset($_POST['refresh_token'])) {
                    $context['action'] = "service_user_login";
                    $context['params'] = [
                        'refresh_token' => $_POST['refresh_token']
                    ];
                }
            }
        }
    });

    vnbiz_add_action('vnbiz_before_start', function (&$context) {
        vnbiz_oauth_setup_google();
    });
}

function vnbiz_oauth_setup_google()
{
    if (!defined('OAUTH_GOOGLE_CLIENT_ID') || !defined('OAUTH_GOOGLE_CLIENT_SECRET')) {
        return;
    }
    $provider = new Google([
        'clientId'     => OAUTH_GOOGLE_CLIENT_ID,
        'clientSecret' => OAUTH_GOOGLE_CLIENT_SECRET
    ]);

    vnbiz_add_action("service_oauth_google_url", function (&$context) use ($provider) {
        if (!$context['params'] || !isset($context['params']['redirect_url'])) {
            $context['code'] = 'missing_params';
            $context['error'] = "Missing 'redirect_url' param";
            return;
        }

        $redirect_url = $context['params']['redirect_url'];
        $authUrl = $provider->getAuthorizationUrl(['redirect_uri' => $redirect_url]);

        $context['code'] = 'success';
        $context['error'] = null;
        $context['models'] = [['url' => $authUrl]];
    });

    vnbiz_add_action("service_oauth_google_login", function (&$context) use ($provider) {
        if (!$context['params'] || !isset($context['params']['code']) || !isset($context['params']['redirect_url'])) {
            $context['code'] = 'missing_params';
            $context['error'] = "Missing 'code' or 'redirect_url'";
            return;
        }

        $googleUser = null;
        try {
            $code = $context['params']['code'];
            $redirect_url = $context['params']['redirect_url'];

            $token = $provider->getAccessToken('authorization_code', [
                'code' => $code,
                'redirect_uri' => $redirect_url
            ]);

            /**
             * @var GoogleUser $ownerDetails
             */
            $ownerDetails = $provider->getResourceOwner($token);
            $googleUser = $ownerDetails->toArray();
        } catch (Throwable $e) {
            throw new VnBizError($e->getMessage(), 'oauth_error', [], $e, 401);
        }
        if (!$googleUser) {
            throw new VnBizError("Google user not found!", 'oauth_error', [], null, 401);
        }

        $context['code'] = 'success';
        $context['error'] = null;
        $context['models'] = [$googleUser];

        if (!isset($googleUser['email'])) {
            throw new VnBizError("Missing email!");
        }

        $user = vnbiz_model_find_one('user', [
            'email' => $googleUser['email']
        ]);

        if ($user) {
            if (isset($user['google_sub'])) {
                if ($user['google_sub'] != $googleUser['sub']) {
                    throw new VnBizError("Same user email but diffrent google_sub!");
                }
            } else {
                vnbiz_model_update('user', ['id' => $user['id']], ['google_sub' => $googleUser['sub']]);
            }
        } else {
            $avatar = null;
            if (isset($google['picture'])) {
                $avatar = $google['picture'];
            } else {
                $avatar = null;
            }
            $user = vnbiz_model_create('user', [
                'email' => $googleUser['email'],
                'google_sub' => $googleUser['sub'],
                'language' => $ownerDetails->getLocale(),
                'first_name' => $ownerDetails->getFirstName(),
                'last_name' => $ownerDetails->getLastName(),
                'alias' => $ownerDetails->getName(),
                'email_verified' => vnbiz_get_key($googleUser, 'email_verified', false),
                'avatar' => $avatar
            ]);
        }
        $context['models'] = [$user];

        vnbiz_user_issue_tokens($user, $context);
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
