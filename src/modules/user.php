<?php

use VnBiz\VnBizError;

function vnbiz_getAuthorizationHeader()
{
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        //print_r($requestHeaders);
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}

/**
 * get access token from header
 * */
function vnbiz_getBearerToken()
{
    $headers = vnbiz_getAuthorizationHeader();
    // HEADER: Get the access token from the header
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}


function vnbiz_user_get_public_hashed_password($user)
{
    if (isset($user['password']) && strlen($user['password']) > 5) {
        return MD5(substr($user['password'], 0, 5) . substr($user['password'], -5));
    }
    return '';
}

function vnbiz_user_all_permissions($user_id)
{
    $useringroups = vnbiz_model_find('useringroup', ['user_id' => $user_id], ['ref' => true, 'limit' => 1000]);

    $permissions = [];
    $permissions_scope = [];
    foreach ($useringroups as $useringroup) {
        if (isset($useringroup['@usergroup_id'])) {
            if (isset($useringroup['@usergroup_id']['permissions'])) {
                foreach (explode(',', $useringroup['@usergroup_id']['permissions']) as $per) {
                    isset($permissions[$per]) ?: $permissions[$per] = [];
                    $permissions[$per][] = $useringroup['@usergroup_id']['id'];
                }
            }
            if (isset($useringroup['@usergroup_id']['permissions_scope'])) {
                foreach (array_keys($useringroup['@usergroup_id']['permissions_scope']) as $scope) {
                    isset($permissions_scope[$scope]) ?: $permissions_scope[$scope] = [];
                    $permissions_scope[$scope][] = $useringroup['@usergroup_id']['id'];
                }
            }
        }
    }
    return [$permissions, $permissions_scope];
}

function vnbiz_user_issue_tokens(&$user, &$context)
{

    if ($user['status'] !== 'active') {
        throw new VnBizError("User status is: " . $user['status'], 'user_status', null, null, 403);
    }

    [$permissions, $permission_scope] = vnbiz_user_all_permissions($user['id']);

    $GLOBALS['vnbiz_user'] = $user;

    $context['code'] = 'success';
    $context['error'] = null;
    $context['models'] = [$user];

    $refresh_payload = [
        'typ' => 'refresh',
        'sub' => vnbiz_encrypt_id($user['id']),
        'pas' => vnbiz_user_get_public_hashed_password($user)
    ];
    if (vnbiz_module_namespace_enabled()) {
        $refresh_payload['ns'] = vnbiz_namespace_id();
    }

    $expire_in = 900; //15 minutes
    $context['refresh_token'] = vnbiz_token_sign($refresh_payload, VNBIZ_TOKEN_SECRET);

    $access_payload = [
        'typ' => 'access',
        'sub' => vnbiz_encrypt_id($user['id']),
        'per' => $permissions,
        'per_s' => $permission_scope
    ];
    if (vnbiz_module_namespace_enabled()) {
        $access_payload['ns'] = vnbiz_namespace_id();
    }
    $context['access_token'] = vnbiz_token_sign($access_payload, VNBIZ_TOKEN_SECRET, time() + $expire_in);

    $context['token_type'] = 'Bearer';
    $context['expires_in'] = $expire_in;
}

