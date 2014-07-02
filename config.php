<?php

$configuration = array(
	"base_path" => $ROOT,
	"src_path" => $ROOT."/src/",
	"test_path" => $ROOT."/test/",			
	"modules" => array("security","cache"),
	"memcached" => array("host"=>"127.0.0.1","port":"11211")
	);