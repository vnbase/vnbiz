<?php

use VnBiz\VnBizError;


function vnbiz_init_module_editing_by() {

    /**
     * action: service_editing_by_me
     * params: model_id, model_name
     */
    vnbiz_add_action("service_editing_by_me", function (&$context) {
        $current_user_id = vnbiz_user_id();
        if (!$current_user_id) {
            throw new VnBizError("Login required", 'login_required', null, null, 401);
        }

        if (isset($context['params'])) {
            if (!isset($context['params']['model_id']) || !isset($context['params']['model_name'])) {
                throw new VnBizError("model_id && model_name is required", 'invalid_params', null, null, 400);
            }
        } else {
            throw new VnBizError("Missing params", 'invalid_params', null, null, 400);
        }

        $model_id = $context['params']['model_id'];
        $model_name = $context['params']['model_name'];

        //asure model_id && model_name is valid

        vnbiz_assure_model_name_exists($model_name);

        $web_find_one_context = [
            'action' => 'model_find',
            'model_name' => $model_name,
            'filter' => [
                'id' => $model_id
            ],
            'meta' => [
                'limit' => 1
            ]
        ];

        vnbiz_do_action('web_model_find', $web_find_one_context);

        $model = isset($web_find_one_context['models']) ? $web_find_one_context['models'][0] : null;

        if (!$model) {
            throw new VnBizError("Model not found", 'model_not_found', [
                'find_code' => $web_find_one_context['code'],
                'find_message' => isset($web_find_one_context['message']) ? $web_find_one_context['message'] : null
            ], null, 400);
        }

        $redis_key = "editing_by:$model_name:$model_id";;
        $user = vnbiz_redis_get_array("editing_by:$model_name:$model_id");

        if ($user && isset($user['id']) && $user['id'] != $current_user_id) {
            $context['code'] = 'editing_by_other';
            $context['models'] = [
                [
                    '@editing_by' => [
                        'id' => vnbiz_encrypt_id($user['id'])
                    ]
                ]
            ];
            return;
        }

        $expires_in = 15;

        $user = [
            'id' => $current_user_id
        ];
        vnbiz_redis_set_array($redis_key, $user, $expires_in);

        $context['code'] = 'success';
        $context['models'] = [
            [
                'v' => $model['v'],
            ]
        ];
        $context['expires_in'] = $expires_in;
    });


}

trait vnbiz_trait_editing_by
{
    public $has_editing_by = false;

    public function has_editing_by($permissions = [], $fn_can_by_pass = null)
    {
        // assure this model has "v" field;

		if (!isset($this->schema->schema['v'])) {
			throw new Error("has_v() is required to use has_editing_by()");
		}

        // web_before_update this model will check if editing_by is null or current_user_id
        $func_validate_editing_by = function (&$context) use ($permissions, $fn_can_by_pass) {
            if (vnbiz_user_has_permissions('super')) {
                return;
            }
            if (vnbiz_user_has_permissions(...$permissions)) {
                return;
            }
            if ($fn_can_by_pass && $fn_can_by_pass($context)) {
                return;
            }

            if (isset($context['filter']) && isset($context['filter']['id']) && isset($context['model_name'])) {
                $filter_model_id = $context['filter']['id'];
                if (is_array($filter_model_id)) {
                    throw new VnBizError("filter[id] must be scalar", 'unsupported', null, null, 400);
                }

                $model_name = $context['model_name'];
                $decoded_model_id = vnbiz_decrypt_id($filter_model_id);

                $redis_key = "editing_by:$model_name:$decoded_model_id";;
                $user = vnbiz_redis_get_array($redis_key);
                if ($user) {
                    $current_user_id = vnbiz_user_id();
                    if ($user['id'] != $current_user_id) {
                        throw new VnBizError("Model is being edited by another user", 'editing_by_other', [
                            '@editing_by' => [
                                'id' => vnbiz_encrypt_id($user['id'])
                            ]
                        ], null, 400);
                    }
                }
            }
        };

        $this->web_before_update($func_validate_editing_by);

        return $this;
    }
}