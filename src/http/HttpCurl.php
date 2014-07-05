<?php

class PU_HttpCurl{

	const HTTP_METHOD_GET = 'GET';
	const HTTP_METHOD_POST = 'POST';
	const HTTP_METHOD_PUT = 'PUT';
	const HTTP_METHOD_HEAD = 'HEAD';
	const HTTP_METHOD_DELETE = 'DELETE';
	const HTTP_METHOD_TRACE = 'TRACE';
	const HTTP_METHOD_OPTIONS = 'OPTIONS';

	protected $_curlOptions = array (
			CURLOPT_HEADER => 1, 
			CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35", 
			CURLOPT_TIMEOUT=>'30',
			CURLOPT_CONNECTTIMEOUT => '30', 
			CURLOPT_FOLLOWLOCATION => 0, 
			CURLOPT_RETURNTRANSFER => true, 
			CURLOPT_FORBID_REUSE => true );
	protected $_requestHeaders = array ();
	protected $_requestCookies = array();	
	
	public function __construct(){

	}

	public function reset() {
		return $this;
	}

	public function setCurlOption($name, $value) {
		$this->_curlOptions [$name] = $value;
	}

	/**
	 * define a set of HTTP headers to be sent to the server
	 **/
	public function setHeaders($headers) {
		if (is_array ( $headers )) {
			foreach ( $headers as $name => $value ) {
				$this->addHeader ( $name, $value );
			}
		}
	}

	protected function addHeader($headerName, $headerValue = null) {
		$lower_name = strtolower ( $headerName );		
		// Check if $name needs to be split
		if ($headerValue === null && (strpos ( $headerName, ':' ) > 0)) {
			list ( $headerName, $headerValue ) = explode ( ':', $headerName, 2 );
		}		
		// Make sure the name is valid
		if (! preg_match ( '/^[a-zA-Z0-9-]+$/', $headerName )) {
			return false;
		}		
		// If $value is null or false, unset the header
		if ($headerValue === null || $headerValue === false) {
			unset ( $this->_requestHeaders [$lower_name] );
			return false;		
		// Else, set the header
		} else {
			// Header names are stored lowercase internally.
			if (is_string ( $headerValue )) {
				$headerValue = trim ( $headerValue );
			}
			$this->_requestHeaders [$lower_name] = array ($headerName, $headerValue );
		}
		return true;	
	}

	public function removeHeader($headerName) {
		$lower_name = strtolower ( $headerName );
		unset ( $this->_requestHeaders [$lower_name] );
	}

	public function addCookies($cookiesArr){
		if (is_array($cookiesArr)){
			// be cautious about the use of array_merge
			$this->_requestCookies = array_merge($this->_requestCookies,$cookiesArr);	
		}
	}	

	public function doGet($url, $query_params = '') {
		if (! empty ( $query_params ) && is_array ( $query_params )) {
			$url .= (strpos($url,'?')===false? '?' : '&') . http_build_query ( $query_params, null, '&' );
		}
        return $this->curl ( self::HTTP_METHOD_GET, $url );
	}

	public function doPost($url, $postVars) {
		return $this->curl ( self::HTTP_METHOD_POST, $url, $postVars );
	}

	public function doPut($url, $putVars=null) {
		return $this->curl ( self::HTTP_METHOD_PUT, $url, $putVars );
	}
	
	public function doDelete($url, $query_params = '') {
		if (! empty ( $query_params ) && is_array ( $query_params )) {
            $url .= (strpos($url,'?')===false? '?' : '&') . http_build_query ( $query_params, null, '&' );
		}
		return $this->curl ( self::HTTP_METHOD_DELETE, $url );
	}

	public function doHead($url) {
		return $this->curl ( self::HTTP_METHOD_HEAD, $url );
	}

	/*
	 * do the http request curl
	 * normal procedure of the curl mehotd:
	 * 1 $ch = curl_init($url)
	 * 2 curl_exec($ch)
	 * 3 curl_close($ch)
	*/
	final private function curl($method, $url, $postargs = array()) {
	    if(isset($_GET['curl_debug'.CSS_JS_VERSION.'_start']) && $_GET['curl_debug'.CSS_JS_VERSION.'_start'] == 100){
	        list($usec, $sec) = explode(" ", microtime());
		    $timedebug1 = ((float)$usec + (float)$sec);	
	    }
		// Get the curl ch_session object
		$ch_session = curl_init ( $url );		
		foreach ( $this->_curlOptions as $name => $value ) {
			curl_setopt ( $ch_session, $name, $value );
		}		
		//set http header options
		if (! empty ( $this->_requestHeaders ) && is_array ( $this->_requestHeaders )) {
			$headers = $this->preProcessHeaders ();
			curl_setopt ( $ch_session, CURLOPT_HTTPHEADER, $headers );
		}
		//set cookies to header
		if (!empty($this->_requestCookies)){
			$cookies = $this->preProcessCookies();
			curl_setopt($ch_session, CURLOPT_COOKIE, $cookies);
		}
		if ($method == self::HTTP_METHOD_POST) {
			curl_setopt ( $ch_session, CURLOPT_POST, 1 );
			curl_setopt ( $ch_session, CURLOPT_POSTFIELDS, $postargs );
		}
		elseif ($method == self::HTTP_METHOD_PUT) {
            curl_setopt ( $ch_session, CURLOPT_CUSTOMREQUEST, 'PUT');
            $headers[] = 'Content-Length: ' . strlen($postargs);
            curl_setopt ( $ch_session, CURLOPT_HTTPHEADER, $headers);
			curl_setopt ( $ch_session, CURLOPT_POSTFIELDS, $postargs );
		}
		elseif ($method == self::HTTP_METHOD_DELETE) {
			// A custom request method to use instead of "GET" or "HEAD" when doing a HTTP request
            curl_setopt ( $ch_session, CURLOPT_CUSTOMREQUEST, 'DELETE');
		}
        
		// Do the curl and then close the ch_session
		$response = curl_exec ( $ch_session );
		$curl_info = curl_getinfo ( $ch_session );
		
		$httpResp = null;
		if (curl_errno ( $ch_session )) {
			$err_msg = curl_error ( $ch_session );
		} else {
			curl_close ( $ch_session );
			$httpResp = $this->postProcessHeaders ( $response );
		}
		return $httpResp;
	}

	protected function preProcessHeaders() {		
		$headers = array ();		
		foreach ( $this->_requestHeaders as $header ) {
			list ( $name, $value ) = $header;
			if (is_array ( $value )) {
				$value = implode ( ', ', $value );
			}			
			$headers [] = "$name: $value";
		}		
		return $headers;	
	}

	protected function preProcessCookies(){
		$cookies = array();
		if (is_array($this->_requestCookies)){
			foreach($this->_requestCookies as $key => $value){
				if(!empty($key)){
					$cookies[] = $key . '=' . $value;
				}
			}
			return implode ( '; ', $cookies );
		}
		return false;
	}

	protected function postProcessHeaders($response){
		return $response;
	}
}