//TODO: Add refresh token service
//TODO: Limit request per second
//TODO: capchar features
function vnbiz_init_module_user()
{
    vnbiz_model_add('user')
        ->default([
            'username' => 'u' . vnbiz_random_string()
        ])
        ->ui([
            'icon' => 'person',
            'photo' => 'avatar',
            'title' => 'alias',
            'subtitle' => 'email'
        ])
        ->string('alias', 'first_name', 'last_name')
        ->s3_image('avatar', [50], [200])
        ->s3_image('cover', [640, 360], [820, 312])
        ->email('email')
        ->string('phone')
        ->string('username') //TODO: validate
        ->bool('email_verified', 'phone_verified')
        ->string('timezone')
        ->string('language')
        ->string('fuid', 'google_sub')
        ->password('password')
        ->text('bio', 'note')
        ->enum('status', ['active', 'inactive', 'deleted'], 'active')
        ->has_usermarks('follow')
        ->author()
        ->unique('user_unique_email', ['email'])
        ->unique('user_unique_username', ['username'])
        ->unique('user_unique_fuid', ['fuid'])
        ->no_delete()
        ->text_search('alias', 'first_name', 'last_name', 'email', 'note', 'username')
        ->read_field_permission_or(['email', 'fuid', 'status', 'first_name', 'last_name'], ['super', 'user_read'], function (&$model) {
            $user = vnbiz_user();
            if ($user && $user['id'] == vnbiz_decrypt_id($model['id'])) {
                return true;
            }
            return false;
        })
        ->write_field_permission(['email', 'phone', 'fuid', 'google_sub', 'email_verified', 'phone_verified'], ['super', 'user_write'])
        ->web_before_update(function (&$context) {
            if (vnbiz_user_has_permissions('super', 'user_write')) {
                return true;
            }

            $user = vnbiz_user();
            if ($user && isset($context['filter']) && isset($context['filter']['id'])) {
                if ($user && $user['id'] == vnbiz_decrypt_id($context['filter']['id'])) {
                    return true;
                }
            }

            return false;
        })

    ;
    // ->text_search('alias', 'first_name', 'last_name', 'email', 'description',);

    vnbiz_model_add('usergroup')
        ->ui([
            'icon' => 'groups',
            'title' => 'name',
            // 'subtitle' => 'description'
        ])
        ->string('name')
        ->text('description')
        ->text('permissions')
        ->json('permissions_scope')
        ->author()
        ->text_search('name', 'description')
        ->read_permission('super', 'permission_read')
        ->write_permission('super', 'permission_write')
    ;


    vnbiz_model_add('useringroup')
        ->ui([
            'icon' => 'group',
            'title' => 'user_id',
            'subtitle' => 'usergroup_id'
        ])
        ->ref('user_id', 'user')
        ->ref('usergroup_id', 'usergroup')
        ->require('user_id', 'usergroup_id')
        ->unique('user_unique_group', ['user_id', 'usergroup_id'])
        ->author()
        ->read_permission('super', 'permission_read')
        ->write_permission('super', 'permission_write')
    ;

    vnbiz_add_action('web_before', function (&$context) {
        $token = vnbiz_getBearerToken();
        if (!$token) {
            return;
        }

        $arr = vnbiz_token_verify($token, VNBIZ_TOKEN_SECRET);
        if ($arr && isset($arr['sub']) && isset($arr['typ']) && $arr['typ'] === 'access') {
        } else {
            throw new VnBizError('Invalid bearer token', "invalid_token", null, null, 401);
        }

        $user_id = vnbiz_decrypt_id($arr['sub']);
        $GLOBALS['vnbiz_user'] = ['id' => $user_id];
        if (isset($arr['per'])) {
            $GLOBALS['vnbiz_user_permissions'] = $arr['per'];
        }
        if (isset($arr['per'])) {
            $GLOBALS['vnbiz_user_permissions_scope'] = $arr['per_s'];
        }

        if (vnbiz_debug_enabled()) {
            L_withName('user:' . $user_id);
        }

    });

    vnbiz_add_action("service_user_login", function (&$context) {
        if (!isset($context['params'])) {
            $context['code'] = 'missing_params';
            $context['error'] = "Missing username or password";
            return;
        }
        //TODO: Check token namespace
        $user = null;
        if (isset($context['params']['refresh_token'])) {
            $arr = vnbiz_token_verify($context['params']['refresh_token'], VNBIZ_TOKEN_SECRET);
            if ($arr &&  isset($arr['sub']) && isset($arr['typ']) && $arr['typ'] === 'refresh' && isset($arr['pas'])) {
                $user = vnbiz_model_find_one('user', ['id' => vnbiz_decrypt_id($arr['sub'])]);
                if (!$user) {
                    throw new VnBizError('Invalid bearer token', "invalid_token", null, null, 401);
                }
                $hashed_pass = vnbiz_user_get_public_hashed_password($user);
                if ($hashed_pass !== $arr['pas']) {
                    throw new VnBizError('Invalid bearer token', "invalid_token", null, null, 401);
                }
            } else {
                throw new VnBizError('Invalid bearer token', "invalid_token", null, null, 401);
            }
        } else if (isset($context['params']['username'])) {
            if (!isset($context['params']['password'])) {
                $context['code'] = 'missing_params';
                $context['error'] = "Missing password";
                return;
            }
            $username = $context['params']['username'];
            $password = $context['params']['password'];
            $user = vnbiz_model_find_one('user', [
                'username' => $username  //find by username
            ]);
            // $xxx = [
            //     'model_name' => 'user',
            //     'filter' => [
            //         'email' => $username
            //     ]
            // ];
            // vnbiz_do_action('model_find', $xxx);
            if (!$user) {
                $user = vnbiz_model_find_one('user', [
                    'email' => $username  //find by email
                ]);
            }
            if (!$user) {
                $user = vnbiz_model_find_one('user', [
                    'phone' => $username  //find by phone
                ]);
            }
            if (!$user) {
                $context['code'] = 'invalid_params';
                $context['error'] = "Invalid username or password";
                return;
            }

            if (!password_verify($password, $user['password'])) {
                $context['code'] = 'invalid_params';
                $context['error'] = "Invalid username or password";
                //TODO: limit login attempt
                return;
            }
        } else {
            $context['code'] = 'missing_params';
            $context['error'] = "Missing username or password";
            return;
        }

        vnbiz_user_issue_tokens($user, $context);
    });

    vnbiz_add_action("service_user_me", function (&$context) {
        $user = vnbiz_user();
        if (!$user) {
            throw new VnBizError('Login required', "login_required", null, null, 401);
            return;
        }
        $user = vnbiz_model_find_one('user', ['id' => $user['id']]);

        if ($user['status'] !== 'active') {
            $context['code'] = 'user_status';
            $context['models'] = [];
            return;
        }

        $context['code'] = 'success';
        $context['model_name'] = 'user';
        $user['@permissions'] = $GLOBALS['vnbiz_user_permissions'];
        $user['@permissions_scope'] = $GLOBALS['vnbiz_user_permissions_scope'];
        $context['models'] = [$user];
    });

    vnbiz_add_action("service_user_logout", function (&$context) {
        $context['code'] = 'success';
    });

    vnbiz_add_action('service_db_init_default', function (&$context) {
        $usercount = vnbiz_model_count('user');
        $context['code'] = 'success';
        $context['models'] = [];
        if ($usercount > 0) {
            return;
        }
        $user = vnbiz_model_create('user', [
            'email' => 'superadmin@vnbiz.com',
            'username' => 'superadmin',
            'password' => 'superadmin'
        ]);
        $usergroup = vnbiz_model_create('usergroup', [
            'name' => 'Super Admin Group',
            'description' => 'You know, for Supert Admin user',
            'permissions' => 'super',
            'permissions_scope' => [
                '.' => true
            ]
        ]);
        vnbiz_model_create('useringroup', [
            'user_id' => $user['id'],
            'usergroup_id' => $usergroup['id']
        ]);
        $context['models'] = [$user];
    });
}
