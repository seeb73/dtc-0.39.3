<?php
require_once('./DtcScriptingRequest.php');

class DtcScriptingClient extends DtcScriptingRequest {
  private $dtc_host = '';

  public function __construct( $dtc_host, $dtc_login, $dtc_pass, $verify_cert = false ) {	
	$this->dtc_host = $dtcadmin_host;
        parent::__construct( 'https://'.$dtc_host.'/dtc/index.php?', $dtc_login, $dtc_pass, false, $verify_cert, "DTC Scripting Client 0.1");
  }
}
