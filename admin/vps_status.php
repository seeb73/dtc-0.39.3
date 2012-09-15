<?php

require_once("../shared/autoSQLconfig.php");
$panel_type="admin";
require_once("$dtcshared_path/dtc_lib.php");

require_once("authme.php");

$vps_node = $_REQUEST["server_hostname"];
$vps_name = $_REQUEST["vps_name"];

$soap_client = connectToVPSServer($vps_node);
if($soap_client != false){
	$vps_remote_info = getVPSInfo($vps_node,$vps_name,$soap_client);
	if($vps_remote_info == false){
		if(strstr($vps_soap_err,_("Method getVPSState failed"))){
			echo _("getVPSState() failed");
		}else if(strstr($vps_soap_err,_("couldn't connect to host"))){
			echo _("HTTP error");
		}else{
			echo "<font color=\"red\">"._("Not running")."</font>";
		}
	}else if($vps_remote_info == "fsck"){
		echo "FSCK";
	}else if($vps_remote_info == "mkos"){
		echo "MKOS";
	}else{
		echo "<font color=\"green\">"._("Running")."</font>";
	}
}else{
	echo _("Could not connect");
}

?>
