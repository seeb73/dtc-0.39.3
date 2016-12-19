<?php

//function auth_failed($reason) {
	//header( "WWW-authenticate: basic realm=\"DTC Admin ".$_SERVER["HTTP_HOST"]."\"" );
	//header( "HTTP/1.0 401 Unauthorized" );
//	echo $reason;
	// Log to SYSLOG
	//syslog(LOG_WARNING, "Failed login to DTC Admin from ".$_SERVER['REMOTE_ADDR']);
	//die();
//}

//if( !isset($_SERVER["PHP_AUTH_USER"]) || $_SERVER["PHP_AUTH_USER"] == ""){
//	auth_failed(_("Please login with your admin username and password to access the DTC admin interface."));
//}else{
//	$q = "SELECT * FROM tik_admins WHERE pseudo='".mysqli_real_escape_string($mysqli_connection,$_SERVER['PHP_AUTH_USER'])."' AND (tikadm_pass='".mysqli_real_escape_string($mysqli_connection,$_SERVER['PHP_AUTH_PW'])."' OR tikadm_pass=SHA1('".mysqli_real_escape_string($mysqli_connection,$_SERVER['PHP_AUTH_PW'])."'));";
//	$r = mysqli_query($mysqli_connection,$q)or die("Cannot query for auth line ".__LINE__." file ".__FILE__);
//	$n = mysqli_num_rows($r);
//	if($n != 1)	auth_failed(_("Incorrect login or password."));
//
//	
//}

