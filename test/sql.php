<?php 
require (__DIR__ . '/test.php');

(vnbiz_sql_alter_tables());
echo "<pre>";
echo vnbiz_sql_generate();
echo "</pre>";