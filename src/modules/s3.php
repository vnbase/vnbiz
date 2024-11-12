<?php

use VnBiz\VnBizError;

function vnbiz_s3_get_url_gen($path)
{
    // $aws_region = "ap-southeast-1";
    // $s3_bucket = "this.is.for.testing";
    $aws_id = AWS_ACCESS_KEY_ID;
    $aws_key = AWS_ACCESS_KEY_SECRET;
    $aws_region = AWS_REGION;
    $s3_bucket = AWS_S3_BUCKET;
    $s3_host = AWS_S3_HOST;
    $s3_scheme = AWS_S3_SCHEME;


    $aws_service = 's3';

    $path = "/$s3_bucket" . $path;

    $host = $s3_host;

    $method = 'GET';
    date_default_timezone_set('UTC');
    $date = date("Ymd");
    // $timestamp = date('Ymd\THisZ\Z');
    $timestamp = date('Ymd\THis\Z');

    $Scope = "$date/$aws_region/$aws_service/aws4_request";

    $Credential = urlencode("$aws_id/$Scope");
    $Expires = 86400; //24 hours

    $CanonicalRequest = $method . "\n"
        . ($path) . "\n"
        . "X-Amz-Algorithm=AWS4-HMAC-SHA256"
        . "&X-Amz-Credential=" . $Credential
        . "&X-Amz-Date=" . $timestamp
        . "&X-Amz-Expires=" . $Expires
        . "&X-Amz-SignedHeaders=host" . "\n"
        . 'host:' . $host . "\n"
        . "\n"
        . 'host' . "\n"
        . 'UNSIGNED-PAYLOAD';

    $StringToSign = "AWS4-HMAC-SHA256" . "\n"
        . $timestamp . "\n"
        . $Scope . "\n"
        . hash('sha256', $CanonicalRequest);

    $DateKey = hash_hmac('sha256', $date, 'AWS4' . $aws_key, true);
    $DateRegionKey = hash_hmac('sha256', $aws_region, $DateKey, true);
    $DateRegionServiceKey = hash_hmac('sha256', $aws_service, $DateRegionKey, true);
    $SigningKey = hash_hmac('sha256', 'aws4_request', $DateRegionServiceKey, true);
    $signature = hash_hmac('sha256', $StringToSign, $SigningKey);

    $url = "$s3_scheme://$host" . $path . '?X-Amz-Algorithm=AWS4-HMAC-SHA256'
        . "&X-Amz-Credential=" . $Credential
        . "&X-Amz-Date=" . $timestamp
        . "&X-Amz-Expires=" . $Expires
        . "&X-Amz-SignedHeaders=host"
        . "&X-Amz-Signature=" . $signature;

    return $url;
}