function isPseudoLoggedIn($adm_session){
	global $mysqli_connection;
	global $pro_mysql_sessions_table;
	global $pseudo_login;
	$ret["err"] = 0;
	$ret["mesg"]=_("No error");
	
	if (!empty($_REQUEST["logout"]) && $_REQUEST["logout"]=="true")
	{		
		unset($_COOKIE['dtcsessioncookie']);
		setcookie('dtcsessioncookie', null, -1, '/');
		$adm_session=null;
	}
	
	// if we haven't got a session cookie yet, create one
	if (!isset($adm_session["session"]["session_key"]) || !$adm_session["session"]["session_key"])
	{
		// see if we have a session cookie in our browser, that hasn't been stored into the DB
		if (!empty($_COOKIE["dtcsessioncookie"]))
		{
			$cookie = $_COOKIE["dtcsessioncookie"];
		}
		if (isset($cookie))
		{
			$session_key = $cookie;
		}
		else
		{
			// generate a new UUID
			$session_key = UUID::uuid4();
		}
		
		$session_expiry = strtotime( '+30 days' );
		// we have authenticated correctly, generate a session cookie and populate the session table with an expiry
		setcookie("dtcsessioncookie",$session_key, $session_expiry, '/');
		$adm_session["session"]["session_key"] = $session_key;
		$adm_session["session"]["expiry"] = $session_expiry;
	}
	// if we have already got a pseudo login in our session, use that login
	if (!empty($adm_session["session"]["pseudo"]))
	{
		$pseudo_login = $adm_session["session"]["pseudo"];
	}
	else if (isset($_REQUEST["pseudo"]) && $_REQUEST["pseudo"]!="" && isset($_REQUEST["password"]) && $_REQUEST["password"] != "")
	{
		$q = "SELECT * FROM tik_admins WHERE pseudo='".mysqli_real_escape_string($mysqli_connection,$_REQUEST["pseudo"])."' AND (tikadm_pass='".mysqli_real_escape_string($mysqli_connection,$_REQUEST["password"])."' OR tikadm_pass=SHA1('".mysqli_real_escape_string($mysqli_connection,$_REQUEST["password"])."'));";
		$r = mysqli_query($mysqli_connection,$q)or die("Cannot query for auth line ".__LINE__." file ".__FILE__);
		$n = mysqli_num_rows($r);
		if($n != 1)	{		
			$ret["err"] = 1;
			$ret["mesg"]=_("Incorrect login or password.") . "Cannot execute query $q line ".__LINE__." file ".__FILE__." sql said: ".mysqli_error($mysqli_connection);
			return $ret;	
		}
		$row = mysqli_fetch_array($r);
		
		$pseudo_login = $row["pseudo"];
	}
	else
	{
		$ret["err"] = 2;
		$ret["mesg"]=_("Incorrect login or password.");
	}
		
	// if we are here, and we have a session that has authenticated in the client panel, we need to store our pseudo login into the DB
	if ((isset($adm_session["session"]["id"]) && $adm_session["session"]["id"] > 0) && 
		(!isset($adm_session["session"]["pseudo"]) || $adm_session["session"]["pseudo"] == "") && 
		isset($pseudo_login) && $pseudo_login)
	{
		// pull it from the session
		$session_expiry = $adm_session["session"]["expiry"];
		$session_key = $adm_session["session"]["session_key"];
		
		$adm_session["session"]["pseudo"] = $pseudo_login;
		$q2 = "UPDATE $pro_mysql_sessions_table set pseudo = '$pseudo_login' where session_key = '$session_key';";
		
		if(mysqli_query($mysqli_connection,$q2) === FALSE){
			// if we can't update session data, we should bail out here, something is wrong
			$ret["err"] = 5;
			$ret["mesg"]="Cannot execute query $q2 line ".__LINE__." file ".__FILE__." sql said: ".mysqli_error($mysqli_connection);
			return $ret;
		}
		$adm_session["session"]["pseudo"] = $pseudo_login;
	}
	
	// check to see if we have a full session retrieved (ie, there is a session id), otherwise, store it into the DB
	if ((!isset($adm_session["session"]["id"]) || $adm_session["session"]["id"] < 0) && isset($pseudo_login) && $pseudo_login)
	{	
		// pull it from the session
		$session_expiry = $adm_session["session"]["expiry"];
		$session_key = $adm_session["session"]["session_key"];
		
		$adm_session["session"]["pseudo"] = $pseudo_login;
		$q2 = "INSERT INTO $pro_mysql_sessions_table (pseudo,session_key,ip_addr,expiry)
	VALUES ('$pseudo_login', '$session_key', '$session_ip', FROM_UNIXTIME($session_expiry) );";
		
		if(mysqli_query($mysqli_connection,$q2) === FALSE){
			// if we can't insert session data, we should bail out here, something is wrong
			$ret["err"] = 5;
			$ret["mesg"]="Cannot execute query $q2 line ".__LINE__." file ".__FILE__." sql said: ".mysqli_error($mysqli_connection);
			return $ret;
		}
		
		// expire out sessions that are older than 60 days
		$q2 = "DELETE FROM $pro_mysql_sessions_table where expiry < (NOW() - INTERVAL 60 DAY) ;";
		if(mysqli_query($mysqli_connection,$q2) === FALSE){
			// if we can't delete old session data, we should bail out here, something is wrong
			$ret["err"] = 6;
			$ret["mesg"]="Cannot execute query $q2 line ".__LINE__." file ".__FILE__." sql said: ".mysqli_error($mysqli_connection);
			return $ret;
		}	
	}
	return $ret;
}

function pseudo_login_form(){
	global $conf_skin;

	$HTML_admin_edit_data = "
<form action=\"?\" method=\"post\">
<table>
<tr>
	<td align=\"right\">". _("Login: ") ."</td>
	<td><input type=\"text\" name=\"pseudo\" value=\"\"></td>
</tr><tr>
	<td align=\"right\">". _("Password:") ."</td>
	<td><input type=\"password\" name=\"password\" value=\"\"></td>
</tr><tr>
	<td></td><td><input type=\"submit\" name=\"Login\" value=\"login\">
</td></tr>
</table></form>";

	return $HTML_admin_edit_data;
}


?>
