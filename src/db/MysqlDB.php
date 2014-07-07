<?php

class PU_MysqlDB {

	private $link_id;
	private $handle;
	private $is_log;
	private $time;

	public function __construct() {
		$this->time = $this->microtime_float();
		$this->connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE, USE_PCONNECT);
		$this->is_log = DB_LOG;
		if($this->is_log){
			$handle = fopen(DB_LOG_PATH."dblog.txt", "a+");
			$this->handle=$handle;
		}
	}

	// connection to the database
	public function connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect = 0,$charset='utf8') {
		if( $pconnect==0 ) {
			$this->link_id = @mysql_connect($dbhost, $dbuser, $dbpw, true);
			if(!$this->link_id){
				$this->halt("Fail to connect");
			}
		} else {
			$this->link_id = @mysql_pconnect($dbhost, $dbuser, $dbpw);
			if(!$this->link_id){
				$this->halt("Fail to connect");
			}
		}
		if(!@mysql_select_db($dbname,$this->link_id)) {
			$this->halt('Fail to connect');
		}
		@mysql_query("set names ".$charset); // 解决mysql中文 乱码问题
		/*@mysql_query("set names "."utf8");
		@mysql_query("set character_set_client=utf8");  
    	@mysql_query("set character_set_results=utf8"); */
	}

	public function query($sql) {
		$this->write_log("Search ".$sql);
		@mysql_query("set names "."utf8"); // handle the utf-8 problem
		$query = mysql_query($sql,$this->link_id);
		if(!$query) 
			$this->halt('Query Error: ' . $sql);
		return $query;
	}

	public function insert($table,$dataArray) {
		$field = "";
		$value = "";
		if( !is_array($dataArray) || count($dataArray)<=0) {
			$this->halt('No data to insert');
			return false;
		}
		while(list($key,$val)=each($dataArray)) {
			$field .="$key,";
			$value .="'$val',";
		}
		$field = substr( $field,0,-1);
		$value = substr( $value,0,-1);
		$sql = "insert into $table($field) values($value)";
		$this->write_log("Insert ".$sql);
		if(!$this->query($sql)) return false;
		return true;
	}

	public function insert_a($table, $inserts) {
		$values = array_map('mysql_real_escape_string', array_values($inserts)); // callback function
		$keys = array_keys($inserts);
		return mysql_query('INSERT INTO `' . $table . '` (`' . implode('`,`', $keys) . '`) VALUES (\'' . implode('\',\'', $values) . '\')');
	}

	public function update( $table,$dataArray,$condition="") {
		if( !is_array($dataArray) || count($dataArray)<=0) {
			$this->halt('No data to update');
			return false;
		}
		$value = "";
		while( list($key,$val) = each($dataArray))
		$value .= "$key = '$val',";
		$value .= substr( $value,0,-1);
		$sql = "update $table set $value where 1=1 and $condition";
		$this->write_log("Update ".$sql);
		if(!$this->query($sql)) return false;
		return true;
	}

	public function delete( $table,$condition="") {
		if( empty($condition) ) {
			$this->halt('No data to delete');
			return false;
		}
		$sql = "delete from $table where 1=1 and $condition";
		$this->write_log("Deletion ".$sql);
		if(!$this->query($sql)) return false;
		return true;
	}

	// return resultsets
	public function fetch_array($query, $result_type = MYSQL_ASSOC){
		$this->write_log("The resultsets are: ");
		return mysql_fetch_array($query, $result_type);
	}


	//（MYSQL_ASSOC，MYSQL_NUM，MYSQL_BOTH			
	public function get_one($sql,$result_type = MYSQL_ASSOC,$charset='utf8') {
		$query = $this->query($sql);
		$rt =& mysql_fetch_array($query,$result_type);
		$this->write_log("Get one record ".$sql);
		return $rt;
	}

	// get all records
	public function get_all($sql,$result_type = MYSQL_ASSOC,$charset='utf8') {
		$query = $this->query($sql);
		$i = 0;
		$rt = array();
		while($row =& mysql_fetch_array($query,$result_type)) {
			$rt[$i]=$row;
			$i++;			
			/*			
			Test data：
			$this->toString($row);		
			echo json_encode($row);	*/			

		}
		$this->write_log("All the records: ".$sql);
		return $rt;
	}

	// get the records number
	public function num_rows($results) {
		if(!is_bool($results)) {
			$num = mysql_num_rows($results);
			$this->write_log("The records number is ".$num);
			return $num;
		} else {
			return 0;
		}
	}

	// release the resultsets
	public function free_result() {
		$void = func_get_args();
		foreach($void as $query) {
			if(is_resource($query) && get_resource_type($query) === 'mysql result') {
				return mysql_free_result($query);
			}
		}
		$this->write_log("Release the resultset");
	}

	// get the last insert id
	public function insert_id() {
		$id = mysql_insert_id($this->link_id);
		$this->write_log("The last insert id : ".$id);
		return $id;
	}

	public function toString($row)
	{
		$ret = "ID: " . $row["category_id"] . " , Name: " . $row["category_name"];
		echo $ret."<br/>";
	}

	// close the mysql connection
	protected function close() {
		$this->write_log("Connection closed");
		return @mysql_close($this->link_id);
	}

	// error tips
	private function halt($msg='') {
		$msg .= "\r\n".mysql_error();
		$this->write_log($msg);
		die($msg);
	}

	// get fields list
	protected function fields_list($table)
	{
		@ $links = mysql_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD);
		$fields = mysql_list_fields(DB_DATABASE, $table, $links); // SHOW COLUMNS FROM table [LIKE 'name']
		$columns = mysql_num_fields($fields);
		for ($i = 0; $i < $columns; $i++) {
		    echo mysql_field_name($fields, $i) . "<br/>";
		} 
	}

	// destruct
	public function __destruct() {
		$this->free_result();
		$use_time = ($this-> microtime_float())-($this->time);
		$this->write_log("Time consumed by completing whole task: ".$use_time);
		if($this->is_log){
			fclose($this->handle);
		}
	}

	// writen in the log file 
	public function write_log($msg=''){
		if($this->is_log){
			$text = date("Y-m-d H:i:s")." ".$msg."\r\n";
			fwrite($this->handle,$text);
		}
	}

	public function microtime_float() {
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}

	// start transaction
	public function begin(){
         $null = mysql_query("START TRANSACTION", $this->link);
      return mysql_query("BEGIN", $this->link_id);
    }

    // rollback transaction
    public function rollback(){
      return mysql_query("ROLLBACK", $this->link_id);
    }
}

?>