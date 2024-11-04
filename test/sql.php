<?php 
require (__DIR__ . '/test.php');

$isConnected = R::testConnection();
if (!$isConnected) {
    http_response_code(500);
}

(vnbiz_sql_alter_tables());
echo "<pre>";
echo vnbiz_sql_generate();
echo "</pre>";

vnbiz_user_add_default();