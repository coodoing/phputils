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
		$this->memc->set($key,$value,time()+$expire);
	}

	public function setMulti($items,$expire = 30){
		$this->connect();
		$this->memc->setMulti($items,time()+$expire);
	}

	public function add($key, $value, $expire = 30){
		$this->connect();
		$this->memc->add($key,$value,time()+$expire);
	}

	public function replace($key, $value, $expire = 30){
		$this->connect();
		$expire += time();
		$this->memc->replace($key,$value,$expire);
	}

	public function get($key){
		$this->connect();
		$result = $this->meme->get($key);
		if($result == Memcached::RES_NOTFOUND){
			return result;
		}			
	}

	public function getMulti($keys){
		$this->connect();
		$tmpResult = $this->memc->getMulti($keys);
		$result = array();
		if(is_array($tmpResult)){
		    foreach ($tmpResult as $key=>$value){
		        $result[$key] = $value;
		    }
		}
		return $result;
	}

	public function getAllkeys(){
		$this->connect();	
		$this->memc->getAllkeys();
	}

	public function del($key){
		$this->connect();
		$this->meme->del($key);
	}

	public function flush($timeout = 10){
		$this->connect();
		$this->memc->flush($timeout);
	}

	public function isConnect(){
		return $this->conn;
	}

	public function exist($key){
		$value = $this->get($key);
		if(empty($value))
			return false;
		return true;
	}

	public function close(){
		if($this->conn)
			$this->memc->quit();
	}
}