function vnbiz_s3_upload($file_name, $file_path, $original_name = null)
{
    $aws_id = AWS_ACCESS_KEY_ID;
    $aws_key = AWS_ACCESS_KEY_SECRET;
    $aws_region = AWS_REGION;
    $s3_bucket = AWS_S3_BUCKET;
    $s3_host = AWS_S3_HOST;
    $s3_scheme = AWS_S3_SCHEME;

    $aws_service = 's3';

    $file_size = filesize($file_path);
    $file_name = urlencode($file_name);
    $file_type = mime_content_type($file_path);
    $file_hash = hash_file('sha256', $file_path);

    date_default_timezone_set('UTC');
    $date = date("Ymd");
    $timestamp = date('Ymd\THis\Z');

    $path = '/' . $s3_bucket . "/s3/$file_name";
    $method = 'PUT';
    $host = $s3_host;

    $CanonicalRequest = $method . "\n"
        . ($path) . "\n"
        . "\n"
        . 'content-length:' . $file_size . "\n"
        . 'content-type:' . $file_type . "\n"
        . 'host:' . $host . "\n"
        . 'x-amz-content-sha256:' . $file_hash . "\n"
        . 'x-amz-date:' . $timestamp . "\n"
        . "\n"
        . 'content-length;content-type;host;x-amz-content-sha256;x-amz-date' . "\n"
        . $file_hash;

    $Scope = "$date/$aws_region/$aws_service/aws4_request";

    $StringToSign = "AWS4-HMAC-SHA256" . "\n"
        . $timestamp . "\n"
        . $Scope . "\n"
        . hash('sha256', $CanonicalRequest);

    $DateKey = hash_hmac('sha256', $date, 'AWS4' . $aws_key, true);
    $DateRegionKey = hash_hmac('sha256', $aws_region, $DateKey, true);
    $DateRegionServiceKey = hash_hmac('sha256', $aws_service, $DateRegionKey, true);
    $SigningKey = hash_hmac('sha256', 'aws4_request', $DateRegionServiceKey, true);
    $signature = hash_hmac('sha256', $StringToSign, $SigningKey);

    $authorization = "AWS4-HMAC-SHA256 "
        . "Credential=$aws_id/$date/$aws_region/$aws_service/aws4_request,"
        . "SignedHeaders=content-length;content-type;host;x-amz-content-sha256;x-amz-date,"
        . "Signature=" . $signature;

    $headers = [
        'Authorization: ' . $authorization,
        'Content-Length: ' . $file_size,
        'Content-Type: ' . $file_type,
        'x-amz-content-sha256: ' . $file_hash,
        'x-amz-date: ' . $timestamp,
        'Expect:',
        'Accept:',
    ];

    if ($original_name) {
        $headers[] = 'Content-Disposition: attachment; filename="' . $original_name . '"';
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$s3_scheme://$host" . $path);
    curl_setopt($ch, CURLOPT_PUT, 1);

    $fh_res = fopen($file_path, 'r');
    curl_setopt($ch, CURLOPT_INFILE, $fh_res);
    curl_setopt($ch, CURLOPT_INFILESIZE, $file_size);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);

    $information = curl_getinfo($ch);
    fclose($fh_res);
    curl_close($ch);

    return $result;
}

function vnbiz_init_module_s3()
{
    // vnbiz_add_action('db_before_create', function (&$context) {
    //     if ($context['model_name'] === 's3') {
    //     }
    // });

    $after_create_or_update = function (&$context) {
        if (isset($context['model'])) {
            $model = &$context['model'];
            if (isset($model['path_thumbnail'])) {
                $model['url_thumbnail'] = vnbiz_s3_get_url_gen($model['path_thumbnail']);
            }
            for ($j = 0; $j < 10; $j++) {
                if (isset($model['path_' . $j])) {
                    $model['url_' . $j] = vnbiz_s3_get_url_gen($model['path_' . $j]);
                }
            }
        }
    };
    vnbiz_model_add('s3')
        ->string('name', 'type')
        ->text('path_thumbnail')
        ->bool('is_image')
        ->int('size', 'width', 'height')
        ->text('path_0', 'path_1', 'path_2', 'path_3', 'path_4', 'path_5', 'path_6', 'path_7', 'path_8', 'path_9')
        ->no_update()
        ->db_before_create(function (&$context) {
            $file_id = vnbiz_unique_text();
            $model = &$context['model'];
            $original_file_name = $context['model']['name'];
            // $file_type = $context['model']['type'];
            // $file_size = $context['model']['size'];

            unset($model['path_thumbnail']); // remove thumbnail path

            if (isset($model['path_0'])) {
                $file_path = $model['path_0'];
                $file_name = $file_id . '_t';
                $gen_file_path = $file_path . '_t';

                $genereated_file_path = vnbiz_generate_thumbnail($file_path, $gen_file_path, 200, 200);
                if ($genereated_file_path) {
                    $result = vnbiz_s3_upload($file_name, $genereated_file_path);
                    if ($result) {
                        $model['path_thumbnail'] = '/s3/' . $file_name;
                    }
                    unlink($gen_file_path);
                }
            }

            $uploaded = 0;
            for ($i = 0; $i < 10; $i++) {
                $file_name = $file_id . '_' . $i;
                if (isset($model['path_' . $i])) {
                    $file_path = $model['path_' . $i];

                    $ofn = $i == 0 ? $original_file_name : ($i . '_' . $original_file_name);

                    $result = vnbiz_s3_upload($file_name, $file_path, $ofn);
                    if ($result) {
                        $uploaded++;
                        $model['path_' . $i] = '/s3/' . $file_name;
                    } else {
                        unset($model['path_' . $i]);
                    }
                }
            }
        })
        ->db_after_find(function (&$context) {
            if (!isset($context['models'])) {
                return;
            }
            $models = &$context['models'];
            foreach ($models as &$model) {
                if (isset($model['path_thumbnail'])) {
                    $model['url_thumbnail'] = vnbiz_s3_get_url_gen($model['path_thumbnail']);
                }
                for ($j = 0; $j < 10; $j++) {
                    if (isset($model['path_' . $j])) {
                        $model['url_' . $j] = vnbiz_s3_get_url_gen($model['path_' . $j]);
                    }
                }
            }
        })
        ->db_after_create($after_create_or_update)
        ->db_after_update($after_create_or_update)
    ;
}

