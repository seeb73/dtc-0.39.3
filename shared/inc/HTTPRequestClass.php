<?php
// HTTPRequest class adapted from http://sg.php.net/manual/en/function.fopen.php#58099
//#usage:
//$r = new HTTPRequest('http://www.php.net');
//echo $r->DownloadToString();

class dtc_HTTPRequest
{
    var $_fp;        // HTTP socket
    var $_url;        // full URL
    var $_host;        // HTTP host
    var $_protocol;    // protocol (HTTP/HTTPS)
    var $_uri;        // request URI
    var $_port;        // port
    
    // Timeout in seconds 
    var $_timeout = 5;
    
    // scan url
    function _scan_url()
    {
        $req = $this->_url;
        
        $pos = strpos($req, '://');
        $this->_protocol = strtolower(substr($req, 0, $pos));
        
        $req = substr($req, $pos+3);
        $pos = strpos($req, '/');
        if($pos === false)
            $pos = strlen($req);
        $host = substr($req, 0, $pos);
        
        if(strpos($host, ':') !== false)
        {
            list($this->_host, $this->_port) = explode(':', $host);
        }
        else 
        {
            $this->_host = $host;
            $this->_port = ($this->_protocol == 'https') ? 443 : 80;
        }
        
        $this->_uri = substr($req, $pos);
        if($this->_uri == '')
            $this->_uri = '/';
    }
    
    // constructor
    function dtc_HTTPRequest($url)
    {
        $this->_url = $url;
        $this->_scan_url();
    }
    
    // download URL to string Array
    function DownloadToStringArray()
    {
    	$crlf = "/[\r\n]+/";
	$fullresponse = $this->DownloadToString();
    	$array = preg_split($crlf, $fullresponse, -1, PREG_SPLIT_NO_EMPTY);
	return $array;
    }
    
    // download URL to string
    function DownloadToString()
    {
    	// store errors in case we need to handle them
        $errno;
        $errstr;
		$response ='';
			
        $crlf = "\r\n";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->_protocol . "://" . $this->_host . $this->_uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: close'));
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
        $response = curl_exec($ch);
        curl_close($ch);
        
        // split header and body
        $pos = strpos($response, $crlf . $crlf);
        if($pos === false)
            return($response);
        $header = substr($response, 0, $pos);
        $body = substr($response, $pos + 2 * strlen($crlf));
        
        // parse headers
        $headers = array();
        $lines = explode($crlf, $header);
        foreach($lines as $line)
            if(($pos = strpos($line, ':')) !== false)
                $headers[strtolower(trim(substr($line, 0, $pos)))] = trim(substr($line, $pos+1));
        
        // redirection?
        if(isset($headers['location']))
        {
            $http = new dtc_HTTPRequest($headers['location']);
            return($http->DownloadToString($http));
        }
        else 
        {
            return($body);
        }
    }
}
?>
