<?php

/*
	Configuration cls file
*/
class Configuration{

	private $config;
	public function __construct(){
		$configuration = array(
			"base_path" => $ROOT,
			"src_path" => $ROOT."/src/",
			"test_path" => $ROOT."/test/",			
			"modules" => array("des",""),


			);
	}
}

