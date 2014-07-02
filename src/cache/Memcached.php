<?php

//require_once($ROOT.'src/cache/CacheAbstract.php');
//require_once($ROOT.'src/Exception.php');
//require_once($ROOT.'config.php');

class PU_Memcached extends PU_CacheAbstract {	
	private $memc;
	private $host;
	private $port;
	private $conn = false;
	public function __construct(){		
		if(!function_exists('memcache_connect') && !class_exists("Memcached")){
			throw new Exception("Memcache functions not available");
		}else{		
			$this->memc = new Memcached();	
			$this->conn = true;
			$this->host = $config['memcached']['host'];
			$this->port = $config['memcached']['port'];	
			$this->memc->addServer($this->host,$this->port);		
		}
		
	}

	public function connect(){
		if($this->conn)
			return;
		$this->memc = new Memcached();
		$this->conn = true;
		$this->memc->addServer($this->host,$this->port);
	}

	public function set($key,$value,$expire = 30){
		$this->connect();
		$this->meme->set($key,$value,$expire);
	}

	public function setMulti($items,$expire = 30){
		
	}

	public function get($key){
		$this->connect();
		$result = $this->meme->get($key);
		if($result != Memcached::RES_NOTFOUND){

		}	
		return result;
	}

	public function getMulti($keys){

	}

	public function getAllkeys(){
		
	}

	public function del($key){
		$this->connect();
		$this->meme->del($key);
	}

	public function isConnect(){
		return $this->conn;
	}

	public function close(){
		if($this->conn)
			$this->memc->quit();
	}
}