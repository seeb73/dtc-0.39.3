<?php

function deleteMysqlUserAndDB($adm_login){
	global $conf_mysql_db;
	mysqli_select_db("mysql")or die("Cannot select db mysql for account management !!!");

	$q = "SELECT * FROM user WHERE dtcowner='$adm_login';";
	$r = mysqli_query($mysqli_connection,$q)or die("Cannot query $q line ".___LINE__." file".__FILE__);
	$n = mysqli_num_rows($r);
	for($j=0;$j<$n;$j++){
		$a = mysqli_fetch_array($r);
		$mysqli_user = $a["User"];

		$query = "SELECT * FROM db WHERE User='$mysqli_user';";
		$result = mysqli_query($mysqli_connection,$query)or die("Cannot execute query \"$query\" !!!");
		$num_rows = mysqli_num_rows($result);
		for($i=0;$i<$num_rows;$i++){
			$row = mysqli_fetch_array($result);
			$db = $row["Db"];
			// Prevent system db from deletion
			if($db != $conf_mysql_db && $db != "mysql"){
				$query2 = "DROP DATABASE IF EXISTS `$db`";
				mysqli_query($mysqli_connection,$query2)or die("Cannot execute query \"$query2\" line ".__line__." file ".__FILE__." mysql said: ".mysqli_error());
			}
		}
		// Prevent system user from deletion
		if($mysqli_user != "mysql" && $mysqli_user != "root"){
			$query = "DELETE FROM db WHERE User='$mysqli_user';";
			mysqli_query($mysqli_connection,$query)or die("Cannot execute query \"$query\" !!!");
			$query = "DELETE FROM user WHERE User='$mysqli_user';";
			mysqli_query($mysqli_connection,$query)or die("Cannot execute query \"$query\" !!!");
		}
		$query = "FLUSH PRIVILEGES";
		mysqli_query($mysqli_connection,$query)or die("Cannot execute query \"$query\" !!!");
	}
	mysqli_select_db($conf_mysql_db)or die("Cannot select db \"$conf_mysql_db\" in deleteMysqlUserAndDB() !!!");
}

function deleteUserDomain($adm_login,$adm_pass,$deluserdomain,$delete_directories = false){
	global $pro_mysql_admin_table;
	global $pro_mysql_pop_table;
	global $pro_mysql_mailaliasgroup_table;
	global $pro_mysql_ftp_table;
	global $pro_mysql_subdomain_table;
	global $pro_mysql_domain_table;
	global $pro_mysql_list_table;
	global $pro_mysql_fetchmail_table;
	global $pro_mysql_cronjob_table;
	global $conf_demo_version;
	global $conf_generated_file_path;

	global $conf_root_admin_random_pass;
	global $conf_pass_expire;

	checkLoginPassAndDomain($adm_login,$adm_pass,$deluserdomain);
	$adm_query = "SELECT * FROM $pro_mysql_admin_table WHERE adm_login='$adm_login';";
	$result = mysqli_query($mysqli_connection,$adm_query)or die("Cannot execute query \"$adm_query\" line ".__LINE__." file ".__FILE__."sql said ".mysqli_error());
	$num_rows = mysqli_num_rows($result);
	if($num_rows != 1) die("User not found for deletion of domain $deluserdomain !!!");
	$row = mysqli_fetch_array($result);
	$the_admin_path = $row["path"];

	// Delete all mail groups
	$adm_query = "DELETE FROM $pro_mysql_mailaliasgroup_table WHERE domain_parent='$deluserdomain';";
	mysqli_query($mysqli_connection,$adm_query)or die("Cannot execute query \"$adm_query\" !!!");

	// Delete all mail accounts
	$adm_query = "DELETE FROM $pro_mysql_pop_table WHERE mbox_host='$deluserdomain';";
	mysqli_query($mysqli_connection,$adm_query)or die("Cannot execute query \"$adm_query\" !!!");

	$adm_query = "DELETE FROM $pro_mysql_fetchmail_table WHERE domain_name='$deluserdomain';";
	mysqli_query($mysqli_connection,$adm_query)or die("Cannot execute query \"$adm_query\" !!!");

	// Delete all mailboxs
	$adm_query = "DELETE FROM $pro_mysql_ftp_table WHERE hostname='$deluserdomain';";
	mysqli_query($mysqli_connection,$adm_query)or die("Cannot execute query \"$adm_query\" !!!");
	
	// Delete all subdomains
	$domupdate_query = "DELETE FROM $pro_mysql_subdomain_table WHERE domain_name='$deluserdomain';";
	$domupdate_result = mysqli_query($mysqli_connection,$domupdate_query)or die("Cannot execute query \"$domupdate_query\"");

	// Delete the domain
	$adm_query = "DELETE FROM $pro_mysql_domain_table WHERE name='$deluserdomain' LIMIT 1;";
	mysqli_query($mysqli_connection,$adm_query)or die("Cannot execute query \"$adm_query\" !!!");

	// Delete all mailing lists
	$adm_query = "DELETE FROM $pro_mysql_list_table WHERE domain='$deluserdomain';";
	mysqli_query($mysqli_connection,$adm_query)or die("Cannot execute query \"$adm_query\" !!!");

	// Delete the files of the domain name and the DNS zone files
	if($delete_directories == true && $conf_demo_version == "no"){
		system("rm -rf $the_admin_path/$deluserdomain");
		system("rm -rf $conf_generated_file_path/zones/$deluserdomain");
	}

	// We now check if there are still some domains in the admin account
	// if there are none, then we shall delete the symlinks
	$adm_query = "SELECT name FROM $pro_mysql_domain_table WHERE owner='$adm_login';";
	$r = mysqli_query($mysqli_connection,$adm_query)or die("Cannot execute query \"$adm_query\" line ".__LINE__." file ".__FILE__);
	$n = mysqli_num_rows($r);
	if($n == 0){
		$folder_list = "$the_admin_path/lib $the_admin_path/dev $the_admin_path/etc $the_admin_path/sbin $the_admin_path/tmp $the_admin_path/usr $the_admin_path/var $the_admin_path/bin $the_admin_path/libexec";
		$mystring = exec("uname -m",$out,$ret);
		$arch = $out[0];
		if($arch == "x86_64"){
			$folder_list .= " $the_admin_path/lib64";
		}
		system("rm -rf ".$folder_list);
	}

	$adm_query = "UPDATE $pro_mysql_cronjob_table SET qmail_newu='yes',restart_qmail='yes',reload_named='yes',
	restart_apache='yes',gen_vhosts='yes',gen_named='yes',gen_qmail='yes',gen_webalizer='yes',gen_backup='yes',gen_ssh='yes',gen_fetchmail='yes' WHERE 1;";
	mysqli_query($mysqli_connection,$adm_query);
	triggerDomainListUpdate();
}

