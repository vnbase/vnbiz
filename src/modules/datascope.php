<?php

use VnBiz\VnBizError;

define('VNBIZ_DATASCOPE_REGEX', '/^(\.\d+)*\.$/');

function vnbiz_init_module_datascope()
{
    $validate_model_scope = function (&$context) {};

    vnbiz_model_add('datascope')
        ->string('name', 'scope')
        ->text('description')
        ->author()
        ->require('name', 'scope', 'created_by')
        ->read_permission('super', 'datascope_read')
        ->write_permission('super', 'datascope_write')
        ->unique("unique", ['scope'])
        ->db_before_create($validate_model_scope)
        ->db_before_update($validate_model_scope)
    ;
}
function vnbiz_get_user_permissions_scope()
{
    if (isset($GLOBALS['vnbiz_user_permissions_scope'])) {
        return $GLOBALS['vnbiz_user_permissions_scope'];
    }
    return [];
}

function vnbiz_current_user_inaccessable_datascope($scopes)
{
    if (!is_array($scopes)) {
        throw new VnBizError('$scopes must be array');
    }
    $user_permissions_scope = isset($GLOBALS['vnbiz_user_permissions_scope']) ? $GLOBALS['vnbiz_user_permissions_scope'] : [];
    foreach ($scopes as $scope) {
        $valid = false;
        foreach ($user_permissions_scope as $key => $value) {
            if (str_starts_with($scope, $key)) {
                $valid = true;
                break;
            }
        }
        if (!$valid) {
            return $scope;
        }
    }
    return null;
}

trait vnbiz_trait_datascope
{
    public $has_datascope = false;

    public function has_datascope($fn_read_permissions = null, $fn_write_permissions = null)
    {
        $field_name = 'datascope';

        $this->schema->add_field($field_name, 'datascope');

        $func_validate_datascope_format = function (&$context) use ($field_name) {
            if (vnbiz_has_key($context, ['model', 'datascope'])) {
                $model = &$context['model'];

                $value = $model[$field_name];
                if (!is_string($value)) {
                    throw new VnBizError("$field_name must be string", 'invalid_model');
                }
                if (!preg_match(VNBIZ_DATASCOPE_REGEX, $value)) {
                    throw new VnBizError("Invalid scope value", 'invalid_model', ['datascope' => $value]);
                }
            } else {
                if ($context['action'] == 'model_create') {
                    throw new VnBizError("Missing datascope value", 'invalid_model', ['datascope' => false]);
                }
            }
        };

        $this->db_before_create($func_validate_datascope_format);
        $this->db_before_update($func_validate_datascope_format);

        $check_write_permissions = function (&$context) use ($field_name, $fn_write_permissions) {
            if (vnbiz_user_has_permissions('super')) {
                return;
            }

            if ($fn_write_permissions) {
                if ($fn_write_permissions($context)) {
                    return;
                }
            }

            if (vnbiz_has_key($context, ['model', 'datascope'])) {
                $model = &$context['model'];

                $value = $model[$field_name];
                if (!is_string($value)) {
                    throw new VnBizError("$field_name must be string", 'invalid_model');
                }
                if (!preg_match(VNBIZ_DATASCOPE_REGEX, $value)) {
                    throw new VnBizError("Invalid scope value", 'invalid_model', ['datascope' => $value]);
                }
                $inaccessible = vnbiz_current_user_inaccessable_datascope([$value]);
                if ($inaccessible) {
                    throw new VnBizError("You are not allowed to access this filter datascope", 'permission', ['datascope' => $inaccessible]);
                }
            } else {
                if ($context['action'] == 'model_create') {
                    throw new VnBizError("Missing datascope value", 'invalid_model', ['datascope' => false]);
                }
            }
        };

        $this->web_before_create($check_write_permissions);
        $this->web_before_update($check_write_permissions);

        $check_find_permissions = function (&$context) use ($field_name, $fn_read_permissions) {
            if (vnbiz_user_has_permissions('super')) {
                return;
            }

            if ($fn_read_permissions) {
                if ($fn_read_permissions($context)) {
                    return;
                }
            }

            vnbiz_user_or_throw(); // must login

            $scopes = [];
            if (vnbiz_has_key($context, ['filter', $field_name])) {
                $scopes = $context['filter'][$field_name];
                if (is_string($scopes)) {
                    $scopes = [$scopes];
                }
                if (is_array($scopes)) {
                    foreach ($scopes as $scope) {
                        if (!is_string($scope)) {
                            throw new VnBizError("All datascope must be string", 'invalid_filter', ['datascope' => false]);
                        }
                        if (!preg_match(VNBIZ_DATASCOPE_REGEX, $scope)) {
                            throw new VnBizError("Invalid scope filter", 'invalid_filter', ['datascope' => $scope]);
                        }
                    }
                } else {
                    throw new VnBizError("Datascope must be string or array of strings", 'invalid_filter', ['datascope' => false]);
                }

                $inaccessable_datascope = vnbiz_current_user_inaccessable_datascope($scopes);
                if ($inaccessable_datascope) {
                    throw new VnBizError("You are not allowed to access this filter datascope", 'permission', ['datascope' => $inaccessable_datascope]);
                }
            } else {
                $scopes = array_keys(vnbiz_get_user_permissions_scope());
                if (empty($scopes)) {
                    throw new VnBizError("You don't have any permissions_scope", 'permission', ['datascope' => false]);
                }
                $context['filter'][$field_name] = $scopes;
            }
        };
        $this->web_before_find($check_find_permissions);
        $this->web_before_update($check_find_permissions);
        $this->web_before_delete($check_find_permissions);

        return $this;
    }
}
