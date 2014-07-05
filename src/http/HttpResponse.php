<?php

class PU_HTTPResponse(){
	private $messages = array (
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',

        // Success 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',  // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        // 306 is deprecated but reserved
        307 => 'Temporary Redirect',
	
        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',

        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded'
    );
	
	private $version;
	private $code;//The HTTP response code
	private $message;
	private $headers = array (); // response headers
	private $body; // response body
	
	public function __construct($code, array $headers, $body = null, $version = '1.1', $message = null) {
		if ($this->prettifyResponseCode ( $code ) === null) {
			die  ( "{$code} is not a valid HTTP response code" );
		}
		
		$this->code = $code;
		
		foreach ( $headers as $name => $value ) {
			if (is_int ( $name )) {
				$header = explode ( ":", $value, 2 );
				if (count ( $header ) != 2) {
					die ( "'{$value}' is not a valid HTTP header" );
				}
				
				$name = trim ( $header [0] );
				$value = trim ( $header [1] );
			}
			
			$this->headers [ucwords ( strtolower ( $name ) )] = $value;
		}
		
		$this->body = $body;	
		if (! preg_match ( '|^\d\.\d$|', $version )) {
			die( "Invalid HTTP response version: $version" );
		}
		
		$this->version = $version;
		if (is_string ( $message )) {
			$this->message = $message;
		} else {
			$this->message = $this->prettifyResponseCode ( $code );
		}
	}
	
	// whether the response is an error
	public function isError() {
		$restype = floor ( $this->code / 100 );
		if ($restype == 4 || $restype == 5) {
			return true;
		}		
		return false;
	}
	
	// whether the response in successful
	public function isSuccessful() {
		$restype = floor ( $this->code / 100 );
		if ($restype == 2 || $restype == 1) { 
			return true;
		}
		
		return false;
	}
	
	// whether the response is a redirection
	public function isRedirect() {
		$restype = floor ( $this->code / 100 );
		if ($restype == 3) {
			return true;
		}
		
		return false;
	}
	
	public function getBody() {
		$body = '';
		// decode the body if it was transfer-encoded
		switch (strtolower ( $this->getHeader ( 'transfer-encoding' ) )) {
			// process chunked body
			case 'chunked' :
				$body = $this->decodeChunkedBody ( $this->body );
				break;
			default :
				$body = $this->body;
				break;
		}
		
		// Decode any content-encoding (gzip or deflate) if needed
		switch (strtolower ( $this->getHeader ( 'content-encoding' ) )) {			
			// process gzip encoding
			case 'gzip' :
				$body = $this->decodeGzip ( $body );
				break;			
			// process deflate encoding
			case 'deflate' :
				$body = $this->decodeDeflate ( $body );
				break;			
			default :
				break;
		}
		
		return $body;
	}
	
	// gete the raw response body (as transfered "on wire") as string
	public function getRawBodyAsString() {
		return $this->body;
	}

	public function getVersion() {
		return $this->version;
	}

	public function getStatus() {
		return $this->code;
	}
	
	public function getMessage() {
		return $this->message;
	}
	
	public function getHeaders() {
		return $this->headers;
	}

	public function getHeader($headerName) {
		$headerName = ucwords ( strtolower ( $headerName ) );
		if (! is_string ( $headerName ) || ! isset ( $this->headers [$headerName] ))
			return null;
		
		return $this->headers [$headerName];
	}
	
	// get all headers as string
	public function getHeadersAsString($status_line = true, $br = "\n") {
		$str = '';
		
		if ($status_line) {
			$str = "HTTP/{$this->version} {$this->code} {$this->message}{$br}";
		}
		
		// Iterate over the headers and stringify them
		foreach ( $this->headers as $name => $value ) {
			if (is_string ( $value ))
				$str .= "{$name}: {$value}{$br}";
			
			elseif (is_array ( $value )) {
				foreach ( $value as $subval ) {
					$str .= "{$name}: {$subval}{$br}";
				}
			}
		}
		
		return $str;
	}
	
	// get the entire response as string
	public function asString($br = "\n") {
		return $this->getHeadersAsString ( true, $br ) . $br . $this->getRawBodyAsString ();
	}
	
	// override
	public function __toString() {
		return $this->asString ();
	}
	
