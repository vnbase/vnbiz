<?php

include __DIR__ . '/base/rb.php';
include __DIR__ . '/base/div.php';
include __DIR__ . '/base/jwt.php';

include __DIR__ . '/base/hashids/src/Math/MathInterface.php';
include __DIR__ . '/base/hashids/src/HashidsInterface.php';
include __DIR__ . '/base/hashids/src/Math/Bc.php';
include __DIR__ . '/base/hashids/src/Math/Gmp.php';
include __DIR__ . '/base/hashids/src/HashidsException.php';
include __DIR__ . '/base/hashids/src/Hashids.php';

include __DIR__ . '/vnbiz_functions.php';
include __DIR__ . '/vnbiz_model.php';
include __DIR__ . '/vnbiz_core.php';

include __DIR__ . '/modules/systemconfig.php';
include __DIR__ . '/modules/user.php';
include __DIR__ . '/modules/usermark.php';
include __DIR__ . '/modules/tag.php';
include __DIR__ . '/modules/comment.php';
include __DIR__ . '/modules/review.php';
include __DIR__ . '/modules/sql.php';
include __DIR__ . '/modules/history.php';
include __DIR__ . '/modules/s3.php';
include __DIR__ . '/modules/template.php';
include __DIR__ . '/modules/email.php';
include __DIR__ . '/modules/jsonschema.php';
include __DIR__ . '/modules/typescriptschema.php';


date_default_timezone_set("UTC");
ini_set('session.cookie_samesite', 'None');
session_start();

vnbiz()
	->init_modules('systemconfig', 'user', 'usermark', 'comment', 'tag', 'review', 'history', 's3', 'template', 'email');
