<?php

function fetchTable($query){
	$ret["err"] = 0;
	$ret["mesg"] = "No error";

	$result = mysqli_query($mysqli_connection,$query);
	if (!$result)
	{
		$ret["err"] = 1;
		$ret["mesg"] = "Cannot query \"$query\" !";
		return $ret;
	}
	$num_rows = mysqli_num_rows($result);
	for($i=0;$i<$num_rows;$i++){
		$table[] = mysqli_fetch_array($result);
	}
	return $table;
}

function fetchMailboxInfos($adm_email_login,$adm_email_pass){
	global $pro_mysql_pop_table;
	global $mysqli_connection;

	$ret["err"] = 0;
	$ret["mesg"] = "No error";

	$a = explode('@',$adm_email_login);
	$mailbox = $a[0];
	$domain = $a[1];
	$q = "SELECT * FROM $pro_mysql_pop_table WHERE id='$mailbox' AND mbox_host='$domain';";
	$r = mysqli_query($mysqli_connection,$q);
	if (!$r)
	{
		$ret["err"] = 1;
		$ret["mesg"] = "Cannot execute query \"$q\" !".mysqli_error($mysqli_connection)." line ".__LINE__." file ".__FILE__;
		return $ret;
	}
	if(mysqli_num_rows($r) != 1){
		$ret["mesg"] = _("Wrong user or password, or timeout expired") ;
		$ret["err"] = -1;
		return $ret;
	}
	$ret["data"] = mysqli_fetch_array($r);
	return $ret;
}

function fetchCommands($id_client){
	global $pro_mysql_command_table;
	global $mysqli_connection;

	$ret["err"] = 0;
	$ret["mesg"] = "No error";

	$query = "SELECT * FROM $pro_mysql_command_table WHERE id_client='$id_client';";
	$result = mysqli_query($mysqli_connection,$query);
	if (!$result)
	{
		$ret["err"] = 1;
		$ret["mesg"] = "Cannot execute query \"$query\"";
		return $ret;
	}
	$num_rows = mysqli_num_rows($result);
	if($num_rows < 1){
		$ret["err"] = -1;
		$ret["mesg"] = "No command for this user";
		return $ret;
	}
	for($i=0;$i<$num_rows;$i++){
		$row = mysqli_fetch_array($result);
		$commands[] = $row;
	}
	$ret["data"] = $commands;
	return $ret;
}

function fetchAdminInfo($adm_login){
    	global $pro_mysql_admin_table;
	global $mysqli_connection;

	$ret["err"] = 0;
	$ret["mesg"] = "No error";

	$query = "SELECT * FROM $pro_mysql_admin_table WHERE adm_login='$adm_login';";
	$result = mysqli_query($mysqli_connection,$query);
	if (!$result)
	{
		$ret["err"] = 1;
		$ret["mesg"] = "Cannot execute query \"$query\"";
		return $ret;
	}
	$num_rows = mysqli_num_rows($result);
 	if($num_rows > 1){
		$ret["err"] = 2;
		$ret["mesg"] = "More than one user with the name \"$adm_login\"";
		return $ret;
	}else if($num_rows < 1){
		$ret["mesg"] = "User not found.";
		$ret["err"] = -1;
		return $ret;
	}
	$row = mysqli_fetch_array($result);
	if (!$row)
	{
		$ret["err"] = 3;
		$ret["mesg"] = "Cannot fetch user line ".__LINE__." file ".__FILE__." sql said: ".mysqli_error($mysqli_connection);
		return $ret;
	}
	$ret["data"] = $row;
	return $ret;
}

