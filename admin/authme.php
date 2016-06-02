<?php

function auth_failed($reason) {
	header( "WWW-authenticate: basic realm=\"DTC Admin ".$_SERVER["HTTP_HOST"]."\"" );
	header( "HTTP/1.0 401 Unauthorized" );
	echo $reason;
	// Log to SYSLOG
	syslog(LOG_WARNING, "Failed login to DTC Admin from ".$_SERVER['REMOTE_ADDR']);
	die();
}

if( !isset($_SERVER["PHP_AUTH_USER"]) || $_SERVER["PHP_AUTH_USER"] == ""){
	auth_failed(_("Please login with your admin username and password to access the DTC admin interface."));
}else{
	$q = "SELECT * FROM tik_admins WHERE pseudo='".mysqli_real_escape_string($mysql_connection,$_SERVER['PHP_AUTH_USER'])."' AND (tikadm_pass='".mysqli_real_escape_string($mysql_connection,$_SERVER['PHP_AUTH_PW'])."' OR tikadm_pass=SHA1('".mysqli_real_escape_string($mysql_connection,$_SERVER['PHP_AUTH_PW'])."'));";
	$r = mysqli_query($mysql_connection,$q)or die("Cannot query for auth line ".__LINE__." file ".__FILE__);
	$n = mysqli_num_rows($r);
	if($n != 1)	auth_failed(_("Incorrect login or password."));
}


?>
