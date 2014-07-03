<?php

$config = array(
	"base_path" => $ROOT,
	"src_path" => $ROOT."/src/",
	"test_path" => $ROOT."/test/",			
	"modules" => array("security","cache","",),
	"filecache_path" => "/tmp/filecache/",
	"memcached" => array("host"=>"127.0.0.1","port"=>"11213"),
	"redis" => array("host"=>"127.0.0.1","port"=>"6379","db"=>2,"timeout"=>3600),


	);