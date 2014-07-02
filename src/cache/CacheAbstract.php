<?php

/*
	Abstract class for cache
*/
abstract class PU_CacheAbstract{

	abstract public function set($key,$value,$expire = 30);
	abstract public function get($key);
	abstract public function del($key);

}