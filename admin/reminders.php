<?php

$script_start_time = time();
$panel_type="cronjob";
require("../shared/autoSQLconfig.php"); // Our main configuration file
require_once("$dtcshared_path/dtc_lib.php");

// Send a mail to the admin with the error message
function sendAdminWarning($message){
  global $conf_webmaster_email_addr;
  $headers = "From: ".$conf_webmaster_email_addr;
  mail($conf_webmaster_email_addr,"[DTC] Reminder warning message!",$msg_2_send,$headers);
}

function getCustomizedReminder($msg,$client,$remaining_days,$expiration_date,$adm_login){
  global $conf_administrative_site;
  global $conf_use_ssl;

  $msg_2_send = $msg;
  $msg_2_send = str_replace("%%%FIRST_NAME%%%",$client,$msg_2_send);
  if($remaining_days > 0){
     $msg_2_send = str_replace("%%%REMAINING_DAYS%%%",$remaining_days,$msg_2_send);
  }else if($remaining_days == 0){
  }else{
    $how_many_days = 0 - $remaining_days;
    $msg_2_send = str_replace("%%%EXPIRED_DAYS%%%",$how_many_days,$msg_2_send);
  }
  $msg_2_send = str_replace("%%%EXPIRATION_DATE%%%",$expiration_date,$msg_2_send);
  $msg_2_send = str_replace("%%%DTC_ADM_LOGIN%%%",$adm_login,$msg_2_send);
  if($conf_use_ssl == "yes"){
    $surl = "s";
  }else{
    $surl = "";
  }
  $msg_2_send = str_replace("%%%DTC_CLIENT_URL%%%","http".$surl."://".$conf_administrative_site."/dtc/",$msg_2_send);
  return $msg_2_send;
}

