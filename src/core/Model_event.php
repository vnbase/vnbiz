<?php
// new trait named Model_permission

use VnBiz\VnBizError;

trait Model_event
{
    public function web_before_create($func)
    {
        $model_name = $this->schema->model_name;

        vnbiz_add_action("web_before_model_create_$model_name", $func);

        return $this;
    }

    public function web_before_update($func)
    {
        $model_name = $this->schema->model_name;

        vnbiz_add_action("web_before_model_update_$model_name", $func);

        return $this;
    }

    public function web_before_delete($func)
    {
        $model_name = $this->schema->model_name;

        vnbiz_add_action("web_before_model_delete_$model_name", $func);

        return $this;
    }

    public function web_before_find($func)
    {
        $model_name = $this->schema->model_name;

        vnbiz_add_action("web_before_model_find_$model_name", $func);

        return $this;
    }

    public function web_after_create($func)
    {
        $model_name = $this->schema->model_name;

        vnbiz_add_action("web_after_model_create_$model_name", $func);

        return $this;
    }

    public function web_after_update($func)
    {
        $model_name = $this->schema->model_name;

        vnbiz_add_action("web_after_model_update_$model_name", $func);

        return $this;
    }

    public function web_after_delete($func)
    {
        $model_name = $this->schema->model_name;

        vnbiz_add_action("web_after_model_delete_$model_name", $func);

        return $this;
    }

    public function web_after_find($func)
    {
        $model_name = $this->schema->model_name;

        vnbiz_add_action("web_after_model_find_$model_name", $func);

        return $this;
    }

    public function db_before_create($func)
    {
        $model_name = $this->schema->model_name;

        vnbiz_add_action("db_before_create", function (&$context) use ($func, $model_name) {
            if (isset($context['model_name']) && $context['model_name'] == $model_name) {
                $func($context);
            }
        });

        return $this;
    }

    public function db_begin_create($func)
    {
        $model_name = $this->schema->model_name;

        vnbiz_add_action("db_before_create_$model_name", $func);

        return $this;
    }


    public function db_before_update($func)
    {
        $model_name = $this->schema->model_name;

        vnbiz_add_action("db_before_update", function (&$context) use ($func, $model_name) {
            if (isset($context['model_name']) && $context['model_name'] == $model_name) {
                $func($context);
            }
        });

        return $this;
    }

    public function db_begin_update($func)
    {
        $model_name = $this->schema->model_name;

        vnbiz_add_action("db_before_update_$model_name", $func);
        return $this;
    }

    public function db_begin_delete($func)
    {
        $model_name = $this->schema->model_name;

        vnbiz_add_action("db_before_delete", function (&$context) use ($func, $model_name) {
            if (isset($context['model_name']) && $context['model_name'] == $model_name) {
                $func($context);
            }
        });

        return $this;
    }

    public function db_after_create($func)
    {
        $model_name = $this->schema->model_name;
        vnbiz_add_action("db_after_create_$model_name", $func);

        return $this;
    }

    public function db_after_commit_create($func)
    {
        $model_name = $this->schema->model_name;
        vnbiz_add_action("db_after_commit_create_$model_name", $func);

        return $this;
    }

    public function db_after_fetch($func)
    {
        $model_name = $this->schema->model_name;
        vnbiz_add_action("db_after_fetch_$model_name", $func);

        return $this;
    }

    public function db_before_find($func)
    {
        $model_name = $this->schema->model_name;
        // vnbiz_add_action("db_before_find_$model_name", $func);

        vnbiz_add_action("db_before_find", function (&$context) use ($func, $model_name) {
            if (isset($context['model_name']) && $context['model_name'] == $model_name) {
                $func($context);
            }
        });

        return $this;
    }

    public function db_after_find($func)
    {
        $model_name = $this->schema->model_name;
        vnbiz_add_action("db_after_find_$model_name", $func);

        return $this;
    }

    public function db_after_update($func)
    {
        $model_name = $this->schema->model_name;
        vnbiz_add_action("db_after_update_$model_name", $func);

        return $this;
    }

    public function db_after_commit_update($func)
    {
        $model_name = $this->schema->model_name;
        vnbiz_add_action("db_after_commit_update_$model_name", $func);

        return $this;
    }

    public function db_before_delete($func)
    {
        $model_name = $this->schema->model_name;
        vnbiz_add_action("db_before_delete_$model_name", $func);

        return $this;
    }

    public function db_after_delete($func)
    {
        $model_name = $this->schema->model_name;
        vnbiz_add_action("db_after_delete_$model_name", $func);

        return $this;
    }

    public function db_after_commit_delete($func)
    {
        $model_name = $this->schema->model_name;

        return $this;
    }

    public function on_new_ref($func)
    {
        $model_name = $this->schema->model_name;
        vnbiz_add_action("model_new_ref_$model_name", $func);

        return $this;
    }

    public function db_before_count($func)
    {
        $model_name = $this->schema->model_name;

        vnbiz_add_action("db_before_count", function (&$context) use ($func, $model_name) {
            if (isset($context['model_name']) && $context['model_name'] == $model_name) {
                $func($context);
            }
        });

        return $this;
    }
}
