<?php

namespace VnBiz;

class Actions
{
    private $actions = [];

    public function add_action($action, $func)
    {
        // echo "add_action: $action\n";
        if (!is_callable($func)) {
            throw new VnBizError("vnbiz: handler must be callable!");
        }

        if (!isset($this->actions[$action])) {
            $this->actions[$action] = [];
        }

        $this->actions[$action][] = $func;

        return $this;
    }

    public function has_action($action)
    {
        return isset($this->actions[$action]);
    }

    public function add_action_one($action, $func)
    {
        // echo "add_action_one: $action\n";
        if (!is_callable($func)) {
            throw new VnBizError("vnbiz: handler must be callable!");
        }

        if (!isset($this->actions[$action])) {
            $this->actions[$action] = [];
        }

        $this->actions[$action] = [$func];

        return $this;
    }

    public function do_action($action, &$context = [])
    {
        // $str = '';
        // $model_name = '';
        // $meta = '';
        // if (isset($context['filter'])) {
        //     $str = json_encode($context['filter']);
        // }
        // if (isset($context['model_name'])) {
        //     $model_name = $context['model_name'];
        // }
        // if (isset($context['meta'])) {
        //     $meta = json_encode($context['meta']);
        // }
        // echo ("do_action: $action, model: $model_name, fitler: $str, meta: $meta \n");

        if (isset($this->actions[$action])) {
            foreach ($this->actions[$action] as $func) {
                call_user_func_array($func, [&$context]);
            }
        }
    }

    public function do_action_one($action, &$context = [])
    {
        if (isset($this->actions[$action])) {
            if (sizeof($this->actions[$action]) > 0) {
                $func = $this->actions[$action][0];
                call_user_func_array($func, [&$context]);
            }
        }
    }
}
