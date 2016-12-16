<?php

error_reporting(E_ALL);

$console = "";

// AUTO SQL CONFIG
// This file automates the use of a mysql database. It creates connection
// to it, and fetch all software config from the config table

$dtcshared_path = dirname(__FILE__);
$autoconf_configfile = "mysql_config.php";
global $mysqli_connection;
global $mysqli_connection_mysql;

require("$dtcshared_path/dtc_version.php");

function connect2base(){
	global $conf_mysql_host;
	global $conf_mysql_login;
	global $conf_mysql_pass;
	global $conf_mysql_db;

	$ressource_id = mysqli_connect("$conf_mysql_host", "$conf_mysql_login", "$conf_mysql_pass", "$conf_mysql_db");
	if($ressource_id == false)	return false;
	return $ressource_id;
}

function createTableIfNotExists(){
	global $console;
	if ($mysqli_connection == NULL || mysqli_ping($mysqli_connection) == false)
	{
		$mysqli_connection =connect2base();
	}
	if ($handle = opendir('tables/')) {
		// Create all tables with stored table creation SQL script in .../dtc/admin/tables/*.sql
		while (false !== ($file = readdir($handle))){
			if($file != "." && $file != ".." && $file != "tables/.." && $file != 'CVS'){
				$fp = fopen("tables/$file","r");
				$table_create_query = fread($fp,filesize("tables/$file"));
				fclose($fp);
				$table_name = preg_replace ("/.sql/", "", $file);
				$query = "SELECT * FROM $table_name WHERE 1 LIMIT 1;";
				$result = @mysqli_query($mysqli_connection,$query);
				if($result == false){
					mysqli_query($mysqli_connection,$table_create_query)or die("Cannot create table $table_name when querying :<br><font color=\"#FF0000\">$table_create_query</font> !!!".mysqli_error($mysqli_connection));
					$console .= "Table ".$table_name." has been created<br>";
				}else{
					// echo "Table ".$table_name." can be selected<br>";
				}
			}
		}
		closedir($handle); 

		// Verify that the groups and config tables have at least one record. If not, create it using default values.
		$query = "SELECT * FROM groups WHERE 1;";
		$result = mysqli_query($mysqli_connection,$query)or die("Cannot query $query !!!".mysqli_error($mysqli_connection));
		$num_rows = mysqli_num_rows($result);
		if($num_rows < 1){
			$query = "INSERT INTO groups (members) VALUES ('zigo')";
			$result = mysqli_query($mysqli_connection,$query)or die("Cannot query $query !!!".mysqli_error($mysqli_connection));
			$console .= "Default values has been inserted in groups table.<br>";
		}

		$query = "SELECT * FROM config WHERE 1;";
		$result = mysqli_query($mysqli_connection,$query)or die("Cannot query $query !!!".mysqli_error($mysqli_connection));
		$num_rows = mysqli_num_rows($result);
		if($num_rows < 1){
			$query = "INSERT INTO config () VALUES ()";
			$result = mysqli_query($mysqli_connection,$query)or die("Cannot query $query !!!".mysqli_error($mysqli_connection));
			$console .= "Default values has been inserted in config table.<br>";
		}

		$query = "SELECT * FROM cron_job WHERE 1;";
		$result = mysqli_query($mysqli_connection,$query)or die("Cannot query $query !!!".mysqli_error($mysqli_connection));
		$num_rows = mysqli_num_rows($result);
		if($num_rows < 1){
			$query = "INSERT INTO cron_job () VALUES ()";
			$result = mysqli_query($mysqli_connection,$query)or die("Cannot query $query !!!".mysqli_error($mysqli_connection));
			$console .= "Default values has been inserted in cron_job table.<br>";
		}
	}
}

// This function get all field from unic row "config" and convert them to
// global variables using the name of that field. Like if a field name is foo,
// then a global variable called $conf_foo will be created.
function getConfig(){
	global $conf_mysql_db;
	global $mysqli_connection;
	$query = "SELECT * FROM config WHERE 1 LIMIT 1;";
	$result = mysqli_query($mysqli_connection,$query)or die("Cannot query $query !!!".mysqli_error($mysqli_connection));
	$num_rows = mysqli_num_rows($result);
	if($num_rows != 1)	die("No config values in table !!!");
	$row = mysqli_fetch_array($result,MYSQLI_ASSOC);

	$fields = mysqli_query($mysqli_connection,"SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name='config' ORDER BY ordinal_position");

	while ($fields_row = $fields->fetch_row())
	{
		$field_name = $fields_row[0];
		$toto = "conf_".$field_name;
		global $$toto;
		$$toto = $row[$field_name];
	}
}

//////////////////////////////////////
//////////////////////////////////////
////                              ////
////   AUTOCONF STARTS HERE !!!   ////
////                              ////
//////////////////////////////////////
//////////////////////////////////////
// Include the config file, create it if not found
require("$dtcshared_path/$autoconf_configfile");

$mysqli_connection = connect2base();
if($mysqli_connection == false){
	die("Cannot connect to database !!!");
}
getConfig();

// Do all the updates according to upgrade_sql.php
if(!isset($conf_db_version)){
	$conf_db_version = 0;
}

if($conf_demo_version == 'yes'){
	@session_start();
	if(isset($demo_version_has_started)) $_SESSION["demo_version_has_started"]=$demo_version_has_started;
	if(!isset($_SESSION["demo_version_has_started"]) || $_SESSION["demo_version_has_started"] != "started"){
		$_SESSION["demo_version_has_started"] = "started";
		$query = "DELETE FROM admin;";
		$result = mysqli_query($mysqli_connection,$query)or die("Cannot query $query !!!".mysqli_error($mysqli_connection));
		$query = "DELETE FROM clients;";
		$result = mysqli_query($mysqli_connection,$query)or die("Cannot query $query !!!".mysqli_error($mysqli_connection));
		$query = "DELETE FROM commande;";
		$result = mysqli_query($mysqli_connection,$query)or die("Cannot query $query !!!".mysqli_error($mysqli_connection));
		$query = "DELETE FROM domain;";
		$result = mysqli_query($mysqli_connection,$query)or die("Cannot query $query !!!".mysqli_error($mysqli_connection));
		$query = "DELETE FROM ftp_access;";
		$result = mysqli_query($mysqli_connection,$query)or die("Cannot query $query !!!".mysqli_error($mysqli_connection));
		$query = "DELETE FROM pop_access;";
		$result = mysqli_query($mysqli_connection,$query)or die("Cannot query $query !!!".mysqli_error($mysqli_connection));
		$query = "DELETE FROM subdomain;";
		$result = mysqli_query($mysqli_connection,$query)or die("Cannot query $query !!!".mysqli_error($mysqli_connection));

		die("Welcom to DTC demo version. In demo version, all tables are erased at
		launch time.<br><br>
		<a href=\"?\">Ok, let's try !</a>
		");
	}
}

// add missing required "easy" function - http://mariolurig.com/coding/mysqli_result-function-to-match-mysql_result/
function mysqli_result($res,$row=0,$col=0){ 
    $numrows = mysqli_num_rows($res); 
    if ($numrows && $row <= ($numrows-1) && $row >=0){
        mysqli_data_seek($res,$row);
        $resrow = (is_numeric($col)) ? mysqli_fetch_row($res) : mysqli_fetch_assoc($res);
        if (isset($resrow[$col])){
            return $resrow[$col];
        }
    }
    return false;
}

?>
