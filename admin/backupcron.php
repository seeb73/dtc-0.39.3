#!/usr/bin/env php
<?php

if(function_exists("date_default_timezone_set") and function_exists("date_default_timezone_get"))
@date_default_timezone_set(@date_default_timezone_get());

// 23 hours max timeout for the cron.php run since backup is done at least once a day
$_timelimit = 82800;
set_time_limit($_timelimit);

chdir(dirname(__FILE__));

$start_stamps = gmmktime(date("H"), date("i"), date("s"), date("m"), date("d"), date("Y"));
$panel_type="cronjob";

require("../shared/autoSQLconfig.php"); // Our main configuration file
require_once("$dtcshared_path/dtc_lib.php");

require_once("genfiles/genfiles.php");

global $conf_ftp_backup_activate;
global $conf_ftp_backup_frequency;
global $conf_generated_file_path;
if($conf_ftp_backup_activate == "yes"){
	$do_ftp_backup = "no";
	switch($conf_ftp_backup_frequency){
	case "day":
		$do_ftp_backup = "yes";
		break;
	case "week":
		if(date("N",$start_stamps) == "1"){
			$do_ftp_backup = "yes";
		}
		break;
	case "month":
		if(date("j",$start_stamps) == "1"){
			$do_ftp_backup = "yes";
		}
		break;
	default:
		break;
			}
	if($do_ftp_backup == "yes"){
		echo "Launching ftp backup script !\n";
		system("$conf_generated_file_path/net_backup.sh &");
	}
}

?>
