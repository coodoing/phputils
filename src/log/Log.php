<?php

class PU_Log{

	private $isLog;
	private $logPath;

	const LOG_LEVEL_BI = 0;
    const LOG_LEVEL_ERR = 1;
    const LOG_LEVEL_WARNING = 2;
    const LOG_LEVEL_INFO = 3;
    const LOG_LEVEL_DEBUG = 4;

    const LOG_NOT_SPLIT = 0;
    const LOG_SPLIT_BY_TIME = 1;
    const LOG_SPLIT_BY_LEVEL = 2;
    const LOG_SPLIT_BY_TIME_AND_LEVEL = 3;

    public $logLevelWord = array('0'=>'BI',
                                '1'=>'ERROR',
                                '2'=>'WARNING',
                                '3'=>'INFO',
                                '4'=>'DEBUG',
                                );

	public function __construct() {
		$this->isLog = true;
		$this->logPath = '/var/logs/';
	}

	protected function writeLog($key, $type, $msg=''){
		if($this->isLog){
			// identify ::: convenient to separate and analysis
			$text = $key . ":::" . $type . ":::" . date("Y-m-d H:i:s") . ":::" . $msg . "\r\n" ;
			$file = $this->logPath . $key . '-' . date("Y-m-d");
			if(file_exists($file)){
				$handler = fopen($file, "a+");				
			}else{
				$handler = fopen($file, "w+");
			}
			fwrite($handler,$text);
			fclose($handler);			
		}
	}

	public function logBI($key,$msg){
    	$this->writeLog($key, "logBI", $msg);
    }       

    public function logError($msg){
    	$this->writeLog($key, "logError", $msg);
    }       

    public function logWarning($msg){
    	$this->writeLog($key, "logWarning", $msg);
    }   

    public function logInfo($msg){
    	$this->writeLog($key, "logInfo", $msg);
    }   

    public function logDebug($msg){
    	$this->writeLog($key, "logDebug", $msg);
    }   
}