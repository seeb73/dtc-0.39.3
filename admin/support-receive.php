#!/usr/bin/env php
<?php

chdir(dirname(__FILE__));

$panel_type="cronjob";
require("../shared/autoSQLconfig.php"); // Our main configuration file
require_once("$dtcshared_path/dtc_lib.php");

// This comes from the Mail_Mime PEAR package, under Debian, you need
// the php-mail-mime package to have this script work.
require_once 'Mail/mimeDecode.php';

// Email header parsing
function decodeEmail($input){
	$params['include_bodies'] = true;
	$params['decode_bodies']  = true;
	$params['decode_headers'] = true;
	$decoder = new Mail_mimeDecode($input);
	$structure = $decoder->decode($params);
	return $structure;
}

// Read the email from standard input
$msg = "";
$fp = fopen('php://stdin', 'r');
while($line = fgets($fp, 4096) ){
	$msg .= $line;
}

$DEBUG_ME = 0;
if($DEBUG_ME == 1){
	@mkdir("/tmp/support/");
	$debug_fp = fopen("/tmp/support/".date('Y-m-d')."_".date('H-i-s')."_".getRandomValue().".txt","w+");
	fwrite($debug_fp,$msg);
	fclose($debug_fp);
}

// Decode the msg using php-mail-mime
$stt = decodeEmail($msg);

// Get the From: header email
$flag = preg_match_all("/[\._a-zA-Z0-9+-]+@[\._a-zA-Z0-9-]+/i", $stt->headers["from"], $matches);
if($flag == 0 || sizeof($matches) != 1){
	echo("No email found in From! :(\n");
	exit(1);
}
$email_from = $matches[0][0];

// Do nothing if there's a mail from an auto-responder
if( isset($stt->headers["X-AutoReply-From"]) || isset($stt->headers["X-Mail-Autoreply"]) 
	|| (isset($stt->headers["Auto-Submitted"]) && $stt->headers["Auto-Submitted"] != "no") ){
	exit(0);
}

// TODO: Check the Cc as well
// Get the To: header email
$flag = preg_match_all("/[\._a-zA-Z0-9+-]+@[\._a-zA-Z0-9-]+/i", $stt->headers["to"], $matches);
if($flag == 0){
	echo("No email found in To! :(\n");
	exit(1);
}

// Build the support ticket email regexp
if( !isset($conf_support_ticket_domain) || $conf_support_ticket_domain == "default"){
	$tik_domain = $conf_main_domain;
}else{
	$tik_domain = $conf_support_ticket_domain;
}
$tik_regexp = '^' . $conf_support_ticket_email . "[-+]([a-f0-9]*)@" . $tik_domain . '$';

$email_to = $matches[0][0];
$n = sizeof($matches[0]);
for($i = 0;$i<$n;$i++){
	if( preg_match("/".$tik_regexp."/",$matches[0][$i]) ){
		$email_to = $matches[0][$i];
	}
}

//echo "From: $email_from To: $email_to\n";

// This is to avoid any hacking with support mail looping to itself.
if( preg_match("/".$tik_regexp."/",$email_from) ){
	echo "From email is the one of our support ticket, we can't allow this!";
	exit(1);
}

