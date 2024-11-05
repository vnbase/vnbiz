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

function vnbiz_user_add_default()
{
    $usercount = vnbiz_model_count('user');
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
        'permission_scope' => [
            '.' => true
        ]
    ]);
    vnbiz_model_create('useringroup', [
        'user_id' => $user['id'],
        'usergroup_id' => $usergroup['id']
    ]);
    return $user;
}

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
        ->string('username') //TODO: validate
        ->string('timezone')
        ->string('fuid')
        ->string('language')
        ->string('google_sub')
        ->password('password')
        ->text('bio', 'note')
        ->enum('status', ['active', 'inactive', 'deleted'], 'active')
        ->has_usermarks('follow')
        ->author()
        ->unique('user_unique_email', ['email'])
        ->unique('user_unique_username', ['username'])
        ->unique('user_unique_fuid', ['fuid'])
        ->require('email')
        ->no_delete()
        ->text_search('alias', 'first_name', 'last_name', 'email', 'note', 'username')
        ->read_field_permission_or(['email', 'fuid', 'status', 'first_name', 'last_name'], ['super', 'user_read'], function (&$model) {
            $user = vnbiz_user();
            if ($user && $user['id'] == vnbiz_decrypt_id($model['id'])) {
                return true;
            }
            return false;
        })
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
        if (isset($_SERVER["HTTP_AUTHORIZATION"])) {
            $auth = $_SERVER["HTTP_AUTHORIZATION"];
            $auth_array = explode(" ", $auth);
            $un_pw = explode(":", base64_decode($auth_array[1]));
            $email = $un_pw[0];
            $password = $un_pw[1];

            if ($email && $password) {
                $user = vnbiz_model_find_one('user', [
                    'email' => $email
                ]);
                if ($user) {
                    if (password_verify($password, $user['password'])) {
                        $_SESSION['user_id'] = $user['id'];
                    }
                } else {
                    session_destroy();
                }
            }
        }

        $token = vnbiz_getBearerToken();
        if ($token) {

            $arr = vnbiz_token_verify($token, 'vnbizsecret');

            if ($arr && $arr['user_id']) {
                $user = vnbiz_model_find_one('user', ['id' => $arr['user_id']]);

                if ($user) {
                    if ($user['status'] != 'active') {
                        throw new VnBizError("User status is: " . $user['status'], 'user_status', null, null, 403);
                    }

                    $GLOBALS['vnbiz_user'] = $user;

                    $find_context = [
                        'model_name' => 'useringroup',
                        'filter' => [
                            'user_id' => $user['id'],
                        ],
                        'meta' => [
                            'ref' => true
                        ]
                    ];
                    vnbiz_model_search($find_context);
                    $permissions = [];
                    $permissions_scopes = [];
                    foreach ($find_context['models'] as $useringroup) {
                        if (isset($useringroup['@usergroup_id'])) {
                            if (isset($useringroup['@usergroup_id']['permissions'])) {
                                foreach (explode(',', $useringroup['@usergroup_id']['permissions']) as $per) {
                                    isset($permissions[$per]) ?: $permissions[$per] = [];
                                    $permissions[$per][] = $useringroup['@usergroup_id']['id'];
                                }
                            }
                            if (isset($useringroup['@usergroup_id']['permissions_scope'])) {
                                foreach (array_keys($useringroup['@usergroup_id']['permissions_scope']) as $scope) {
                                    $permissions_scopes[$scope] = true;
                                }
                            }
                        }
                    }
                    $GLOBALS['vnbiz_user_permissions'] = $permissions;
                    $GLOBALS['vnbiz_user_permissions_scope'] = $permissions_scopes;
                } else {
                    unset($_SESSION['user_id']);
                }
            } else {
                throw new VnBizError('Invalid bearer token', "invalid_token", null, null, 401);
            }

            return; // skip other
        }

        $has_session = session_status() == PHP_SESSION_ACTIVE;
        if ($has_session) {
            if (isset($_SESSION['user_id']) && !isset($GLOBALS['vnbiz_user'])) {

                $find_context = [
                    'model_name' => 'useringroup',
                    'filter' => [
                        'user_id' => $_SESSION['user_id']
                    ],
                    'meta' => [
                        'ref' => true
                    ]
                ];
                vnbiz_model_search($find_context);

                if (sizeof($find_context['models']) > 0) {
                    $GLOBALS['vnbiz_user'] = $find_context['models'][0]['@user_id'];
                    // $GLOBALS['vnbiz_user_permissions'] []

                    $permissions = [];
                    $permissions_scopes = [];
                    foreach ($find_context['models'] as $useringroup) {
                        if (isset($useringroup['@usergroup_id'])) {
                            if (isset($useringroup['@usergroup_id']['permissions'])) {
                                foreach (explode(',', $useringroup['@usergroup_id']['permissions']) as $per) {
                                    isset($permissions[$per]) ?: $permissions[$per] = [];
                                    $permissions[$per][] = $useringroup['@usergroup_id']['id'];
                                }
                            }
                            if (isset($useringroup['@usergroup_id']['permissions_scope'])) {
                                foreach (array_keys($useringroup['@usergroup_id']['permissions_scope']) as $scope) {
                                    $permissions_scopes[$scope] = true;
                                }
                            }
                        }
                    }
                    $GLOBALS['vnbiz_user_permissions'] = $permissions;
                    $GLOBALS['vnbiz_user_permissions_scope'] = $permissions_scopes;


                    // if (isset($GLOBALS['vnbiz_user']) && isset($GLOBALS['vnbiz_user']['id'])) {

                    // }
                } 
                // else {
                //     $user = vnbiz_model_find_one('user', ['id' => $_SESSION['user_id']]);

                //     if ($user) {
                //         $GLOBALS['vnbiz_user'] = $user;
                //         $GLOBALS['vnbiz_user_permissions'] = [];
                //         $GLOBALS['vnbiz_user_permissions_scope'] = [];
                //     } else {
                //         unset($_SESSION['user_id']);
                //     }
                // }
            }
        }
    });

    vnbiz_add_action("service_user_login", function (&$context) {
        if (!$context['params'] || !isset($context['params']['email']) || !isset($context['params']['password'])) {
            $context['code'] = 'missing_params';
            $context['error'] = "Missing email or password";
            return;
        }

        $email = $context['params']['email'];
        $password = $context['params']['password'];

        $c = [
            'model_name' => 'user',
            'email' => $email
        ];

        $user = vnbiz_model_find_one('user', [
            'email' => $email
        ]);

        if (!$user) {
            $context['code'] = 'invalid_params';
            $context['error'] = "Invalid email or password";
            return;
        }

        if (!password_verify($password, $user['password'])) {
            $context['code'] = 'invalid_params';
            $context['error'] = "Invalid email or password";
            return;
        }

        $GLOBALS['vnbiz_user'] = $user;


        $context['code'] = 'success';
        $context['error'] = null;
        $context['models'] = [$user];

        $context['access_token'] = vnbiz_token_sign(['user_id' => $user['id']], 'vnbizsecret');

        $_SESSION['user_id'] = $user['id'];
    });

    vnbiz_add_action("service_user_me", function (&$context) {
        $user = vnbiz_user();
        if (!$user) {
            $context['code'] = 'login_required';
            $context['models'] = [];
            return;
        }
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
        session_destroy();
    });

    // vnbiz_add_action('sql_gen_index', function (&$context) {
    // 	isset($context['sql']) ?: $context['sql'] = '';
    // 	$context['sql'] .= "
    // 		CREATE UNIQUE INDEX IF NOT EXISTS user_unique_email ON `user` (email);
    // 	";
    // });

}