function fetchAdminStats($admin){
	global $adm_login;
	global $conf_mysql_db;
	global $conf_demo_version;
	global $pro_mysql_domain_table;
	global $pro_mysql_acc_http_table;
	global $pro_mysql_acc_ftp_table;
	global $pro_mysql_acc_email_table;
	global $mysqli_connection;
	global $mysqli_connection_mysql;
	global $conf_user_mysql_host;
	global $conf_user_mysql_root_login;
	global $conf_user_mysql_root_pass;
	global $conf_user_mysql_type;
	global $conf_mysql_host;
	global $conf_mysql_login;
	global $conf_mysql_pass;

	// make sure we have a connection to mysql
	if($conf_user_mysql_type=="distant"){
		if ($mysqli_connection_mysql == NULL || $mysqli_connection_mysql.ping() == false) 
		{
			$mysqli_connection_mysql=mysqli_connect($conf_user_mysql_host,$conf_user_mysql_root_login,$conf_user_mysql_root_pass,"mysql") or die("Cannot connect to user SQL host");
		}
	}
	
	if ($mysqli_connection == NULL || mysqli_ping($mysqli_connection) == false)
	{
			$mysqli_connection = mysqli_connect($conf_mysql_host,$conf_mysql_login,$conf_mysql_pass,"$pro_mysql_db")or die("Cannot connect to user SQL host " . __FILE__ . ' ' . __LINE__);
	}
	
	if ($mysqli_connection_mysql == NULL || mysqli_ping($mysqli_connection_mysql) == false)
	{
			$mysqli_connection_mysql = mysqli_connect($conf_mysql_host,$conf_mysql_login,$conf_mysql_pass,"mysql")or die("Cannot connect to user SQL host " . __FILE__ . ' ' . __LINE__);
	}

	$ret["err"] = 0;
	$ret["mesg"] = "No error";

	$ret["total_http"] = 0;
	$ret["total_hit"] = 0;
	$adm_path = $admin["info"]["path"];
	$query = "SELECT name,du_stat FROM ".$pro_mysql_domain_table." WHERE owner='".$admin["info"]["adm_login"]."' ORDER BY name";
	$result = mysqli_query($mysqli_connection,$query);
	if (!$result)
	{
		$ret["err"] = 1;
		$ret["mesg"] = "Cannot execute query \"$query\"".mysqli_error($mysqli_connection);
		return $ret;
	}
	$num_domains = mysqli_num_rows($result);
	$ret["total_ftp"] = 0;
	for($ad=0;$ad<$num_domains;$ad++){
		$domain_name = mysqli_result($result,$ad,"name");
		$ret["domains"][$ad]["name"] = $domain_name;

		// Retrive disk usage
// Use the following version if you want it to be calculated in real time
//		$du_string = exec("du -sb $adm_path/$domain_name --exclude=access.log",$retval);
//		$du_state = explode("\t",$du_string);
//		$ret["domains"][$ad]["du"] = $du_state[0];
//		$ret["total_du_domains"] += $du_state[0];
// The following get value from table. du_stat is setup by cron script each time you have setup it (curently each hours)
		$du_stat = mysqli_result($result,$ad,"du_stat");
		$ret["domains"][$ad]["du"] = $du_stat;
		if(!isset($ret["total_du_domains"]))	$ret["total_du_domains"] = $du_stat;
		else	$ret["total_du_domains"] += $du_stat;

		// HTTP transfer
// Uncomment this if you want it in realtime
//		sum_http($domain_name);
		$query_http = "SELECT SUM(bytes_sent) as bytes_sent , SUM(count_impressions) as count_impressions FROM $pro_mysql_acc_http_table WHERE domain='$domain_name' AND month='".date("m",time())."' AND year='".date("Y",time())."'";
		$result_http = mysqli_query($mysqli_connection,$query_http);
		if (!$result_http)
		{	
			$ret["err"] = 2;
			$ret["mesg"] ="Cannot execute query \"$query_http\"";
			return $ret;
		}
		$num_rows = mysqli_num_rows($result_http);
		if($num_rows == 1){
			$rez_array = mysqli_fetch_array($result_http);
			$rez_http = $rez_array["bytes_sent"];
			$ret["total_http"] += $rez_http;
			$ret["domains"][$ad]["http"] = $rez_http;
			$ret["total_hit"] += $rez_array["count_impressions"];
			$ret["domains"][$ad]["hit"] = $rez_array["count_impressions"];
		}else{
			$rez_http = 0;
			$ret["domains"][$ad]["http"] = 0;
			$ret["domains"][$ad]["hit"] = 0;
		}

		// And FTP transfer
// Uncomment this if you want it in realtime (currently done in cron)
//		sum_ftp($domain_name);
		$query_ftp = "SELECT SUM(transfer) AS transfer FROM $pro_mysql_acc_ftp_table WHERE sub_domain='$domain_name' AND month='".date("m",time())."' AND year='".date("Y",time())."'";
		$result_ftp = mysqli_query($mysqli_connection,$query_ftp);
		if (!$result_ftp)
		{
			$ret["err"] = 3;
			$ret["mesg"] = "Cannot execute query \"$query\" !".mysqli_error($mysqli_connection)." line ".__LINE__." file ".__FILE__;
			return $ret;
		}
		$num_rows = mysqli_num_rows($result_ftp);
		$rez_ftp = mysqli_result($result_ftp,0,"transfer");
		if($rez_ftp == NULL){
			$ret["total_ftp"] += 0;
			$ret["domains"][$ad]["ftp"] = 0;
		}else{
			if(!isset($ret["total_ftp"]))	$ret["total_ftp"] = $rez_ftp;
			else	$ret["total_ftp"] += $rez_ftp;
			$ret["domains"][$ad]["ftp"] = $rez_ftp;
		}

		// Email accounting
		$q = "SELECT smtp_trafic,pop_trafic,imap_trafic FROM $pro_mysql_acc_email_table
		WHERE domain_name='$domain_name' AND month='".date("m")."' AND year='".date("Y")."'";
		$r = mysqli_query($mysqli_connection,$q);
		if (!$r)
		{
			$ret["err"] = 4;
			$ret["mesg"] = "Cannot execute query \"$q\" !".mysqli_error($mysqli_connection)." line ".__LINE__." file ".__FILE__;
			return $ret;
		}
		$num_rows = mysqli_num_rows($r);
		if($num_rows == 1){
			$smtp_bytes = mysqli_result($r,0,"smtp_trafic");
			$pop_bytes = mysqli_result($r,0,"pop_trafic");
			$imap_bytes = mysqli_result($r,0,"imap_trafic");
		}else{
			$smtp_bytes = 0;
			$pop_bytes = 0;
			$imap_bytes = 0;
		}
		$email_bytes = $smtp_bytes + $pop_bytes + $imap_bytes;
		if(!isset($ret["total_email"]))	$ret["total_email"] = $email_bytes;
		else	$ret["total_email"] += $email_bytes;
		$ret["domains"][$ad]["smtp"] = $smtp_bytes;
		$ret["domains"][$ad]["pop"] = $pop_bytes;
		$ret["domains"][$ad]["imap"] = $imap_bytes;

		if(!isset($ret["domains"][$ad]["total_transfer"]))
			$ret["domains"][$ad]["total_transfer"] = $rez_http + $rez_ftp + $email_bytes;
		else
			$ret["domains"][$ad]["total_transfer"] += $rez_http + $rez_ftp + $email_bytes;

		if(!isset($ret["total_transfer"]))
			$ret["total_transfer"] = $rez_http + $rez_ftp + $email_bytes;
		else
			$ret["total_transfer"] += $rez_http + $rez_ftp + $email_bytes;
	}

	$ret["total_du_db"] = 0;
	if($conf_demo_version != "yes"){
		$qu = "SELECT User FROM user WHERE dtcowner='".$admin["info"]["adm_login"]."'";
		$ru = $r = mysqli_query($mysqli_connection_mysql,$qu);
		if (!$ru)
		{
			$ret["err"] = 5;
			$ret["mesg"] = "Cannot query \"$qu\" !".mysqli_error($mysqli_connection)." line ".__LINE__." file ".__FILE__;
			mysqli_select_db($conf_mysql_db);
			return $ret;
		}
		$nbr_mysql_user = mysqli_num_rows($ru);
		for($j=0;$j<$nbr_mysql_user;$j++){
			$au = mysqli_fetch_array($ru);
			$dtcowner_user = $au["User"];

			$q = "SELECT Db FROM db WHERE User='$dtcowner_user'";
			$r = mysqli_query($mysqli_connection,$q);
			if (!$r)
			{
				$ret["err"] = 6;
				$ret["mesg"] = "Cannot query \"$q\" !".mysqli_error($mysqli_connection)." line ".__LINE__." file ".__FILE__;
				mysqli_select_db($conf_mysql_db);
				return $ret;
			}
			$db_nbr = mysqli_num_rows($r);
			for($i=0;$i<$db_nbr;$i++){
				$db_name = mysqli_result($r,$i,"Db");

				$query = "SHOW TABLE STATUS FROM $db_name;";
				$result = mysqli_query($mysqli_connection,$query);
				if (!$result){
					// $ret["err"] = 7;
					// $ret["mesg"] = "Cannot query \"$q\" !".mysqli_error($mysqli_connection);
					// mysqli_select_db($conf_mysql_db);
					// return $ret;
				}else{
					$num_tbl = mysqli_num_rows($result);
					$ret["db"][$i]["du"] = 0;
					for($k=0;$k<$num_tbl;$k++){
						$db_du = mysqli_result($result,$k,"Data_length");
						$ret["db"][$i]["du"] += $db_du;
						$ret["total_du_db"] += $db_du;
					}
					$ret["db"][$i]["name"] = $db_name;
				}
			}
		}
	}

	// reset to 0, and add total_du_db and total_du_domains
	$ret["total_du"] = 0;
	if (isset($ret["total_du_db"]))
	{
		$ret["total_du"] += $ret["total_du_db"];	
	}
	if (isset($ret["total_du_domains"]))
	{
		$ret["total_du"] += $ret["total_du_domains"];
	}
// ["domains"][0-n]["name"]
//                 ["du"]
//                 ["ftp"]
//                 ["http"]
//		   ["hit"]
//                 ["smtp"]
//                 ["pop"]
//                 ["total_transfer"]
// ["total_http"]
// ["total_hit"]
// ["total_ftp"]
// ["total_email"]
// ["total_transfer"]
// ["total_du_domains"]
// ["db"][0-n]["name"]
//            ["du"]
// ["total_db_du"]
// ["total_du"]
	return $ret;
}

