<?php
// new trait named Model_permission

use VnBiz\VnBizError;

trait Model_permission
{


    public function create_permission(...$permissions)
    {
        $this->web_before_create(function (&$context) use ($permissions) {
            if (isset($GLOBALS['vnbiz_permission_skip']) && $GLOBALS['vnbiz_permission_skip'] == true) {
                return;
            }
            vnbiz_assure_user_has_permissions(...$permissions);
        });
        return $this;
    }

    public function create_permission_or($permissions, $func)
    {
        $this->web_before_create(function (&$context) use ($permissions, $func) {
            if (isset($GLOBALS['vnbiz_permission_skip']) && $GLOBALS['vnbiz_permission_skip'] == true) {
                return;
            }

            if (vnbiz_user_has_permissions(...$permissions)) {
                return;
            }

            if ($func($context)) {
                return;
            }

            throw new VnBizError("Require permissions: " . implode(',', $permissions), 'permission');
        });
        return $this;
    }

    public function update_permission(...$permissions)
    {
        $this->web_before_update(function (&$context) use ($permissions) {
            if (isset($GLOBALS['vnbiz_permission_skip']) && $GLOBALS['vnbiz_permission_skip'] == true) {
                return;
            }
            vnbiz_assure_user_has_permissions(...$permissions);
        });
        return $this;
    }

    public function update_permission_or($permissions, $func)
    {
        $this->web_before_update(function (&$context) use ($permissions, $func) {
            if (isset($GLOBALS['vnbiz_permission_skip']) && $GLOBALS['vnbiz_permission_skip'] == true) {
                return;
            }

            if (vnbiz_user_has_permissions(...$permissions)) {
                return;
            }

            if ($func($context)) {
                return;
            }

            throw new VnBizError("Require permissions: " . implode(',', $permissions), 'permission');
        });
        return $this;
    }

    public function delete_permission(...$permissions)
    {
        $this->web_before_delete(function (&$context) use ($permissions) {
            if (isset($GLOBALS['vnbiz_permission_skip']) && $GLOBALS['vnbiz_permission_skip'] == true) {
                return;
            }

            vnbiz_assure_user_has_permissions(...$permissions);
        });
        return $this;
    }

    public function delete_permission_or($permissions, $func)
    {
        $this->web_before_delete(function (&$context) use ($permissions, $func) {
            if (isset($GLOBALS['vnbiz_permission_skip']) && $GLOBALS['vnbiz_permission_skip'] == true) {
                return;
            }

            if (vnbiz_user_has_permissions(...$permissions)) {
                return;
            }

            if ($func($context)) {
                return;
            }

            throw new VnBizError("Require permissions: " . implode(',', $permissions), 'permission');
        });
        return $this;
    }

    public function find_permission(...$permissions)
    {
        $this->web_before_find(function (&$context) use ($permissions) {
            if (isset($GLOBALS['vnbiz_permission_skip']) && $GLOBALS['vnbiz_permission_skip'] == true) {
                return;
            }
            vnbiz_assure_user_has_permissions(...$permissions);
        });
        return $this;
    }

    public function find_permission_or($permissions, $func)
    {
        $this->web_before_find(function (&$context) use ($permissions, $func) {
            if (isset($GLOBALS['vnbiz_permission_skip']) && $GLOBALS['vnbiz_permission_skip'] == true) {
                return;
            }

            if (vnbiz_user_has_permissions(...$permissions)) {
                return;
            }

            if ($func($context)) {
                return;
            }

            throw new VnBizError("Require permissions: " . implode(',', $permissions), 'permission');
        });
        return $this;
    }

    /**
     * $func receives $context of the new created or updated model which refs to this one
     * use $context['model'][$context['ref_field_name']] to get id;
     */
    public function ref_permission_or($permissions, $func)
    {
        $check_ref_permission = function (&$context) use ($permissions, $func) {
            if (isset($GLOBALS['vnbiz_permission_skip']) && $GLOBALS['vnbiz_permission_skip'] == true) {
                return;
            }

            if (vnbiz_user_has_permissions(...$permissions)) {
                return;
            }

            if ($func($context)) {
                return;
            }

            throw new VnBizError("Require permissions: " . implode(',', $permissions), 'permission');
        };

        $this->on_new_ref($check_ref_permission);

        return $this;
    }