// Send all the mail for VPS for a given renew period
function sendVPSReminderEmail($remaining_days,$file,$send_webmaster_copy="no"){
  global $pro_mysql_admin_table;
  global $pro_mysql_client_table;
  global $pro_mysql_vps_table;
  global $conf_webmaster_email_addr;

  $fname = "reminders_msg/".$file;
  $fp = fopen($fname,"r");
  $mesg = fread($fp,filesize($fname));
  fclose($fp);

  $now_timestamp = mktime();
  $one_day = 3600 * 24;
  $q = "SELECT * FROM $pro_mysql_vps_table WHERE expire_date='".date("Y-m-d",$now_timestamp + $one_day*$remaining_days)."';";
  $r = mysql_query($q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
  $n = mysql_num_rows($r);
  for($i=0;$i<$n;$i++){
    $vps = mysql_fetch_array($r);

    // Get the admin
    $q2 = "SELECT * FROM $pro_mysql_admin_table WHERE adm_login='".$vps["owner"]."';";
    $r2 = mysql_query($q2)or die("Cannot query $q2 line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
    $n2 = mysql_num_rows($r2);
    if($n2 != 1){
      sendAdminWarning("Could not get admin_login ".$vps["owner"]." line ".__LINE__." file ".__FILE__);
      continue;
    }
    $admin = mysql_fetch_array($r2);
    if($admin["id_client"] == 0){
      sendAdminWarning("Admin has no client id (".$admin["id_client"].") line ".__LINE__." file ".__FILE__);
      continue;
    }

    // Get the client
    $q2 = "SELECT * FROM $pro_mysql_client_table WHERE id='".$admin["id_client"]."';";
    $r2 = mysql_query($q2)or die("Cannot query $q2 line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
    $n2 = mysql_num_rows($r2);
    if($n2 != 1){
      sendAdminWarning("Could not find id client ".$admin["id_client"]." line ".__LINE__." file ".__FILE__);
      continue;
    }
    $client = mysql_fetch_array($r2);

    // Write the email
    $msg_2_send = $mesg;
    $msg_2_send = getCustomizedReminder($msg_2_send,$client["christname"],$remaining_days,$vps["expire_date"],$admin["adm_login"]);
    $msg_2_send = str_replace("%%%VPS_NUMBER%%%",$vps["vps_xen_name"],$msg_2_send);
    $msg_2_send = str_replace("%%%VPS_NODE%%%",$vps["vps_server_hostname"],$msg_2_send);

    $headers = "From: ".$conf_webmaster_email_addr;
    mail($client["email"],"[DTC] Your VPS expiration",$msg_2_send,$headers);
    if($send_webmaster_copy == "yes"){
      mail($conf_webmaster_email_addr,"[DTC] A VPS has expired",$msg_2_send,$headers);
    }
  }
}

////////////////////
// VPS EXPIRATION //
////////////////////
// Send reminders before expiration
$before = explode("|",$conf_vps_renewal_before);
$n = sizeof($before);
for($i=0;$i<$n;$i++){
  sendVPSReminderEmail($before[$i],"vps_will_expire.txt");
}
// Send reminders the day of the expiration
sendVPSReminderEmail(0,"vps_expired_today.txt","yes");
// Send reminders after expiration
$after = explode("|",$conf_vps_renewal_after);
$n = sizeof($after);
for($i=0;$i<$n;$i++){
  $days = 0 - $after[$i];
  sendVPSReminderEmail($days,"vps_expired_already.txt");
}
// Send reminders for last warning
sendVPSReminderEmail(-$conf_vps_renewal_lastwarning,"vps_expired_last_warning.txt","yes");
// Send the shutdown message
sendVPSReminderEmail(-$conf_vps_renewal_shutdown,"vps_expired_shutdown.txt","yes");

///////////////////////////////
// SHARED HOSTING EXPIRATION //
///////////////////////////////
function sendSharedHostingReminderEmail($remaining_days,$file,$send_webmaster_copy="no"){
  global $pro_mysql_admin_table;
  global $pro_mysql_domain_table;
  global $pro_mysql_client_table;

  global $conf_webmaster_email_addr;

  $fname = "reminders_msg/".$file;
  $fp = fopen($fname,"r");
  $mesg = fread($fp,filesize($fname));
  fclose($fp);

  $now_timestamp = mktime();
  $one_day = 3600 * 24;
  $q = "SELECT * FROM $pro_mysql_admin_table WHERE expire='".date("Y-m-d",$now_timestamp + $one_day*$remaining_days)."';";
  $r = mysql_query($q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
  $n = mysql_num_rows($r);
  for($i=0;$i<$n;$i++){
    $admin = mysql_fetch_array($r);
    
    // Check to see if the user has some domain name hosted (it could be an admin only for VPS or Dedicated...)
    $q2 = "SELECT * FROM $pro_mysql_domain_table WHERE owner='".$admin["adm_login"]."';";
    $r2 = mysql_query($q2)or die("Cannot query $q2 line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
    $n2 = mysql_num_rows($r2);
    if($n2 < 0){
      continue;
    }
    
    $q2 = "SELECT * FROM $pro_mysql_client_table WHERE id='".$admin["id_client"]."';";
    $r2 = mysql_query($q2)or die("Cannot query $q2 line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
    $n2 = mysql_num_rows($r2);
    if($n2 != 1){
      sendAdminWarning("Could not find id client ".$admin["id_client"]." line ".__LINE__." file ".__FILE__);
      continue;
    }
    $client = mysql_fetch_array($r2);

    // Write the email
    $msg_2_send = $mesg;
    $msg_2_send = getCustomizedReminder($msg_2_send,$client["christname"],$remaining_days,$admin["expire"],$admin["adm_login"]);

    $headers = "From: ".$conf_webmaster_email_addr;
    mail($client["email"],"[DTC] Your shared hosting expiration",$msg_2_send,$headers);
    if($send_webmaster_copy == "yes"){
      mail($conf_webmaster_email_addr,"[DTC] A shared hosting account has expired",$msg_2_send,$headers);
    }
    
  }
}

// Send reminders before expiration
$before = explode("|",$conf_shared_renewal_before);
$n = sizeof($before);
for($i=0;$i<$n;$i++){
  sendSharedHostingReminderEmail($before[$i],"shared_will_expire.txt");
}
// Send reminder the day of expiration
sendSharedHostingReminderEmail(0,"shared_expired_today.txt","yes");
// Send reminders after expiration
$after = explode("|",$conf_shared_renewal_after);
$n = sizeof($after);
for($i=0;$i<$n;$i++){
  $days = 0 - $after[$i];
  sendSharedHostingReminderEmail($days,"shared_expired_already.txt");
}
// Send last warning
sendSharedHostingReminderEmail(-$conf_shared_renewal_lastwarning,"shared_expired_last_warning.txt","yes");
// Send rexpiration reminder
sendSharedHostingReminderEmail(-$conf_shared_renewal_shutdown,"shared_expired_shutdown.txt","yes");

?>