function randomizePassword($adm_login,$adm_input_pass){
	global $pro_mysql_admin_table;
	global $pro_mysql_tik_admins_table;
	global $adm_realpass;
	global $adm_pass;
	global $adm_random_pass;
	global $conf_session_expir_minute;

	global $panel_type;
	global $gettext_lang;
	global $mysqli_connection;

	$ret["err"] = 0;
	$ret["mesg"] = "No error";

	if(isset($adm_random_pass) && strlen($adm_random_pass) > 0 && isRandomNum($adm_random_pass)){
		return $ret;
	}

	$query = "SELECT * FROM $pro_mysql_admin_table WHERE adm_login='$adm_login' AND ((adm_pass='$adm_input_pass' OR adm_pass=SHA1('$adm_input_pass')) OR (pass_next_req='$adm_pass' AND pass_expire > '".time()."'));";
	$result = mysqli_query($mysqli_connection,$query);
	if (!$result)
	{
		$ret["err"] = 1;
		$ret["mesg"] = "Cannot execute query for password line ".__LINE__." file ".__FILE__." (error message removed for security reasons).";
		return $ret;
	}
	$num_rows = mysqli_num_rows($result);

	if($num_rows != 1){
		$q = "SELECT * FROM $pro_mysql_tik_admins_table WHERE pass_next_req='$adm_input_pass' AND pass_expire > '".time()."';";
		$r = mysqli_query($mysqli_connection,$q);
		if (!$r)
		{
			$ret["err"] = 2;
			$ret["mesg"] = "Cannot execute query for password line ".__LINE__." file ".__FILE__." (error message removed for security reasons).";
			return $ret;
		}
		$n = mysqli_num_rows($r);
		if($n == 1){
			$is_root_admin = "yes";
			$query = "SELECT * FROM $pro_mysql_admin_table WHERE adm_login='$adm_login';";
			$result = mysqli_query($mysqli_connection,$query);
			if (!$result)
			{
				$ret["err"] = 3;
				$ret["mesg"] = "Cannot execute query for password line ".__LINE__." file ".__FILE__." (error message removed for security reasons).";
				return $ret;
			}
			$num_rows = mysqli_num_rows($result);

			if($num_rows != 1){
				$ret["mesg"] = _("Incorrect username or password, or timeout expired.") ;
				$ret["err"] = -1;
				return $ret;
			}
		}else{
			$ret["mesg"] = _("Incorrect username or password, or timeout expired.") ;
			$ret["err"] = -1;
			return $ret;
		}
		$is_root_admin = "yes";
	}else{
		$is_root_admin = "no";
	}
	$row = mysqli_fetch_array($result);
	if (!$row)
	{
		$ret["err"] = 4;
		$ret["mesg"] = "Cannot fetch user line ".__LINE__." file ".__FILE__." sql said: ".mysqli_error($mysqli_connection);
		return $ret;
	}

	// This stuff is rotating passwords helping NOT to save passwords on users browsers.
	$rand = getRandomValue();
	$adm_random_pass = $rand;
	$expirationTIME = time() + (60 * $conf_session_expir_minute);
	if($panel_type == "admin" && $is_root_admin == "yes"){
		$q = "UPDATE $pro_mysql_tik_admins_table SET pass_next_req='$rand', pass_expire='$expirationTIME' WHERE pseudo='".$_SERVER["PHP_AUTH_USER"]."';";
		$r = mysqli_query($mysqli_connection,$q);
		if (!$r)
		{
			$ret["err"] = 5;
			$ret["mesg"] = "Cannot execute query \"$q\" !";
			return $ret;
		}
	}else{
		$q = "UPDATE $pro_mysql_admin_table SET pass_next_req='$rand', pass_expire='$expirationTIME' WHERE adm_login='$adm_login'";
		$r = mysqli_query($mysqli_connection,$q);
		if (!$r)
		{
			$ret["err"] = 6;
			$ret["mesg"] = "Cannot execute query \"$q\" !";
			return $ret;
		}
	}
	// Save the last used language, so we know for future email sendings what to use.
	if(isset($gettext_lang) && $panel_type == "client"){
		$q = "UPDATE $pro_mysql_admin_table SET last_used_lang='$gettext_lang' WHERE adm_login='$adm_login';";
		$r = mysqli_query($mysqli_connection,$q);
	}

	$adm_pass = $rand;
	$adm_realpass = $row["adm_pass"];
}

