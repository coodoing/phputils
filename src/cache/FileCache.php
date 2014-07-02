<?php

//require_once($ROOT.'src/cache/CacheAbstract.php');

class PU_FileCache extends PU_CacheAbstract{
	
	const DIRECTORY_SEPARATOR = '/';
	public $cachePath;
	public $cacheFileSuffix='.pud';
	public $directoryLevel=0; //the level of sub-directories to store cache files

	public function __construct($cachePath = null) {
		$this->cachePath = $cachePath;
		$this->init();
	}
	
	public function init(){
		parent::init();
		if($this->cachePath===null)
			$this->cachePath= $config["filecache_path"];
		if(!is_dir($this->cachePath))
			mkdir($this->cachePath,0777,true);
	}

	protected function get($key){
		$cacheFile=$this->getCacheFile($key);
		if(($time=@filemtime($cacheFile))>time())
			return unserialize(file_get_contents($cacheFile));
		else if($time>0)
			@unlink($cacheFile);
		return false;
	}

	public function __construct(){

	}

	protected function set($key,$value,$expire){
		if($expire<=0){
			return false;
		}			
		$expire+=time();

		$cacheFile=$this->getCacheFile($key);
        $value = serialize($value);
		if($this->directoryLevel>0)
			@mkdir(dirname($cacheFile),0777,true);
		if(@file_put_contents($cacheFile,$value,LOCK_EX)==strlen($value)){
			@chmod($cacheFile,0777);
			return @touch($cacheFile,$expire);
		}else
			return false;
	}

	protected function add($key,$value,$expire)
	{
		$cacheFile=$this->getCacheFile($key);
		if(@filemtime($cacheFile)>time())
			return false;
		return $this->set($key,$value,$expire);
	}

	protected function del($key)
	{
		$cacheFile=$this->getCacheFile($key);
		return @unlink($cacheFile);
	}

	protected function getCacheFile($key)
	{
		if($this->directoryLevel>0)
		{
			$base=$this->cachePath;
			for($i=0;$i<$this->directoryLevel;++$i)
			{
				if(($prefix=substr($key,$i+$i,2))!==false)
					$base.= self::DIRECTORY_SEPARATOR.$prefix;
			}
			return $base.self::DIRECTORY_SEPARATOR.$key.$this->cacheFileSuffix;
		}
		else
			return $this->cachePath.self::DIRECTORY_SEPARATOR.$key.$this->cacheFileSuffix;
	}
}