	/**
	 * A convenience function that returns a text representation of
	 * HTTP response codes. Returns 'Unknown' for unknown codes.
	 * Returns array of all codes, if $code is not specified.
	 *
	 * Conforms to HTTP/1.1 as defined in RFC 2616 (except for 'Unknown')
	 * See http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10 for reference
	 */
	public function prettifyResponseCode($code = null, $http11 = true) {
		$messages = $this->messages;
		if (! $http11)
			$messages [302] = 'Moved Temporarily';
		
		if ($code === null) {
			return $messages;
		} elseif (isset ( $messages [$code] )) {
			return $messages [$code];
		} else {
			return 'Unknown';
		}
	}
		
	// decode a "chunked" transfer-encoded body and return the decoded text
	public function decodeChunkedBody($body) {
		$decBody = '';
		
		// If mbstring overloads substr and strlen functions, we have to
		// override it's internal encoding
		if (function_exists ( 'mb_internal_encoding' ) && (( int ) ini_get ( 'mbstring.func_overload' )) & 2) {
			
			$mbIntEnc = mb_internal_encoding ();
			mb_internal_encoding ( 'ASCII' );
		}

		return $body;
	}
	
	// decode a gzip encoded message (when Content-encoding = gzip)
	public function decodeGzip($body) {
		if (! function_exists ( 'gzinflate' )) {
			die( 'zlib extension is required in order to decode "gzip" encoding' );
			return false;
		}
		
		return gzinflate ( substr ( $body, 10 ) );
	}
	
	// decode a zlib deflated message (when Content-encoding = deflate)
	public function decodeDeflate($body) {
		if (! function_exists ( 'gzuncompress' )) {
			die( 'zlib extension is required in order to decode "deflate" encoding' );
			return false;
		}		
		$zlibHeader = unpack ( 'n', substr ( $body, 0, 2 ) );
		if ($zlibHeader [1] % 31 == 0) {
			return gzuncompress ( $body );
		} else {
			return gzinflate ( $body );
		}
	}

	public function extractCode($response_str) {
		preg_match ( "|^HTTP/[\d\.x]+ (\d+)|", $response_str, $m );
		
		if (isset ( $m [1] )) {
			return ( int ) $m [1];
		} else {
			return false;
		}
	}

	public function extractMessage($response_str) {
		preg_match ( "|^HTTP/[\d\.x]+ \d+ ([^\r\n]+)|", $response_str, $m );
		
		if (isset ( $m [1] )) {
			return $m [1];
		} else {
			return false;
		}
	}

	public function extractVersion($response_str) {
		preg_match ( "|^HTTP/([\d\.x]+) \d+|", $response_str, $m );
		
		if (isset ( $m [1] )) {
			return $m [1];
		} else {
			return false;
		}
	}
	
	// extract the headers from a response string
	public function extractHeaders($response_str) {
		$headers = array ();
		
		// First, split body and headers
		$parts = preg_split ( '|(?:\r?\n){2}|m', $response_str, 2 );
		if (! $parts [0])
			return $headers;
		
		// Split headers part to lines
		$lines = explode ( "\n", $parts [0] );
		unset ( $parts );
		$last_header = null;
		
		foreach ( $lines as $line ) {
			$line = trim ( $line, "\r\n" );
			if ($line == "")
				break;
			
			if (preg_match ( "|^([\w-]+):\s*(.+)|", $line, $m )) {
				unset ( $last_header );
				$h_name = strtolower ( $m [1] );
				$h_value = $m [2];
				
				if (isset ( $headers [$h_name] )) {
					if (! is_array ( $headers [$h_name] )) {
						$headers [$h_name] = array ($headers [$h_name] );
					}
					
					$headers [$h_name] [] = $h_value;
				} else {
					$headers [$h_name] = $h_value;
				}
				$last_header = $h_name;
			} elseif (preg_match ( "|^\s+(.+)$|", $line, $m ) && $last_header !== null) {
				if (is_array ( $headers [$last_header] )) {
					end ( $headers [$last_header] );
					$last_header_key = key ( $headers [$last_header] );
					$headers [$last_header] [$last_header_key] .= $m [1];
				} else {
					$headers [$last_header] .= $m [1];
				}
			}
		}
		
		return $headers;
	}

	public function extractBody($response_str) {
		$parts = preg_split ( '|(?:\r?\n){2}|m', $response_str, 2 );
		if (isset ( $parts [1] )) {
			return $parts [1];
		}
		return '';
	}
}