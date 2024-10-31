<?php

use VnBiz\VnBizError;

include_once(__DIR__ . "/../vendor/autoload.php");
include_once(__DIR__ . "/../src/vnbiz.php");


vnbiz()
	->init_app('test')
	->init_db_mysql('mysql8', 'root', 'rootpass', 'vnbiz_dev')
	->init_aws('minioroot', 'rootpass', 'ap-southeast-1', 'vnbizbucket', "minio:9000", 'http')
	->init_redis('redis7')
	;

	
include_once(__DIR__ . "/../example/types.php");


vnbiz()->start();
