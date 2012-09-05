<?php

class DtcScriptingRequest {
  /*
   *
   */
  private $dtc_url = '';

  /*
   *
   */
  private $dtc_login = '';

  /*
   *
   */
  private $dtc_pass = '';

  private $dtc_verify_cert = false;

  private $dtc_useragent = '';

  private $dtc_http_auth = false;

  public function __construct( $url, $login, $password, $http_auth = false, $verify_cert = false, $useragent = 'DTC Scripting Client 0.1') {
        $this->dtc_url = $url;
        $this->dtc_login = $login;
        $this->dtc_pass = $password;
	$this->dtc_verify_cert = $verify_cert;
	$this->dtc_useragent = $useragent;
        $this->dtc_http_auth = $http_auth;
  }

  public function doRequest( $params = array(), $url = null ){
	if( $url == null )
                $url = $this->dtc_url;

	if( !$this->dtc_http_auth ) {
		$params['adm_login'] = $this->dtc_login;
		$params['adm_pass'] = $this->dtc_pass;
	}

	// bring the params into the url
	foreach( $params as $param => $value ){
          $url .= $param.'='.urlencode($value).'&';
	}

	// if( strrpos( $url, '&' ) == strlen($url) -1)
	//	$url = substr( $url, 0, strlen($url) - 2);

	// Initialise cURL session
	$curl = curl_init($url);
	// echo 'Used url: '.$url;

	// Load in the destination URL
	// curl_setopt($curl,CURLOPT_URL,$url);

	// tell cURL we're doing a GET
	curl_setopt($curl,CURLOPT_HTTPGET,true);

	// Place a nice friendly user-agent
	curl_setopt($curl,CURLOPT_USERAGENT, $this->dtc_useragent);
	// return the output instead of displaying it
	curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);

	// provide credentials if they're established at the beginning of the script
	if($this->dtc_http_auth && !empty($this->dtc_login) && !empty($this->dtc_pass))
		curl_setopt($curl,CURLOPT_USERPWD,$this->dtc_login . ":" . $this->dtc_pass);
        else if($this->dtc_http_auth)
		return 'Please Provide valid admin credentials';

	// tell cURL wheter to accept an SSL certificate whitout verification if presented
	curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, $this->dtc_verify_cert);

	// execute, and log the result to curl_put.log
	$result = curl_exec($curl);
	$error = curl_error($curl);
	curl_close($curl);

	if(!empty($error))
		$return = "CURL Error: ".$error;

	if( strpos( $result, '<!DOCTYPE') === false )
		return 'DTC Error: '.$result;

	// return $url; //uncomment this line to get the url executed.

	// return $result; // uncomment this line to get the resulting web page

	return true;
  }
}
