<?php 
require (__DIR__ . '/test.php');

$isConnected = R::testConnection();
if (!$isConnected) {
    http_response_code(500);
    echo "Can't connect db";
    return;
}

ob_start();

echo "<pre>";
    try {
        vnbiz_sql_alter_tables_echo();
        vnbiz_user_add_default();
    } catch (\Throwable $e) {
        http_response_code(500);
        throw $e;
    }
echo "</pre>";



$output = ob_get_contents();
ob_end_clean();
echo $output;