<?php

class PU_HttpCurl{

	const HTTP_METHOD_GET = 'GET';
	const HTTP_METHOD_POST = 'POST';
	const HTTP_METHOD_PUT = 'PUT';
	const HTTP_METHOD_HEAD = 'HEAD';
	const HTTP_METHOD_DELETE = 'DELETE';
	const HTTP_METHOD_TRACE = 'TRACE';
	const HTTP_METHOD_OPTIONS = 'OPTIONS';

	protected $_curlOptions = array (CURLOPT_HEADER => 1, CURLOPT_USERAGENT => "Mozilla/5.0", CURLOPT_TIMEOUT=>TICKET_TIMEOUT,CURLOPT_CONNECTTIMEOUT => TICKET_TIMEOUT, CURLOPT_FOLLOWLOCATION => 0, CURLOPT_RETURNTRANSFER => true, CURLOPT_FORBID_REUSE => true );
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

	public function setCredentials($username, $password) {
		$this->addHeader ( 'Authorization', 'Basic ' . base64_encode ( $username . ':' . $password ) );
	}

	function setHeaders($headers) {
		if (is_array ( $headers )) {
			foreach ( $headers as $name => $value ) {
				$this->addHeader ( $name, $value );
			}
		}
	}

	public function addHeader($headerName, $headerValue = null) {
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
			$this->_requestCookies = array_merge($this->_requestCookies,$cookiesArr);	
		}
	}	

	public function doGet($url, $query_params = '') {
		if (! empty ( $query_params ) && is_array ( $query_params )) {
			$url .= (strpos($url,'?')===false? '?' : '&') . http_build_query ( $query_params, null, '&' );
			}

        return $this->_request ( self::HTTP_METHOD_GET, $url );
	}

	public function doPost($url, $postVars) {
		return $this->_request ( self::HTTP_METHOD_POST, $url, $postVars );
	}

	public function doPut($url, $putVars=null) {
		return $this->_request ( self::HTTP_METHOD_PUT, $url, $putVars );
	}
	
	public function doDelete($url, $query_params = '') {
		if (! empty ( $query_params ) && is_array ( $query_params )) {
            $url .= (strpos($url,'?')===false? '?' : '&') . http_build_query ( $query_params, null, '&' );
		}

		return $this->_request ( self::HTTP_METHOD_DELETE, $url );
	}

	
	

}