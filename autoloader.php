<?php

// autoload function: http://php.net/manual/zh/language.oop5.autoload.php
function __autoload($className){
	$base = dirname(__FILE__);
	$prefix = 'PU_';
	$directories = array(
		'src/',
		'tests/',		
		'src/security/',
		'src/io/',
		'src/http/',
		'src/cache/',
		'src/utils/',		
		);
	$className = substr($className,3);
	foreach($directories as $directory){				
        if(file_exists($cls = $base.'/'.$directory.$className.'.php')){
            require_once $cls;
            return true;
        }
    }             
}

?>