function fetchAdminData($adm_session,$adm_login,$adm_input_pass){
	global $pro_mysql_domain_table;
	global $pro_mysql_admin_table;
	global $pro_mysql_list_table;
	global $pro_mysql_pop_table;
	global $pro_mysql_mailaliasgroup_table;
	global $pro_mysql_ftp_table;
	global $pro_mysql_ssh_table;
	global $pro_mysql_subdomain_table;
	global $pro_mysql_config_table;
	global $pro_mysql_vps_table;
	global $pro_mysql_vps_ip_table;
	global $pro_mysql_vps_server_table;
	global $pro_mysql_dedicated_table;
	global $pro_mysql_custom_product_table;
	global $pro_mysql_sessions_table;
	global $panel_type;

	global $conf_session_expir_minute;

	global $adm_realpass;
	global $adm_pass;
	global $adm_random_pass;
	global $idn;
	
	// This one is used by the root GUI so that you can browse your user
	// account at the same time as him without destroying his session.
	global $DONOT_USE_ROTATING_PASS;

	global $mysqli_connection;

	$logged_in_with_pass = false;

	$ret["err"] = 0;
	$ret["mesg"] = "No error";

	if($panel_type == "cronjob"){
		$pass = $adm_input_pass;
	}else{
		randomizePassword($adm_login,$adm_input_pass);
		$pass = $adm_realpass;
	}

	$row = null;
	
	if (isset($adm_session) && !empty($adm_session["session"]))
	{
		$session_expiry = new DateTime($adm_session["session"]["expiry"]);
		$date_now = new DateTime("now");
		// if we have a session passed in, we need to validate our session is valid, and has access to $adm_login
		// user_access_list * means access all admins
		if ($panel_type == "admin")
		{
			$login_type = "pseudo";
		}
		else
		{
			$login_type = "adm_login";
		}
		if ($session_expiry > $date_now && (in_array("*", $adm_session["user_access_list"]) || in_array($adm_login, $adm_session["user_access_list"])))
		{
			$query = "SELECT * FROM $pro_mysql_admin_table WHERE adm_login='". $adm_login ."';";
			$result = mysqli_query($mysqli_connection,$query);
			if (!$result){
				$ret["err"] = 1;
				$ret["mesg"] = "Cannot execute query for password line ".__LINE__." file ".__FILE__." (MySQL error message removed for security reasons).";
				return $ret;
			}
			$row = mysqli_fetch_array($result);
			if (!$row){
				$ret["err"] = 2;
				$ret["mesg"]= _("Cannot fetch user:")." "._("either your username or password is not valid, or your session has expired (timed out).");
				return $ret;
			}
		}
		else
		{
			$ret["err"] = 33;
			$ret["mesg"]= _("Cannot fetch user:")." "._("either your username or password is not valid, or your session has expired (timed out).");
			return $ret;
		}
		
	}
	else if (isset($pass))
	{
		// if we have a password passed in, we can validate it against the database
		$query = "SELECT * FROM $pro_mysql_admin_table WHERE adm_login='$adm_login' AND (adm_pass='$pass' OR adm_pass=SHA1('$pass'));";
		$result = mysqli_query($mysqli_connection,$query);
		if (!$result){
			$ret["err"] = 3;
			$ret["mesg"] = "Cannot execute query for password line ".__LINE__." file ".__FILE__." (MySQL error message removed for security reasons).";
			return $ret;
		}
		$row = mysqli_fetch_array($result);
		if (!$row){
			$ret["err"] = 4;
			$ret["mesg"]= _("Cannot fetch user:")." "._("either your username or password is not valid, or your session has expired (timed out).");
			return $ret;
		}
		$logged_in_with_pass = true;
	}

	$adm_path = $row["path"];
	$adm_max_ftp = $row["max_ftp"];
	$ret["max_ftp"] = $adm_max_ftp;
	$adm_max_ssh = $row["max_ssh"];
	$ret["max_ssh"] = $adm_max_ssh;
	$adm_max_email = $row["max_email"];
	$total_email = 0;
	$adm_quota = $row["quota"];
	$total_quota = 0;
	
	$session_key = NULL;
	$session_expiry = NULL;
	$session_ip = get_ip_address();
	
	
	if ($panel_type != "admin")
	{
		// if we haven't got a session cookie yet, create one
		if (!isset($adm_session["session"]["session_key"]) || !$adm_session["session"]["session_key"])
		{
			// see if we have a session cookie in our browser, that hasn't been stored into the DB
			$cookie = null;
			if (!empty($_COOKIE["dtcsessioncookie"]))	
			 $cookie = $_COOKIE["dtcsessioncookie"];
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
			if ($panel_type != "cronjob")
			{
				setcookie("dtcsessioncookie",$session_key, $session_expiry, '/');
			}
			$adm_session["session"]["session_key"] = $session_key;
			$adm_session["session"]["expiry"] = $session_expiry;
			// if we have an adm_login passed in here, set it into the session object we just created
			if (isset($adm_login))
			{
				$adm_session["session"]["adm_login"] = $adm_login;
			}
		}
		
		// check to see if we have a full session retrieved (ie, there is a session id), otherwise, store it into the DB
		if ((!isset($adm_session["session"]["id"]) || $adm_session["session"]["id"] < 0) && isset($adm_login) && $adm_login)
		{	
			// pull it from the session
			$session_expiry = $adm_session["session"]["expiry"];
			$session_key = $adm_session["session"]["session_key"];
			
			$adm_session["session"]["adm_login"] = $adm_login;
	
			// only set into the session table if logged in with pass
			if ($logged_in_with_pass)
			{
				$q2 = "INSERT INTO $pro_mysql_sessions_table (adm_login,session_key,ip_addr,expiry)
				VALUES ('$adm_login', '$session_key', '$session_ip', FROM_UNIXTIME($session_expiry) );";
			
				if(mysqli_query($mysqli_connection,$q2) === FALSE){
					// if we can't insert session data, we should bail out here, something is wrong
					$ret["err"] = 5;
					$ret["mesg"]="Cannot execute query $q2 line ".__LINE__." file ".__FILE__." sql said: ".mysqli_error($mysqli_connection);
					return $ret;
				}
			}
			
			// expire out sessions that are older than 60 days
			$q2 = "DELETE FROM $pro_mysql_sessions_table where expiry < (NOW() - INTERVAL 60 DAY) ;";
			if(mysqli_query($mysqli_connection,$q2) === FALSE){
				// if we can't delete old session data, we should bail out here, something is wrong
				$ret["err"] = 6;
				$ret["mesg"]="Cannot execute query $q2 line ".__LINE__." file ".__FILE__." sql said: ".mysqli_error($mysqli_connection);
				return $ret;
			}	
			if (!$logged_in_with_pass)
			{
				$ret["err"] = 4;
				$ret["mesg"]= _("Cannot fetch user:")." "._("either your username or password is not valid, or your session has expired (timed out).");
				return $ret;
			}
		}
	}
	

	// Get all the VPS of the user
	$q = "SELECT * FROM $pro_mysql_vps_table WHERE owner='$adm_login' ORDER BY vps_server_hostname,vps_xen_name;";
	$r = mysqli_query($mysqli_connection,$q);
	if (!$r)
	{
		$ret["err"] = 7;
		$ret["mesg"]="Cannot execute query $q line ".__LINE__." file ".__FILE__." sql said: ".mysqli_error($mysqli_connection);
		return $ret;
	}
	$n = mysqli_num_rows($r);
	$user_vps = array();
	for($i=0;$i<$n;$i++){
		$one_vps = mysqli_fetch_array($r);
		$q2 = "SELECT * FROM $pro_mysql_vps_ip_table WHERE vps_server_hostname='".$one_vps["vps_server_hostname"]."' AND vps_xen_name='".$one_vps["vps_xen_name"]."' AND available='no' ORDER BY ip_addr;";
		$r2 = mysqli_query($mysqli_connection,$q2);
		if (!$r2)
		{
			$ret["err"] = 8;
			$ret["mesg"]="Cannot execute query $q2 line ".__LINE__." file ".__FILE__." sql said: ".mysqli_error($mysqli_connection);
			return $ret;
		}
		$n2 = mysqli_num_rows($r2);
		unset($vps_ip);
		$vps_ip = array();
		for($j=0;$j<$n2;$j++){
			$a2 = mysqli_fetch_array($r2);
			$vps_ip[] = $a2["ip_addr"];
		}
		$one_vps["ip_addr"] = $vps_ip;
		$user_vps[] = $one_vps;
	}

	// Get all the dedicated servers of the user
	$q = "SELECT * FROM $pro_mysql_dedicated_table WHERE owner='$adm_login' ORDER BY server_hostname;";
	$r = mysqli_query($mysqli_connection,$q);
	if (!$r){
		$ret["err"] = 9;
		$ret["mesg"]="Cannot execute query $q line ".__LINE__." file ".__FILE__." sql said: ".mysqli_error($mysqli_connection);
		return $ret;
	}
	$n = mysqli_num_rows($r);
	$user_dedicated = array();
	for($i=0;$i<$n;$i++){
		$user_dedicated[] = mysqli_fetch_array($r);
	}

	// Get all custom products of the user
	$q = "SELECT * FROM $pro_mysql_custom_product_table WHERE owner='$adm_login' ORDER BY id;";
	$r = mysqli_query($mysqli_connection,$q);
	if (!$r){
		$ret["err"] = 10;
		$ret["mesg"]="Cannot execute query $q line ".__LINE__." file ".__FILE__." sql said: ".mysqli_error($mysqli_connection);
		return $ret;
	}
	$n = mysqli_num_rows($r);
	$user_custom = array();
	for($i=0;$i<$n;$i++){
		$user_custom[] = mysqli_fetch_array($r);
	}

	// Get all domains of the user
	$query = "SELECT * FROM $pro_mysql_domain_table WHERE owner='$adm_login' ORDER BY name;";
	$result = mysqli_query($mysqli_connection,$query);
	if (!$result)
	{
		$ret["err"] = 11;
		$ret["mesg"] = "Cannot execute query \"$query\"";
		return $ret;
	}
	$num_rows = mysqli_num_rows($result);
	// re-order by name, using idn
	for($i=0;$i<$num_rows;$i++){
		$row = mysqli_fetch_array($result);
		if (!$row)
		{
			$ret["err"] = 12;
			$ret["mesg"] = "Cannot fetch domain";
			return $ret;
		}
		$domains[] = $row;
		$b[$i] = $idn->decode($row["name"]);	
	}
	if (isset($b) && is_array($b)) {
		asort($b, SORT_LOCALE_STRING);
		foreach($b as $key=>$val) {
			$domains_idnsorted[] = $domains[$key];
		}
	}
	for($i=0;$i<$num_rows;$i++){
		//echo "$i<br>";
		$row = $domains_idnsorted[$i]; //mysqli_fetch_array($result);
		unset($domain);
		$domain["name"] = $row["name"];
		$domain["safe_mode"] = $row["safe_mode"];
		$domain["sbox_protect"] = $row["sbox_protect"];
		$domain["max_email"] = $row["max_email"];
		$total_email += $row["max_email"];
		$domain["max_lists"] = $row["max_lists"];
		$domain["max_ftp"] = $row["max_ftp"];
		$domain["max_ssh"] = $row["max_ssh"];
		$domain["max_subdomain"] = $row["max_subdomain"];
		$domain["quota"] = $row["quota"];
		$total_quota += $row["quota"];
		$domain["ip_addr"] = $row["ip_addr"];
		$domain["backup_ip_addr"] = $row["backup_ip_addr"];
		$domain["generate_flag"] = $row["generate_flag"];
		$name = $row["name"];
		$domain["default_subdomain"] = $row["default_subdomain"];
		$domain["primary_dns"] = $row["primary_dns"];
		$domain["other_dns"] = $row["other_dns"];
		$domain["primary_mx"] = $row["primary_mx"];
		$domain["other_mx"] = $row["other_mx"];
		$domain["whois"] = $row["whois"];
		$domain["hosting"] = $row["hosting"];
		$domain["du_stat"] = $row["du_stat"];
		$domain["gen_unresolved_domain_alias"] = $row["gen_unresolved_domain_alias"];
		$domain["txt_root_entry"] = $row["txt_root_entry"];
		$domain["txt_root_entry2"] = $row["txt_root_entry2"];
		$domain["catchall_email"] = $row["catchall_email"];
		$domain["domain_parking"] = $row["domain_parking"];
		$domain["domain_parking_type"] = $row["domain_parking_type"];
		$domain["wildcard_dns"] = $row["wildcard_dns"];
		$domain["default_sub_server_alias"] = $row["default_sub_server_alias"];
		$domain["custom_part"] = $row["custom_part"];
		$domain["spf_txt_entry"] = $row["spf_txt_entry"];
		$domain["mail_relay_host"] = $row["mail_relay_host"];
		$domain["autogen_subdomain"] = $row["autogen_subdomain"];

		$query2 = "SELECT * FROM $pro_mysql_subdomain_table WHERE domain_name='$name' ORDER BY subdomain_name;";
		$result2 = mysqli_query($mysqli_connection,$query2);
		if (!$result2)
		{
			$ret["err"] = 7;
			$ret["mesg"] = "Cannot execute query \"$query2\"";
			return $ret;
		}
		$num_rows2 = mysqli_num_rows($result2);
		if($num_rows < 1 && $domain["default_subdomain"] == NULL){
			$ret["mesg"] = "There is a default subdomain, but there is no subdomain in the database.";
			$ret["err"] = -3;
			return $ret;
		}
		unset($subs);
		$subs = array();
		for($j=0;$j<$num_rows2;$j++){
			$row2 = mysqli_fetch_array($result2);
			unset($subdomain);
			if (!$row2)
			{
				$ret["err"] = 8;
				$ret["mesg"] = "Cannot fetch subdomain";
			}
			$subdomain["id"] = $row2["id"];
			$subdomain["name"] = $row2["subdomain_name"];
			$subdomain["safe_mode"] = $row2["safe_mode"];
			$subdomain["sbox_protect"] = $row2["sbox_protect"];
			$subdomain["path"] = $row2["path"];
			$subdomain["ip"] = $row2["ip"];
			if(isset($row2["login"])){
				$subdomain["login"] = $row2["login"];
				$subdomain["pass"] = $row2["pass"];
			}
			$subdomain["w3_alias"] = $row2["w3_alias"];
			$subdomain["register_globals"] = $row2["register_globals"];
			$subdomain["webalizer_generate"] = $row2["webalizer_generate"];
			$subdomain["associated_txt_record"] = $row2["associated_txt_record"];
			if (isset($row2["generate_vhost"])){
				$subdomain["generate_vhost"] = $row2["generate_vhost"];
			} else {
				$subdomain["generate_vhost"] = "yes";
			}
			$subdomain["ssl_ip"] = $row2["ssl_ip"];

			// if we want to generate a NS entry with this subdomain as the nameserver
			if (isset($row2["nameserver_for"])){
				$subdomain["nameserver_for"] = $row2["nameserver_for"];
			} else {
				$subdomain["nameserver_for"] = NULL;
			}
			$subs[] = $subdomain;
		}
		$domain["subdomains"] = $subs;
		//echo "$i<br>";

		// Check that the default subdomain exist in the database
		/*if($domain["default_subdomain"] != NULL){
			$nbr_subdomains = sizeof($domain["subdomains"]);
			$is_default_sub_ok = false;
			for($j=0;$j<$nbr_subdomains;$j++){
				if($domain["subdomains"][$j]["name"] ==	$domain["default_subdomain"]){
					$is_default_sub_ok = true;
				}
			}
			if($is_default_sub_ok == false){
				$ret["mesg"] = "Default subdomain not found in database.";
				$ret["err"] = -4;
				return $ret;
			}
		}*/
// At this point the following shema is fetched :
// $user_domains = [0-n]["default_subdomain"]
//                      ["name"]
//                      ["quota"]
//                      ["max_email"]
//                      ["max_ftp"]
//                      ["max_ssh"]
//                      ["max_subdomain"]
//                      ["ip_addr"]
//                      ["backup_ip_addr"]
//                      ["subdomains"][0-n]["name"]
//                                         ["path"]
//                                         ["ip"]
//                                         ["login"]
//                                         ["pass"]
//                                         ["w3_alias"]
//                                         ["register_globals"]
//                                         ["webalizer_generate"]
//                                         ["generate_vhost"]
//                                         ["associated_txt_record"]
// Now Can add emails to all thoses domains !
		$query4 = "SELECT * FROM $pro_mysql_pop_table WHERE mbox_host='$name' ORDER BY id;";
		$result4 = mysqli_query($mysqli_connection,$query4);
		if (!$result4)
		{
			$ret["err"] = 9;
			$ret["mesg"] = "Cannot execute query \"$query4\"";
			return $ret;
		}
		$num_rows4 = mysqli_num_rows($result4);
		unset($emails);
		for($j=0;$j<$num_rows4;$j++){
			$row4 = mysqli_fetch_array($result4);
			if (!$row4)
			{
				$ret["err"] = 10;
				$ret["mesg"] = "Cannot fetch mailbox";
				return $ret;
			}
			unset($email);
			$email["id"] = $row4["id"];
			$email["uid"] = $row4["uid"];
			$email["gid"] = $row4["gid"];
			$email["home"] = $row4["home"];
			$email["crypt"] = $row4["crypt"];
			$email["passwd"] = $row4["passwd"];
			$email["shell"] = $row4["shell"];
			$email["redirect1"] = $row4["redirect1"];
			$email["redirect2"] = $row4["redirect2"];
			$email["localdeliver"] = $row4["localdeliver"];
			$email["bounce_msg"] = $row4["bounce_msg"];
			$email["spam_mailbox"] = $row4["spam_mailbox"];
			$email["spam_mailbox_enable"] = $row4["spam_mailbox_enable"];
			$email["vacation_flag"] = $row4["vacation_flag"];
			$email["vacation_text"] = $row4["vacation_text"];
			$email["permit_redir"] = $row4["permit_redir"];
			$email["permit_spam"] = $row4["permit_spam"];
			$email["spam_modifies_subj"] = $row4["spam_modifies_subj"];
			$emails[] = $email;
		}	
		if(isset($emails)){
			$domain["emails"] = $emails;
		}

// Now Can add alias emails to all thoses domains !
		$query5 = "SELECT * FROM $pro_mysql_mailaliasgroup_table WHERE domain_parent='$name' ORDER BY id;";
		$result5 = mysqli_query($mysqli_connection,$query5);
		if (!$result5)
		{
			$ret["err"] = 9;
			$ret["mesg"] = "Cannot execute query \"$query5\"";
			return $ret;
		}
		$num_rows4 = mysqli_num_rows($result5);
		unset($aliases);
		for($j=0;$j<$num_rows4;$j++){
			$row5 = mysqli_fetch_array($result5);
			if (!$row5)
			{
				$ret["err"] = 10;
				$ret["mesg"] = "Cannot fetch mailbox";
				return $ret;
			}
			unset($alias);
			$alias["autoinc"] = $row5["autoinc"];
			$alias["id"] = $row5["id"];
			$alias["domain_parent"] = $row5["domain_parent"];
			$alias["delivery_group"] = $row5["delivery_group"];
			$alias["active"] = $row5["active"];
			$alias["start_date"] = $row5["start_date"];
			$alias["expire_date"] = $row5["expire_date"];
			$alias["bounce_msg"] = $row5["bounce_msg"];
			$aliases[] = $alias;
		}	
		if(isset($aliases)){
			$domain["aliases"] = $aliases;
		}

		//now to add all the mailing lists
		$query4 = "SELECT * FROM $pro_mysql_list_table WHERE domain='$name' ORDER BY name;";
		$result4 = mysqli_query($mysqli_connection,$query4);
		if (!$result4)
		{
			$ret["err"] = 11;
			$ret["mesg"] = "Cannot execute query \"$query4\"";
			return $ret;
		}
		$num_rows4 = mysqli_num_rows($result4);
		unset($mailinglists);
		for($j=0; $j < $num_rows4; $j++){
			$row4 = mysqli_fetch_array($result4);
			if (!$row4)
			{
				$ret["err"] = 12;
				$ret["mesg"] = "Cannot fetch mailing list";
				return $ret;
			}
			unset($mailinglist);
			$mailinglist["id"] = $row4["id"];
			$mailinglist["name"] = $row4["name"];
			$mailinglist["owner"] = $row4["owner"];
			$mailinglist["domain"] = $row4["domain"];
			$mailinglists[] = $mailinglist;
		}
		if(isset($mailinglists)){
			$domain["mailinglists"] = $mailinglists;
		}

		$query4 = "SELECT * FROM $pro_mysql_ftp_table WHERE hostname='$name' ORDER BY login";
		$result4 = mysqli_query($mysqli_connection,$query4);
		if (!$result4)
		{
			$ret["err"] = 13;
			$ret["mesg"] = "Cannot execute query \"$query4\"";
			return $ret;
		}
		$num_rows4 = mysqli_num_rows($result4);
		unset($ftps);
		for($j=0;$j<$num_rows4;$j++){
			$row4 = mysqli_fetch_array($result4);
			if (!$row4)
			{
				$ret["err"] = 14;
				$ret["mesg"] = "Cannot fetch ftp account";
				return $ret;
			}
			$ftp["login"] = $row4["login"];
			$ftp["passwd"] = $row4["password"];
			$ftp["path"] = $row4["homedir"];
			$ftps[] = $ftp;
		}
		if(isset($ftps)){
			$domain["ftps"] = $ftps;
		}

		$query4 = "SELECT * FROM $pro_mysql_ssh_table WHERE hostname='$name' ORDER BY login";
		$result4 = mysqli_query($mysqli_connection,$query4);
		if (!$result4)
		{
			$ret["err"] = 15;
			$ret["mesg"] = "Cannot execute query \"$query4\"";
			return $ret;
		}
		$num_rows4 = mysqli_num_rows($result4);
		unset($sshs);
		for($j=0;$j<$num_rows4;$j++){
			$row4 = mysqli_fetch_array($result4);
			if (!$row4)
			{
				$ret["err"] = 16;
				$ret["mesg"] = "Cannot fetch ssh account";
				return $ret;
			}
			$ssh["login"] = $row4["login"];
			$ssh["passwd"] = $row4["password"];
			$ssh["path"] = $row4["homedir"];
			$sshs[] = $ssh;
		}
		if(isset($sshs)){
			$domain["sshs"] = $sshs;
		}

// Now we have :
// $user_domains = [0-n]["default_subdomain"]
//                      ["name"]
//                      ["quota"]
//                      ["max_email"]
//                      ["max_ftp"]
//                      ["max_ssh"]
//                      ["max_subdomain"]
//                      ["ip_addr"]
//                      ["backup_ip_addr"]
//			["domain_parking"]
//                      ["subdomains"][0-n]["name"]
//                                         ["path"]
//                                         ["ip"]
//                                         ["login"]
//                                         ["pass"]
//                                         ["w3_alias"]
//                                         ["register_globals"]
//                                         ["webalizer_generate"]
//                                         ["generate_vhost"]
//                                         ["associated_txt_record"]
//                      ["emails"][0-n]["id"]
//                                     ["uid"]
//                                     ["gid"]
//                                     ["home"]
//                                     ["crypt"]
//                                     ["passwd"]
//                                     ["shell"]
//			["mailinglists"][0-n]["id"]
//					     ["name"]
//					     ["owner"]
//                      ["ftps"]["login"]
//                              ["passwd"]
//                              ["path"]
//                      ["sshs"]["login"]
//                              ["passwd"]
//                              ["path"]
		$user_domains[] = $domain;
	}
	if(isset($user_domains)){
		$user_domains[0]["total_email"] = $total_email;
		$user_domains[0]["total_quota"] = $total_quota;
		$user_domains[0]["adm_email"] = $adm_max_email;
		$user_domains[0]["adm_quota"] = $adm_quota;
		$ret["data"] = $user_domains;
	}
	if(isset($user_vps)){
		$ret["vps"] = $user_vps;
	}
	if(isset($user_dedicated)){
		$ret["dedicated"] = $user_dedicated;
	}
	if(isset($user_custom)){
		$ret["custom"] = $user_custom;
	}
	return $ret;
}

