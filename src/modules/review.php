<?php

use VnBiz\VnBizError;

function vnbiz_init_module_review()
{
    $recalculate_rate = function (&$context) {
        // $model_name = $context['model_name'] ?? null;
        // if ($model_name === 'review') {
            $model = $context['model'] ?? $context['old_model'];
            $ref_model_name = $model['model_name'];
            $ref_model_id =$model['model_id'];
            
            // LOCK IN SHARE MODE: make sure the rows are not changed by other transactions
            $rows = R::getAll("SELECT rate, COUNT(rate) as count FROM `review` WHERE model_name=? AND model_id=? GROUP BY rate LOCK IN SHARE MODE", [$ref_model_name, $ref_model_id]);
            // $rows = R::beansToArray($rows);
            // var_dump($rows);

            $all = [
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 0,
                5 => 0,
            ];

            foreach ($rows as $row) {
                $all[$row['rate']] = $row['count'];
            }
            $total = $all[1] + $all[2] + $all[3] + $all[4] + $all[5];
            $rate = 0;
            if ($total > 0) {
                $rate = (($all[1] * 1) + ($all[2] * 2) + ($all[3] * 3) + ($all[4] * 4) + ($all[5] * 5)) / $total;
            }

            R::exec("UPDATE `$ref_model_name` SET review_count=?,review_count_1=?,review_count_2=?,review_count_3=?,review_count_4=?,review_count_5=?,review_rate=? WHERE id=?", [
                $total, $all[1], $all[2], $all[3], $all[4], $all[5],
                $rate,
                $ref_model_id
            ]);
        // }
    };

    // vnbiz_add_action('db_after_create', $recalculate_rate);
    // vnbiz_add_action('db_after_update', $recalculate_rate);
    // vnbiz_add_action('db_after_delete', $recalculate_rate);

    vnbiz_model_add('review')
        ->model_name('model_name')
        ->model_id('model_id')
        ->int('rate')
        ->text('comment')
        ->author()
        ->require('model_name', 'model_id', 'created_by')
        ->unique('unique_review', ['model_name', 'model_id', 'created_by'])
        ->index('rate_index', ['model_name', 'model_id', 'created_by', 'rate'])
        ->db_before_create(function (&$context) {
            $rate = $context['model']['rate'] ?? 0;
            if ($rate < 1 || $rate > 5) {
                throw new VnBizError("Rate must be between 1-5", 'invalid_model');
            }
        })
        ->db_before_update(function (&$context) {
            $rate = $context['model']['rate'] ?? 0;
            if ($rate < 1 || $rate > 5) {
                throw new VnBizError("Rate must be between 1-5");
            }
        })
        ->db_after_commit_create($recalculate_rate)
        ->db_after_commit_update($recalculate_rate)
        ->db_after_commit_delete($recalculate_rate)
        ;
}