function DTCdeleteAdmin ($adm_to_del) {
	global $pro_mysql_admin_table;
	global $pro_mysql_domain_table;

	global $pro_mysql_vps_table;
	global $pro_mysql_dedicated_table;
	global $pro_mysql_tik_queries_table;
	global $pro_mysql_tik_atc_table;
	global $pro_mysql_cronjob_table;
	global $pro_mysql_ssl_ips_table;
	global $pro_mysql_custom_product_table;

	global $conf_demo_version;
	global $conf_mysql_db;
	if( !isFtpLogin($adm_to_del)){
		echo "Admin to delete is not in correct format line ".__LINE__." file ".__FILE__;
		die();
	}
	
	$adm_query = "SELECT * FROM $pro_mysql_admin_table WHERE adm_login='$adm_to_del'";
	$result = mysqli_query($mysqli_connection,$adm_query)or die("Cannot execute query \"$adm_query\" !!!");
	$num_rows = mysqli_num_rows($result);
	if($num_rows != 1)
		die("User not found for deletion of $adm_to_del !!!");
	$row_virtual_admin = mysqli_fetch_array($result);
	$the_admin_path = $row_virtual_admin["path"];

	// delete the user also mailboxs, ftp accounts, domains and subdomains in database
	$query = "SELECT * FROM $pro_mysql_domain_table WHERE owner='$adm_to_del';";
	$result = mysqli_query($mysqli_connection,$query)or die("Cannot execute query \"$query\" !!!");
	$num_rows = mysqli_num_rows($result);
	for($i=0;$i<$num_rows;$i++){
		$row = mysqli_fetch_array($result);
		//echo "Deleting ".$_REQUEST["delete_admin_user"]." / ".$row_virtual_admin["adm_pass"].$row["name"];
		deleteUserDomain($_REQUEST["delete_admin_user"],$row_virtual_admin["adm_pass"],$row["name"]);
	}

	if($conf_demo_version == "no"){
		system("rm -rf $the_admin_path");
	}

	// Make all SSL vhosts the user registered available again
	$q = "UPDATE $pro_mysql_ssl_ips_table SET available='yes' WHERE adm_login='$adm_to_del';";
	$r = mysqli_query($mysqli_connection,$q)or die("Cannot execute query $q line ".__LINE__." file ".__FILE__." sql said: ".mysqli_error());

	deleteMysqlUserAndDB($adm_to_del);

	// Delete all VPS of the user, and set all its IPs as available
	$q = "SELECT * FROM $pro_mysql_vps_table WHERE owner='$adm_to_del';";
	$r = mysqli_query($mysqli_connection,$q)or die("Cannot execute query \"$q\" line ".__LINE__." file ".__FILE__." sql said: ".mysqli_error());
	$n = mysqli_num_rows($r);
	for($i=0;$i<$n;$i++){
		$vps = mysqli_fetch_array($r);
		$q2 = "UPDATE $pro_mysql_vps_ip_table SET available='yes' WHERE vps_server_hostname='".$vps["vps_server_hostname"]."' AND vps_xen_name='".$vps["vps_xen_name"]."';";
		$r2 = mysqli_query($mysqli_connection,$q2)or die("Cannot execute query \"$q2\" line ".__LINE__." file ".__FILE__." sql said: ".mysqli_error());

		$q2 = "DELETE FROM $pro_mysql_vps_stats_table WHERE vps_server_hostname='".$vps["vps_server_hostname"]."' AND vps_xen_name='".$vps["vps_xen_name"]."';";
		$r2 = mysqli_query($mysqli_connection,$q2)or die("Cannot execute query \"$q2\" line ".__LINE__." file ".__FILE__." sql said: ".mysqli_error());

		// Unload (eg: destroy) the VPS directly
		remoteVPSAction($vps["vps_server_hostname"],$vps["vps_xen_name"],"destroy_vps");
		VPS_Server_Subscribe_To_Lists($vps["vps_server_hostname"]);
	}

	$q = "DELETE FROM $pro_mysql_vps_table WHERE owner='$adm_to_del';";
	$r = mysqli_query($mysqli_connection,$q)or die("Cannot execute query \"$q\" line ".__LINE__." file ".__FILE__." sql said: ".mysqli_error());

	// Delete all dedicated servers of the admin
	$q = "DELETE FROM $pro_mysql_dedicated_table WHERE owner='$adm_to_del';";
	$r = mysqli_query($mysqli_connection,$q)or die("Cannot execute query \"$q\" line ".__LINE__." file ".__FILE__." sql said: ".mysqli_error());

	// Delete all support tickets attachements of the admin
	$q = "SELECT attach FROM $pro_mysql_tik_queries_table WHERE adm_login='$adm_to_del' AND attach IS NOT NULL;";
	$r = mysqli_query($mysqli_connection,$q)or die("Cannot execute query \"$q\" line ".__LINE__." file ".__FILE__." sql said: ".mysqli_error());
	$n = mysqli_num_rows($r);
	for($i=0;$i<$n;$i++){
		$tik_a = mysqli_fetch_array($r);
		if($tik_a != ""){
			$exploded = explode("|",$tik_a);
			$num = sizeof($exploded);
			for($j=0;$j<$num;$j++){
				$id = $exploded[$j];
				if(isRandomNum($id)){
					$qd = "DELETE FROM $pro_mysql_tik_atc_table WHERE id='$id';";
					mysqli_query($mysqli_connection,$qd)or die("Cannot execute query \"$qd\" line ".__LINE__." file ".__FILE__." sql said: ".mysqli_error());
				}
			}
		}
	}

	// Delete all support tickets of the admin
	$q = "DELETE FROM $pro_mysql_tik_queries_table WHERE adm_login='$adm_to_del';";
	$r = mysqli_query($mysqli_connection,$q)or die("Cannot execute query \"$q\" line ".__LINE__." file ".__FILE__." sql said: ".mysqli_error());

	// Delete all custom products of the admin
	$q = "DELETE FROM $pro_mysql_custom_product_table WHERE owner='$adm_to_del';";
	$r = mysqli_query($mysqli_connection,$q)or die("Cannot execute query \"$q\" line ".__LINE__." file ".__FILE__." sql said: ".mysqli_error());

	$adm_query = "DELETE FROM $pro_mysql_admin_table WHERE adm_login='$adm_to_del'";
	mysqli_query($mysqli_connection,$adm_query)or die("Cannot execute query \"$adm_query\" !!!");

	// Tell the cron job to activate the changes (in case there was some shared accounts. Todo: check if there is some...)
	$adm_query = "UPDATE $pro_mysql_cronjob_table SET qmail_newu='yes',restart_qmail='yes',reload_named='yes',
	restart_apache='yes',gen_vhosts='yes',gen_named='yes',gen_qmail='yes',gen_webalizer='yes',gen_backup='yes',gen_ssh='yes',gen_fetchmail='yes' WHERE 1;";
	mysqli_query($mysqli_connection,$adm_query);
	triggerDomainListUpdate();
}

?>
