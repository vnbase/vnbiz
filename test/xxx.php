<?php

include "../vnbiz.php";


$x =  vnbiz_encrypt_id(1223);
echo $x .'\n';
echo vnbiz_decrypt_id($x);