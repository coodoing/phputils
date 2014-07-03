<?php

include_once("../autoloader.php");
include_once("../config.php");

//assert(class_exists("Memcached") == false );
//assert(function_exists('memcache_connect') == false );

$memc = new PU_Memcached();
var_dump($memc);
//  
$memc->set('key1', 'This is first value', 60);
$val = $memc->get('key1');
echo "Get key1 value: " . $val ."<br />";
// 
$memc->replace('key1', 'This is replace value', 60);
$val = $memc->get('key1');
echo "Get key1 value: " . $val . "<br />";
//
$memc->del('key1');
$val = $memc->get('key1');
echo "Get key1 value: " . $val . "<br />";
//
$memc->flush();
$val1 = $memc->get('key1');
echo "Get key1 value: ";
print_r($val1);
echo "<br />";
 
$memc->close();