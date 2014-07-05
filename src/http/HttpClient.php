<?php

class PU_HTTPClient(){
    private $url; 
    private $protocolVersion = '1.1';
    private $requestHeaders, $requestBody;    
    private $reply,$replyString; // response code, full response
    private $socket = false;
    private $useProxy = false;
    private $proxyHost, $proxyPort;

    public function __construct($host = '', $port = '') {
      if (!empty($host)) {
        $this->connect($host, $port);
      }
    }

    protected function setProxy($proxyHost, $proxyPort) {
      $this->useProxy = true;
      $this->proxyHost = $proxyHost;
      $this->proxyPort = $proxyPort;
    }

    protected function setProtocolVersion($version) {
      if ( ($version > 0) && ($version <= 1.1) ) {
        $this->protocolVersion = $version;
        return true;
      } else {
        return false;
      }
    }

    protected function setHeaders($headers) {
      if (is_array($headers)) {
        reset($headers);
        while (list($name, $value) = each($headers)) {
          $this->requestHeaders[$name] = $value;
        }
      }
    }

    protected function addHeader($headerName, $headerValue) {
      $this->requestHeaders[$headerName] = $headerValue;
    }

    protected function removeHeader($headerName) {
      unset($this->requestHeaders[$headerName]);
    }

    protected function connect($host, $port = '') {
      $this->url['scheme'] = 'http';
      $this->url['host'] = $host;
      if (!empty($port)) 
        $this->url['port'] = $port;
      return true;
    }

    protected function disconnect() {
      if ($this->socket) 
        fclose($this->socket);
    }

    public function doGet($url) {
      $this->responseHeaders = $this->responseBody = '';
      $uri = $this->generateURI($url);
      if ($this->sendRequest('GET ' . $uri . ' HTTP/' . $this->protocolVersion)) {
        $this->processReply();
      }
      return $this->reply;
    }

    // $params = array( "login" => "tiger", "password" => "secret" );
    // $httpClient->doPost( "/login.php", $params );
    public function doPost($uri, $query_params = '') {
      $uri = $this->generateURI($uri);
      if (is_array($query_params)) {
        $postArray = array();
        reset($query_params);
        while (list($k, $v) = each($query_params)) {
          $postArray[] = urlencode($k) . '=' . urlencode($v);
        }
        $this->requestBody = implode('&', $postArray);
      }
      $this->addHeader('Content-Type', 'application/x-www-form-urlencoded');
      if ($this->sendRequest('POST ' . $uri . ' HTTP/' . $this->protocolVersion)) {
        $this->processReply();
      }
      $this->removeHeader('Content-Type');
      $this->removeHeader('Content-Length');
      $this->requestBody = '';
      return $this->reply;
    }

    // sending a file on the server. it is *not* widely supported
    public function doPut($uri, $filecontent) {
      $uri = $this->generateURI($uri);
      $this->requestBody = $filecontent;

      if ($this->sendRequest('PUT ' . $uri . ' HTTP/' . $this->protocolVersion)) {
        $this->processReply();
      }
      return $this->reply;
    }

    public function getHeaders() {
      return $this->responseHeaders;
    }

    public function getHeader($headername) {
      return $this->responseHeaders[$headername];
    }

    public function getBody() {
      return $this->responseBody;
    }

    // return the server response's status code. e.g 20x, 30x, 40x, 50x
    public function getStatus() {
      return $this->reply;
    }

    // return the full response status, of the form "CODE Message". eg. "404 Document not found"
    public function getStatusMessage() {
      return $this->replyString;
    }


    /**
    * send a request
    * data sent are in order
    * a) the command
    * b) the request headers if they are defined
    * c) the request body if defined
    **/
    public function sendRequest($command) {
      $this->responseHeaders = array();
      $this->responseBody = '';
      if ( ($this->socket == false) || (feof($this->socket)) ) {
        if ($this->useProxy) {
          $host = $this->proxyHost;
          $port = $this->proxyPort;
        } else {
          $host = $this->url['host'];
          $port = $this->url['port'];
        }

        if (!!empty($port)) $port = 80;

        if (!$this->socket = @fsockopen($host, $port, $this->reply, $this->replyString)) {
          return false;
        }

        if (!empty($this->requestBody)) {
          $this->addHeader('Content-Length', strlen($this->requestBody));
        }

        $this->request = $command;
        $cmd = $command . "\r\n";
        if (is_array($this->requestHeaders)) {
          reset($this->requestHeaders);
          while (list($k, $v) = each($this->requestHeaders)) {
            $cmd .= $k . ': ' . $v . "\r\n";
          }
        }

        if (!empty($this->requestBody)) {
          $cmd .= "\r\n" . $this->requestBody;
        }

        $this->requestBody = '';
        fputs($this->socket, $cmd . "\r\n");

        return true;
      }
    }

    public function processReply() {
      $this->replyString = trim(fgets($this->socket, 1024));

      if (preg_match('|^HTTP/\S+ (\d+) |i', $this->replyString, $a )) {
        $this->reply = $a[1];
      } else {
        $this->reply = 'Bad Response';
      }

      $this->responseHeaders = $this->processHeader();
      $this->responseBody = $this->processBody();
      return $this->reply;
    }

    // reads header lines from socket until the line equals $lastLine
    protected function processHeader($lastLine = "\r\n") {
      $headers = array();
      $finished = false;

      while ( (!$finished) && (!feof($this->socket)) ) {
        $str = fgets($this->socket, 1024);
        $finished = ($str == $lastLine);
        if (!$finished) {
          list($hdr, $value) = split(': ', $str, 2);
          if (isset($headers[$hdr])) {
            $headers[$hdr] .= '; ' . trim($value);
          } else {
            $headers[$hdr] = trim($value);
          }
        }
      }

      return $headers;
    }

    // reads the body from the socket
    protected function processBody() {
      $data = '';
      $counter = 0;
      do {
        $status = socket_get_status($this->socket);
        if ($status['eof'] == 1) {
          break;
        }
        if ($status['unread_bytes'] > 0) {
          // diff between fread and fgets
          $buffer = fread($this->socket, $status['unread_bytes']);
          $counter = 0;
        } else {
          $buffer = fread($this->socket, 128);
          $counter++;
          usleep(2);
        }
        $data .= $buffer;
      } while ( ($status['unread_bytes'] > 0) || ($counter++ < 10) );
      return $data;
    }

    protected function generateURI($uri) {
      $a = parse_url($uri);
      if ( (isset($a['scheme'])) && (isset($a['host'])) ) {
        $this->url = $a;
      } else {
        unset($this->url['query']);
        unset($this->url['fragment']);
        $this->url = array_merge($this->url, $a);
      }

      if ($this->useProxy) {
        $requesturi = 'http://' . $this->url['host'] . (empty($this->url['port']) ? '' : ':' . $this->url['port']) . $this->url['path'] . (empty($this->url['query']) ? '' : '?' . $this->url['query']);
      } else {
        $requesturi = $this->url['path'] . (empty($this->url['query']) ? '' : '?' . $this->url['query']);
      }

      return $requesturi;
    }    
}