<?php

interface PU_CacheInterface{

	public function set($key);
	public function get($key);
	public function del($key);

	public function multiSet($values);
	public function multiGet($keys);

	public function flush();
	public function exist($key);
}