function fetchClientData($id_client){
		global $pro_mysql_client_table;
		global $mysqli_connection;

		$query4 = "SELECT * FROM $pro_mysql_client_table WHERE id='$id_client'";
		$result4 = mysqli_query($mysqli_connection,$query4);
		if (!$result4)
		{
			$ret["err"] = 1;
			$ret["mesg"] = "Cannot execute query \"$query4\"";
			return $ret;
		}
		$num_rows4 = mysqli_num_rows($result4);
		if($num_rows4 != 1){
			$ret["err"] = -1;
			$ret["msg"] = "Could not fetch commercial information for that user.";
			$ret["data"] = NULL;
			return $ret;
		}

		$row4 = mysqli_fetch_array($result4);
		if (!$row4)
		{
			$ret["err"] = 2;
			$ret["mesg"] = "Cannot fetch client account";
			return $ret;
		}
		$ret["err"] = 0;
		$ret["msg"] = "No error";
		$ret["data"] = $row4;
		return $ret;
}

function fetchSession($panel_type = "client"){
	global $mysqli_connection;
	global $pro_mysql_admin_table;
	global $pro_mysql_sessions_table;
	global $pro_mysql_userroles_table;
	global $pro_mysql_roles_table;
	global $pro_mysql_rolepermissions_table;
	global $mysqli_connection;
	
	// logout handling
	if (isset($_REQUEST["logout"]) && $_REQUEST["logout"]=="true")
	{	
		unset($_COOKIE['dtcsessioncookie']);
		setcookie('dtcsessioncookie', null, -1, '/');
		$adm_session=null;
	}
	
	// TODO - code to check the current session information
	if (isset($_COOKIE["dtcsessioncookie"]))
	{
		$cookie = $_COOKIE["dtcsessioncookie"];
	}
	
	// for the admin panel, we use "pseudo" for logins
	// for the client panel, we use "adm_login" for the logins
	if ($panel_type == "admin")
	{
		$login_type = "pseudo";
	}
	else
	{
		$login_type = "adm_login";
	}
	
	// if we have a valid session cookie in our session, try and populate it
	// if we don't have a valid session cookie, 
	if (!isset($cookie))
	{
		$adm_session["err"] = 11;
		$adm_session["mesg"] = _("No cookie found");
		return $adm_session;
	} 
	else
	{
		// check what session this $cookie gives us access to
		$query = "SELECT * FROM $pro_mysql_sessions_table WHERE (session_key='$cookie' OR session_key=SHA1('$cookie'));";
		$result = mysqli_query($mysqli_connection,$query);
		if (!$result){
			$ret["err"] = 12;
			$ret["mesg"] = "Cannot execute query for password line ".__LINE__." file ".__FILE__." (MySQL error message removed for security reasons).";
			return $ret;
		}
		$row = mysqli_fetch_array($result);
		if (!$row){
			$ret["err"] = 13;
			$ret["mesg"]= _("Cannot fetch user:")." "._("either your username or password is not valid, or your session has expired (timed out).");
			return $ret;
		}
		$adm_session["session"] = $row;
		
		// populate user_access_list based on user/role/permissions
		$query = "SELECT $pro_mysql_roles_table.id, $pro_mysql_roles_table.code FROM $pro_mysql_roles_table, $pro_mysql_userroles_table WHERE $login_type='". $adm_session["session"][$login_type] . "' and $pro_mysql_roles_table.id = $pro_mysql_userroles_table.role_id ;";
		$result = mysqli_query($mysqli_connection,$query);
		if (!$result){
			$ret["err"] = 14;
			$ret["mesg"] = "Cannot execute query for password line ".__LINE__." file ".__FILE__." (MySQL error message removed for security reasons).";
			return $ret;
		}
		
		$num_rows = mysqli_num_rows($result);
		
		for ($num_rows_i = 0; $num_rows_i < $num_rows; $num_rows_i++)
		{
			$row = mysqli_fetch_array($result);
			if (!$row){
				$ret["err"] = 15;
				$ret["mesg"]= _("Cannot fetch user:")." "._("either your username or password is not valid, or your session has expired (timed out).");
				return $ret;
			}
			if (!isset($adm_session["roles"]))
			{
				$adm_session["roles"] = array ( $row );
			}
			else
			{
				array_push($adm_session["roles"], $row);
			}
		}

		// if we don't have any roles defined in the DB, assume we are a normal admin
		if (empty($adm_session["roles"]))
		{
			$adm_session["roles"] = array (array ( "code" =>  "admin" ));
		}
	
		foreach ($adm_session["roles"] as $role)
		{
			if ($role["code"] == "admin")
			{
				// admin role == single user admin user
				// add $adm_login to user_access_list
				$adm_session["user_access_list"] = array( $adm_session["session"][$login_type] );
			}
			else if ($role["code"] == "root_admin")
			{
				// root_admin role == access the whole panel
				$adm_session["user_access_list"] = array( "*" );
			}
			else
			{
				$adm_session["user_access_list"] = array( $adm_session["session"][$login_type] );
			}
			if (!empty($role["id"]))
			{
				// add the rest of the permissions to the session permissions table
				$query = "SELECT * FROM $pro_mysql_rolepermissions_table WHERE roles_id=" . $role["id"] . ";";
				$result = mysqli_query($mysqli_connection,$query);
				if ($result){
					$row = mysqli_fetch_array($result);
					if (!isset($adm_session["permissions"]))
					{
						$adm_session["permissions"] = $row;
					}
					else
					{
						array_push($adm_session["permissions"], $row);
					}
				}
			}
		}
	}

	if (empty($adm_session["user_access_list"]))
	{
		$adm_session["user_access_list"] = array( $adm_session["session"][$login_type] );
	}
	
	return $adm_session;
	
}