trait vnbiz_trait_s3_file
{

    function s3_image($field_name, ...$sizes)
    {
        $this->schema->add_field($field_name, 'image');

        $image_sizes = [];
        for ($x = 0; $x < sizeof($sizes); $x++) {
            $image_sizes[$x] = $sizes[$x];
            sizeof($image_sizes[$x]) > 1 ?: $image_sizes[$x][1] = $image_sizes[$x][0];
        }
        array_unshift($image_sizes, ['', '']);

        $upload_file = function (&$context) use ($field_name, $sizes, $image_sizes) {
            if (isset($context['model']) && isset($context['model'][$field_name])) {
                if (is_string($context['model'][$field_name])) {
                    $max_file_size_mb = 50;
                    $url = $context['model'][$field_name];
                    $filemodel = vnbiz_download_file_from_url($url, $max_file_size_mb);

                    if ($filemodel === null) {
                        unset($context['model'][$field_name]);
                        return;
                    }
                    $context['model'][$field_name] = $filemodel;
                }

                $file_name = $context['model'][$field_name]['file_name'];
                $file_size = $context['model'][$field_name]['file_size'];
                $file_path = $context['model'][$field_name]['file_path'];
                $file_type = $context['model'][$field_name]['file_type'];

                // assure $file_type is image;
                $image_types = ['image/gif', 'image/bmp', 'image/jpeg', 'image/png', 'image/webp'];
                if (!in_array($file_type, $image_types)) {
                    throw new VnBizError("In field $field_name, Unsupported image type: " . $file_type, 's3_unsupported_image_type');
                }

                $context['temp_files'] = [];

                // $image = new \Gmagick($file_path);
                $image = new \VnbizImage($file_path);

                $s3_model = [
                    'name' => $file_name,
                    'size' => $file_size,
                    'type' => $file_type,
                    'path_0' => $file_path,
                    'is_image' => true,
                    'width' => $image->get_width(),
                    'height' => $image->get_Height(),
                ];

                for ($i = 1; $i < 10; $i++) {
                    if ($i - 1 < sizeof($sizes)) {
                        $size = $sizes[$i - 1];
                        sizeof($size) > 1 ?: $size[1] = $size[0];

                        $new_file = $file_path . '_' . $i;

                        $image->scale($new_file, $size[0], $size[1]);
                        //TODO; resizeimage
                        $s3_model['path_' . $i] = $new_file;
                        $context['temp_files'][] = $new_file;
                    }
                }

                $s3 = vnbiz_model_create('s3', $s3_model);

                $context['model']['@' . $field_name] = &$s3;
                if ($context['model']['@' . $field_name]) {
                    $sizes = $image_sizes;
                    $sizes[0] = [$s3['width'], $s3['height']];
                    $context['model']['@' . $field_name]['@image_sizes'] = $sizes;
                }

                $context['model'][$field_name] = $s3['id'];
            }
        };

        $this->db_before_create($upload_file);
        $this->db_before_update($upload_file);

        $delete_files = function (&$context) {
            if (isset($context['temp_files'])) {
                foreach ($context['temp_files'] as $path) {
                    try {
                        unlink($path);
                    } catch (\Exception $e) {
                    }
                }
            }
        };

        $this->db_after_commit_create($delete_files);
        $this->db_after_commit_update($delete_files);

        $this->db_after_find(function (&$context) use ($field_name, $image_sizes) {
            if (!isset($context['models'])) {
                return;
            }
            $models = &$context['models'];
            $s3_model_ids = [];
            foreach ($models as $model) {
                if (isset($model[$field_name])) {
                    $s3_model_ids[] = $model[$field_name];
                }
            }
            if (sizeof($s3_model_ids) == 0) {
                return;
            }
            
            $s3s = vnbiz_model_find('s3', ['id' => $s3_model_ids]);
            $s3s_map = [];
            foreach ($s3s as &$s3) {
                $s3s_map[$s3['id']] = $s3;
            }
            foreach ($models as &$model) {
                if (isset($model[$field_name]) && isset($s3s_map[$model[$field_name]])) {
                    $model['@' . $field_name] = $s3s_map[$model[$field_name]];
                    if ($model['@' . $field_name]) {
                        $sizes = $image_sizes;
                        $sizes[0] = [$model['@' . $field_name]['width'], $model['@' . $field_name]['height']];
                        $model['@' . $field_name]['@image_sizes'] = $sizes;
                    }
                }
            }
        });
        return $this;
    }

