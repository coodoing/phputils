<?php

// autoload function: http://php.net/manual/zh/language.oop5.autoload.php
function __autoload($className){
	$base = dirname(__FILE__);
	$directories = array(
		'src/',
		'tests/',
		/*
		'src/security/',
		'src/base/',
		*/		
		);
	foreach($directories as $directory){		
        if(file_exists($cls = $base.'/'.$directory.$className.'.php')){
            require_once $cls;
            return true;
        }
    }             
}

?>