    public function read_permission(...$permissions)
    {
        $this->find_permission(...$permissions);
        return $this;
    }

    public function read_permission_or($permissions, $func)
    {
        $this->find_permission_or($permissions, $func);
        return $this;
    }

    public function read_permission_or_user_id($permissions, $user_id_field_name)
    {
        $this->find_permission_or($permissions, function (&$context) use ($user_id_field_name) {
            if (isset($GLOBALS['vnbiz_permission_skip']) && $GLOBALS['vnbiz_permission_skip'] == true) {
                return;
            }

            $user = vnbiz_user();
            if (!$user) {
                return false;
            }

            if (isset($context['filter'])) {
                if (isset($context['filter'][$user_id_field_name])) {
                    return $context['filter'][$user_id_field_name] == $user['id'];
                }
            }
            return false;
        });
        return $this;
    }

    public function write_permission(...$permissions)
    {
        $this->create_permission(...$permissions);
        $this->update_permission(...$permissions);
        $this->delete_permission(...$permissions);
        return $this;
    }

    public function write_permission_or($permissions, $func)
    {
        $this->create_permission_or($permissions, $func);
        $this->update_permission_or($permissions, $func);
        $this->delete_permission_or($permissions, $func);
        return $this;
    }

    public function write_permission_or_user_id($permissions, $user_id_field_name)
    {
        $create_func = function (&$context) use ($user_id_field_name) {
            $user = vnbiz_user();
            if (!$user) {
                return false;
            }

            if (isset($context['model'])) {
                if (isset($context['model'][$user_id_field_name])) {
                    return $context['model'][$user_id_field_name] == $user['id'];
                }
            }
            return false;
        };
        $this->create_permission_or($permissions, $create_func);

        $update_func = function (&$context) use ($user_id_field_name) {
            $user = vnbiz_user();
            if (!$user) {
                return false;
            }

            if (isset($context['filter'])) {
                if (isset($context['filter'][$user_id_field_name])) {
                    return $context['filter'][$user_id_field_name] == $user['id'];
                }
            }

            return false;
        };
        $this->update_permission_or($permissions, $update_func);
        $this->delete_permission_or($permissions, $update_func);
        return $this;
    }

    public function read_field_permission($fields, $permissions)
    {
        $this->web_after_find(function (&$context) use ($fields, $permissions) {
            $models = &$context['models'];
            if (vnbiz_user_has_permissions(...$permissions) == false) {
                foreach ($models as &$model) {
                    foreach ($fields as $field) {
                        unset($model[$field]);
                    }
                }
            }
        });
        return $this;
    }

    public function write_field_permission($fields, $permissions)
    {
        $this->web_before_find(function (&$context) use ($fields, $permissions) {
            $model = &$context['model'];
            if (vnbiz_user_has_permissions(...$permissions) == false) {
                foreach ($fields as $field) {
                    if (isset($model[$field])) {
                        throw new VnBizError('Field ' .  $field . ' need permisison to write: ' . implode(',', $permissions), 'permission');
                    }
                }
            }
        });
        return $this;
    }

    public function read_field_permission_or($fields, $permissions, $func)
    {
        $this->web_after_find(function (&$context) use ($fields, $permissions, $func) {
            $models = &$context['models'];

            if (vnbiz_user_has_permissions(...$permissions) == false) {
                foreach ($models as &$model) {
                    if (!$func($model)) {
                        foreach ($fields as $field) {
                            unset($model[$field]);
                        }
                    }
                }
            }
        });
        return $this;
    }

    public function write_field_permission_or($fields, $permissions, $func)
    {
        $this->web_before_find(function (&$context) use ($fields, $permissions, $func) {
            $model = &$context['model'];
            if (vnbiz_user_has_permissions(...$permissions) == false) {
                if (!$func($model)) {
                    foreach ($fields as $field) {
                        if (isset($model[$field])) {
                            throw new VnBizError('Field ' .  $field . ' need permisison to write: ' . implode(',', $permissions), 'permission');
                        }
                    }
                }
            }
        });
        return $this;
    }
}
