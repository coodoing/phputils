<?php

//require_once($ROOT.'src/cache/CacheAbstract.php');

class PU_RedisCache extends PU_CacheAbstract{
	private $redis;
	private $servers;
	public function __construct(){
		$this->servers = $config["host"];
		if ($this->redis !== null) {
			return $this->redis;
		} else {
			if (class_exists ( 'redis' )) {
				return $this->redis = new Redis ();
			}
		}
	}

	protected function init(){
		if (!empty( $this->servers ) && is_array($this->servers)) {
			foreach ( $this->servers as $server ) {
				$ret = $this->redis->connect ( $server ['host'], $server ['port'], $server ['timeout'] );
                if($server ['db']>0){
                    $ret = $this->redis->select( $server ['db'] );
                }                
			}
		} else{
			$ret = $this->redis->connect ( '127.0.0.1', 6379 );
            $ret = $this->redis->select(0);
		}
	}

	public function set($key,$value,$expire = 30){
		$expire += time();
		return $this->redis->set ( $key, $value, $expire );
	}

	protected function add($key, $value, $expire) {
		$expire += time ();
		$ret = $this->redis->setnx ( $key, $value );
        if($ret && $expire) {
            $ret = $this->redis->expire( $key, $expire );
        }
        return $ret;
	}

	public function get($key){
		return $this->redis->get ( $key );
	}

	public function getMulti($keys){
		return $this->redis->getMultiple ( $keys );
	}

	public function del($key){
		return $this->redis->delete ( $keys );
	}	

	public function escapeKey($key){
		return md5($key);
	}

	public function multiGet($keys){
		$uniqueIDs=array();
		$results=array();
		foreach($keys as $key){
			$uniqueIDs[]=$this->escapeKey($key);
			$results_keys[]=$key;
		}
		$values=$this->getMulti($uniqueIDs);
		foreach($uniqueIDs as $key=>$uniqueID){
			$results[$results_keys[$key]] = $values[$key];
		}
		return $results;
	}
}