function fetchAdmin($adm_session,$adm_login, $adm_pass){
	global $panel_type;
	$ret["err"] = 0;
	$ret["mesg"] = "No error";

	$data = fetchAdminData($adm_session,$adm_login,$adm_pass);
	if($data["err"] != 0){
		$ret["err"] = $data["err"];
		$ret["mesg"] = $data["mesg"];
		return $ret;
	}

	// only use $adm_session data for login purposes if we are not in the admin panel
	if ($panel_type != "admin" && (!isset($adm_login) || !$adm_login))
	{
		$adm_login = $adm_session["session"]["adm_login"];
	}

	$info = fetchAdminInfo($adm_login);
	if($info["err"] != 0){
		$ret["err"] = $info["err"];
		$ret["mesg"] = $info["mesg"];
		return $ret;
	}
	//echo "adm_login is now $adm_login\n";
	//echo "the array contains: " . $info["data"]["adm_login"] . "\n";

	$id_client = $info["data"]["id_client"];
	if($id_client != 0){
		$client = fetchClientData($id_client);
		if($client["err"] != 0){
			$ret["err"] = $client["err"];
			$ret["mesg"] = $client["mesg"];
			return $ret;
		}
		$ret["client"] = $client["data"];
	}else{
		$ret["client"] = "NULL";
	}
	$ret["info"] = $info["data"];
	if(isset($data["data"])){
		$ret["data"] = $data["data"];
	}
	if(isset($data["vps"])){
		$ret["vps"] = $data["vps"];
	}
	if(isset($data["dedicated"])){
		$ret["dedicated"] = $data["dedicated"];
	}
	if(isset($data["custom"])){
		$ret["custom"] = $data["custom"];
	}
	return $ret;
}

