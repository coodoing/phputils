<?php

class PU_Log{

	private $handler;
	private $isLog;

	const LOG_LEVEL_BI = 0;
    const LOG_LEVEL_ERR = 1;
    const LOG_LEVEL_WARNING = 2;
    const LOG_LEVEL_INFO = 3;
    const LOG_LEVEL_DEBUG = 4;

    const LOG_NOT_SPLIT = 0;
    const LOG_SPLIT_BY_TIME = 1;
    const LOG_SPLIT_BY_LEVEL = 2;
    const LOG_SPLIT_BY_TIME_AND_LEVEL = 3;

    public static $logLevelWord = array('0'=>'BI',
                                        '1'=>'ERROR',
                                        '2'=>'WARNING',
                                        '3'=>'INFO',
                                        '4'=>'DEBUG',
                                        );

	public function __construct() {
		$this->isLog = true;
		if($this->isLog){
			$handler = fopen("dblog.txt", "a+");
			$this->handler=$handler;
		}
	}

	protected function writeLog($type,$msg=''){
		if($this->isLog){
			$text = $type.":::".date("Y-m-d H:i:s").":::".$msg."\r\n";
			fwrite($this->handler,$text);
		}
	}

	public function __destruct() {
		if($this->isLog){
			fclose($this->handler);
		}
	}

	public static function logBI($msg){
    	self::writeLog("logBI",$msg);
    }       

    public static function logError($msg){
    	self::writeLog("logBI",$msg);
    }       

    public static function logWarning($msg){
    	self::writeLog("logBI",$msg);
    }   

    public static function logInfo($msg){
    	self::writeLog("logBI",$msg);
    }   

    public static function logDebug($msg){
    	self::writeLog("logBI",$msg);
    }   
}