if(isset($stt->parts)){
	// If the email is a multipart mime message, search for the text version
	unset($text_part);
	unset($html_part);
	$n_parts = sizeof($stt->parts);
	for($i=0;$i<$n_parts;$i++){
		if($stt->parts[$i]->ctype_primary == "text" && $stt->parts[$i]->ctype_secondary == "plain"){
//			echo "Plain part is $i\n";
			$text_part = $i;
		}
		if($stt->parts[$i]->ctype_primary == "text" && $stt->parts[$i]->ctype_secondary == "html"){
//			echo "Html part is $i\n";
			$html_part = $i;
		}
	}
	if( !isset($text_part) && !isset($html_part)){
		echo "We only support multipart messages in HTML and PLAIN format!";
		exit(1);
	}
	if( !isset($text_part) ){
		$body = strip_tags( $stt->parts[$html_part]->body , "<p><a><br><b>");
	}else{
		$body = $stt->parts[$text_part]->body;
		$charset = $stt->parts[$text_part]->ctype_parameters["charset"];
	}
	// Search for images attached to the mail
	$attachements_ids = "";
	for($i=0;$i<$n_parts;$i++){
		// Accept only certain mime types we want
		switch($stt->parts[$i]->ctype_primary){
		case "image":
			if($stt->parts[$i]->ctype_secondary != "gif" &&
				$stt->parts[$i]->ctype_secondary != "jpeg" &&
				$stt->parts[$i]->ctype_secondary != "png" &&
				$stt->parts[$i]->ctype_secondary != "tiff" &&
				$stt->parts[$i]->ctype_secondary != "x-ms-bmp"){
				continue;
			}
			break;
		case "application":
			if($stt->parts[$i]->ctype_secondary != "pdf" &&
				$stt->parts[$i]->ctype_secondary != "rar" &&
				$stt->parts[$i]->ctype_secondary != "rtf" &&
				$stt->parts[$i]->ctype_secondary != "zip" &&
				$stt->parts[$i]->ctype_secondary != "vnd.ms-powerpoint" &&
				$stt->parts[$i]->ctype_secondary != "vnd.oasis.opendocument.presentation" &&
				$stt->parts[$i]->ctype_secondary != "vnd.oasis.opendocument.spreadsheet" &&
				$stt->parts[$i]->ctype_secondary != "vnd.oasis.opendocument.text" &&
				$stt->parts[$i]->ctype_secondary != "x-httpd-php" &&
				$stt->parts[$i]->ctype_secondary != "x-tar" &&
				$stt->parts[$i]->ctype_secondary != "x-gtar"){
				continue;
			}
			break;
		case "message":
			if($stt->parts[$i]->ctype_secondary != "rfc822"){
				continue;
			}
			break;
		case "video":
			if($stt->parts[$i]->ctype_secondary != "mpeg" &&
				$stt->parts[$i]->ctype_secondary != "mp4" &&
				$stt->parts[$i]->ctype_secondary != "quicktime" &&
				$stt->parts[$i]->ctype_secondary != "x-ms-asf" &&
				$stt->parts[$i]->ctype_secondary != "x-ms-wmv" &&
				$stt->parts[$i]->ctype_secondary != "x-msvideo")
				continue;
			break;
		}
		$file_name = mysql_real_escape_string($stt->parts[$i]->ctype_parameters["name"]);
		$file_body = bin2hex($stt->parts[$i]->body);
		$q = "INSERT INTO tik_attach (id,filename,ctype_prim,ctype_sec,datahex)
VALUES ('','$file_name','".mysql_real_escape_string($stt->parts[$i]->ctype_primary)."','".mysql_real_escape_string($stt->parts[$i]->ctype_secondary)."','$file_body');";
		$r = mysqli_query($mysql_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
		$id = mysql_insert_id();
		if($attachements_ids != ""){
			$attachements_ids .= "|";
		}
		$attachements_ids .= mysql_insert_id();
	}
}else{
	$body = $stt->body;
	$charset = $stt->ctype_parameters["charset"];
}

//echo "$charset\n";
$body = mb_convert_encoding($body,"UTF-8",strtoupper($charset));
//echo $body;
//exit(1);

// Check if the To: has the support ID number in it
// emails are sent to something like: support-3bc8212a0@dtc.example.com
// and that a record really exists for it
if( preg_match("/".$tik_regexp."/",$email_to) ){
	// If the To: match an existing ID of a previous ticket, then we should search for that ticket
	$start = strlen($conf_support_ticket_email) + 1;
	$end = strlen($email_to) - $start - strlen($tik_domain) - 1; // Size of the email - size of "support+" - size of "@domain.tld"
	$ticket_hash = substr($email_to,$start,$end);
	if( isRandomNum($ticket_hash) ){
		$q = "SELECT * FROM $pro_mysql_tik_queries_table WHERE hash='$ticket_hash';";
		$r = mysqli_query($mysql_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
		$n = mysqli_num_rows($r);
		if($n == 1){
			// We have a match, we should consider inserting this ticket as a reply...
			$start_tik = mysqli_fetch_array($r);
			if($start_tik["adm_login"] == "" && isValidEmail($start_tik["customer_email"])){
				$request_adm_name = $start_tik["customer_email"];
			}else{
				$request_adm_name = $start_tik["adm_login"];
			}

			// Reopen the ticket if it was closed
			$q = "UPDATE $pro_mysql_tik_queries_table SET closed='no' WHERE hash='$ticket_hash';";
			$r = mysqli_query($mysql_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());

			$last_id = findLastTicketID($ticket_hash);
			if($last_id != 0){
				$q = "INSERT INTO $pro_mysql_tik_queries_table (id,adm_login,date,time,in_reply_of_id,reply_id,admin_or_user,text,initial_ticket,attach)
				VALUES('','".$start_tik["adm_login"]."','".date('Y-m-d')."','".date('H:i:s')."','$last_id','0','user','". mysql_real_escape_string($body) ."','no','$attachements_ids');";
				$r = mysqli_query($mysql_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
				$new_id = mysql_insert_id();
				$q = "UPDATE $pro_mysql_tik_queries_table SET reply_id='$new_id' WHERE id='$last_id';";
				$r = mysqli_query($mysql_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
				mailTicketToAllAdmins($start_tik["subject"],$body,$request_adm_name);
				exit(0);
			}
		}
	}
}


echo "Not an old ticket, searching for a matching customer\n";
$q = "SELECT id FROM $pro_mysql_client_table WHERE email='$email_from';";
$r = mysqli_query($mysql_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
$n = mysqli_num_rows($r);
// A matching email has been found
if($n == 1){
	$a = mysqli_fetch_array($r);
	$q = "SELECT adm_login FROM $pro_mysql_admin_table WHERE id_client='".$a["id"]."';";
	$r = mysqli_query($mysql_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
	$n = mysqli_num_rows($r);
	// At this point, we got an exact match: let's create a new ticket for this adm_login!
	if($n == 1){
		$adm = mysqli_fetch_array($r);
		$q = "INSERT INTO $pro_mysql_tik_queries_table (id,adm_login,date,time,in_reply_of_id,reply_id,admin_or_user,text,initial_ticket,hash,subject,attach)
		VALUES('','".$adm["adm_login"]."','".date('Y-m-d')."','".date('H:i:s')."','0','0','user','". mysql_real_escape_string($body) ."','yes','".createSupportHash()."','". mysql_real_escape_string($stt->headers["subject"]) ."','$attachements_ids');";
		$r = mysqli_query($mysql_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
		mailTicketToAllAdmins($stt->headers["subject"],$body,$adm["adm_login"]);
		exit(0);
	}
// If nothing matches, then we want to create a new ticket associated with
// this email address.
}else{
	$q = "INSERT INTO $pro_mysql_tik_queries_table (id,customer_email,date,time,in_reply_of_id,reply_id,admin_or_user,text,initial_ticket,hash,subject,attach)
	VALUES('','$email_from','".date('Y-m-d')."','".date('H:i:s')."','0','0','user','". mysql_real_escape_string($body) ."','yes','".createSupportHash()."','". mysql_real_escape_string($stt->headers["subject"]) ."','$attachements_ids');";
	$r = mysqli_query($mysql_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
	mailTicketToAllAdmins($stt->headers["subject"],$body,$email_from);
}
exit(0);

?>