    public function s3_file($field_name)
    {
        $this->schema->add_field($field_name, 'file');

        $upload_file = function (&$context) use ($field_name) {
            if (isset($context['model']) && isset($context['model'][$field_name])) {
                if (is_string($context['model'][$field_name])) {
                    $max_file_size_mb = 50;
                    $url = $context['model'][$field_name];
                    $filemodel = vnbiz_download_file_from_url($url, $max_file_size_mb);
                    if ($filemodel === null) {
                        unset($context['model'][$field_name]);
                        return;
                    }
                    $context['model'][$field_name] = $filemodel;
                }

                $file_name = $context['model'][$field_name]['file_name'];
                $file_size = $context['model'][$field_name]['file_size'];
                $file_path = $context['model'][$field_name]['file_path'];
                $file_type = $context['model'][$field_name]['file_type'];
                $result = vnbiz_model_create('s3', [
                    'name' => $file_name,
                    'size' => $file_size,
                    'type' => $file_type,
                    'path_0' => $file_path
                ]);
                $context['model'][$field_name] = $result['id'];
                $context['model']['@' . $field_name] = $result;
            }
        };

        $this->db_before_create($upload_file);
        $this->db_before_update($upload_file);

        $this->db_after_find(function (&$context) use ($field_name) {
            if (!isset($context['models'])) {
                return;
            }
            $models = &$context['models'];
            $s3_model_ids = [];
            foreach ($models as $model) {
                if (isset($model[$field_name])) {
                    $s3_model_ids[] = $model[$field_name];
                }
            }
            if (sizeof($s3_model_ids) == 0) {
                return;
            }

            $s3s = vnbiz_model_find('s3', ['id' => $s3_model_ids]);
            $s3s_map = [];
            foreach ($s3s as &$s3) {
                $s3s_map[$s3['id']] = $s3;
            }
            foreach ($models as &$model) {
                if (isset($model[$field_name]) && isset($s3s_map[$model[$field_name]])) {
                    $model['@' . $field_name] = $s3s_map[$model[$field_name]];
                }
            }
        });

        return $this;
    }
}
