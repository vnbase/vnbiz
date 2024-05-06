<?php

use VnBiz\VnBizError;

function vnbiz_unique_text() {
    $bytes = random_bytes(16);
    return bin2hex($bytes);
}

class VnbizImage {
    private $image;

    private $func_save;

    private $width;
    private $height;
    
    function __construct($file_path){
        $this->image = $this->read_image_file($file_path);
        $this->width  = imagesx($this->image);
        $this->height = imagesy($this->image);
    }

    public function  get_width() {
        return $this->width;
    }
    
    public function get_height() {
        return $this->height;
    }

    function read_image_file($path) {
        $type = mime_content_type($path);
        switch($type) {
            case 'image/gif':
                $image = imagecreatefromgif($path);
                $this->func_save = 'imagegif';
                break;
            case 'image/bmp':
                $image = imagecreatefrombmp($path);
                $this->func_save = 'imagejpeg';
                break;
            case 'image/jpeg':
                $image = imagecreatefromjpeg($path);
                $this->func_save = 'imagejpeg';
                break;
            case 'image/png':
                $image = imagecreatefrompng($path);
                $this->func_save = 'imagepng';
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($path);
                $this->func_save = 'imagewebp';
                break;
            default:
                throw new Error("Unsupported image type: " . $type, 'unsupport');
        }
        return $image;
    }

    function scale($file_name, $width, $height) {
        $w = imagesx($this->image);
        $h = imagesy($this->image);
        $r = $w/$h;

        $new_width = $r * $height;
        if ($new_width < $width) {
            $new_width = $width;
        }

        $img = imagescale($this->image, $new_width);
        $w = imagesx($img);
        $h = imagesy($img);

        $img = imagecrop($img, ['x' => ($w/2) - ($width/2), 'y' => ($h/2) - ($height/2), 'width' => $width, 'height' => $height]);
        ($this->func_save)($img, $file_name);
    }
}

function vnbiz_s3_get_url_gen($path) {
    // $aws_region = "ap-southeast-1";
    // $s3_bucket = "this.is.for.testing";
    $aws_id = AWS_ACCESS_KEY_ID;
    $aws_key= AWS_ACCESS_KEY_SECRET;
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
    $timestamp = date('Ymd\THisZ\Z');

    $Scope = "$date/$aws_region/$aws_service/aws4_request";

    $Credential = urlencode("$aws_id/$Scope");
    $Expires = 86400; //24 hours

    $CanonicalRequest = $method . "\n"
        . ($path) . "\n"
        . "X-Amz-Algorithm=AWS4-HMAC-SHA256"
        . "&X-Amz-Credential=" . $Credential
        . "&X-Amz-Date=" . $timestamp
        . "&X-Amz-Expires=" . $Expires
        . "&X-Amz-SignedHeaders=host" ."\n"
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


function vnbiz_s3_upload($file_name, $file_path) {
    // $aws_region = "ap-southeast-1";
    // $s3_bucket = "this.is.for.testing";
    $aws_id = AWS_ACCESS_KEY_ID;
    $aws_key= AWS_ACCESS_KEY_SECRET;
    $aws_region = AWS_REGION;
    $s3_bucket = AWS_S3_BUCKET;
    $s3_host = AWS_S3_HOST;
    $s3_scheme = AWS_S3_SCHEME;

    $aws_service = 's3';

    $file_size = filesize($file_path);
    $file_name = urlencode($file_name);
    $file_type = mime_content_type($file_path);
    $file_hash = hash_file('sha256', $file_path);
    // $md5 = md5_file($path);

    date_default_timezone_set('UTC');
    $date = date("Ymd"); //date(DATE_ISO8601, strtotime('2010-12-30 23:21:46'));
    $timestamp = date('Ymd\THis\Z'); //;date('Ymd\THisZ\Z');

    //hash_hmac('sha256', 'hello, world!', 'mykey');
    //urlencode

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
        . "Signature=" . $signature
        ;

    $headers = [
        'Authorization: '. $authorization,
        'Content-Length: ' . $file_size,
        'Content-Type: ' . $file_type,
        'x-amz-content-sha256: ' . $file_hash,
        'x-amz-date: ' . $timestamp,
        'Expect:',
        'Accept:'
    ];

    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$s3_scheme://$host" . $path);
    curl_setopt($ch, CURLOPT_PUT, 1);

    $fh_res = fopen($file_path, 'r');
    curl_setopt($ch, CURLOPT_INFILE, $fh_res);
    curl_setopt($ch, CURLOPT_INFILESIZE, $file_size);

    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    // curl_setopt($ch, CURLINFO_HEADER_OUT, true);

    $result = curl_exec ($ch);
    // $err = curl_error($ch);

    $information = curl_getinfo($ch);
    fclose($fh_res);
    curl_close ($ch);

    // var_dump($information);

    return $result;
    // echo "\n=======================\n";
    // var_dump($StringToSign);
    // var_dump($CanonicalRequest);
}

function vnbiz_init_module_s3() {
    // vnbiz_add_action('db_before_create', function (&$context) {
    //     if ($context['model_name'] === 's3') {
    //     }
    // });
    vnbiz_model_add('s3')
        ->string('name', 'type')
        ->bool('is_image')
        ->int('size', 'width', 'height')
        ->string('path_0', 'path_1', 'path_2', 'path_3', 'path_4', 'path_5', 'path_6', 'path_7', 'path_8', 'path_9')
        ->no_update()
        ->db_begin_create(function (&$context) {
            $file_id = vnbiz_unique_text();
            $model = &$context['model'];
            $file_name = $context['model']['name'];
            // $file_type = $context['model']['type'];
            // $file_size = $context['model']['size'];
            
            $uploaded = 0;
            for($i = 0; $i < 10; $i++) {
                $file_name = $file_id . '_' . $i;
                if (isset($model['path_' . $i])) {
                    $file_path = $model['path_' . $i];
                    $result = vnbiz_s3_upload($file_name, $file_path);
                    if ($result) {
                        $uploaded++;
                        $model['path_' . $i] = '/s3/' . $file_name;
                    } else {
                        unset($model['path_' . $i]);
                    }
                }
            }
        })
        ->db_after_get(function (&$context) {
            $model = &$context['model'];
            for($i = 0; $i < 10; $i++) {
                if (isset($model['path_' . $i]) && $model['path_' . $i] != null) {
                    $model['url_' . $i] = vnbiz_s3_get_url_gen($model['path_' . $i]);
                }
            }
            
        })
        ;
}