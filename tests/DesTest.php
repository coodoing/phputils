<?php

include_once("../autoloader.php");
$des = new PU_Des('key');
$text = 'encrypt';
echo 'Input data: '.$text. '<br>';
$data = $des->encrypt($text);
echo 'After encrypt: '.$data . '<br>';
$origin = $des->decrypt($data);
echo 'After encrypt: '.$origin . '<br>'; 

?>