/**
  * CREDIT goes to http://stackoverflow.com/questions/1634782/what-is-the-most-accurate-way-to-retrieve-a-users-correct-ip-address-in-php/2031935#2031935
  * Retrieves the best guess of the client's actual IP address.
  * Takes into account numerous HTTP proxy headers due to variations
  * in how different ISPs handle IP addresses in headers between hops.
  */
 function get_ip_address() {
  // Check for shared internet/ISP IP
  if (!empty($_SERVER['HTTP_CLIENT_IP']) && $this->validate_ip($_SERVER['HTTP_CLIENT_IP']))
   return $_SERVER['HTTP_CLIENT_IP'];

  // Check for IPs passing through proxies
  if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
   // Check if multiple IP addresses exist in var
    $iplist = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    foreach ($iplist as $ip) {
     if ($this->validate_ip($ip))
      return $ip;
    }
   }
  if (!empty($_SERVER['HTTP_X_FORWARDED']) && $this->validate_ip($_SERVER['HTTP_X_FORWARDED']))
   return $_SERVER['HTTP_X_FORWARDED'];
  if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && $this->validate_ip($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
   return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
  if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && $this->validate_ip($_SERVER['HTTP_FORWARDED_FOR']))
   return $_SERVER['HTTP_FORWARDED_FOR'];
  if (!empty($_SERVER['HTTP_FORWARDED']) && $this->validate_ip($_SERVER['HTTP_FORWARDED']))
   return $_SERVER['HTTP_FORWARDED'];

  // Return unreliable IP address since all else failed
  if (!empty($_SERVER['REMOTE_ADDR']))
   return $_SERVER['REMOTE_ADDR'];
  return "unknown";
 }

 /**
  * Ensures an IP address is both a valid IP address and does not fall within
  * a private network range.
  *
  * @access public
  * @param string $ip
  */
 function validate_ip($ip) {
     if (filter_var($ip, FILTER_VALIDATE_IP, 
                         FILTER_FLAG_IPV4 | 
                         FILTER_FLAG_IPV6 |
                         FILTER_FLAG_NO_PRIV_RANGE | 
                         FILTER_FLAG_NO_RES_RANGE) === false)
         return false;
     self::$ip = $ip;
     return true;
 }


?>
