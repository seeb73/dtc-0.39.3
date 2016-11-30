<?php

function mailTicketToAllAdmins($subject,$body,$adm_login){
	global $pro_mysql_tik_admins_table;
	global $mysqli_connection;
	global $conf_webmaster_email_addr;
	global $send_email_header;

	global $conf_message_subject_header;
	global $mysqli_connection;

	if(isset($_REQUEST["server_hostname"])){
		$thehostname = "Server host name: ".$_REQUEST["server_hostname"];
	}else{
		$thehostname = "";
	}

	$q = "SELECT * FROM $pro_mysql_tik_admins_table WHERE available='yes';";
	$r = mysqli_query($mysqli_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said ".mysqli_error());
	$n = mysqli_num_rows($r);
	for($i=0;$i<$n;$i++){
		$a = mysqli_fetch_array($r);
		$content = "A customer has submitted a support ticket.
Below is a copy of his message:

**********
Subject: ".stripslashes($subject)."
Admin login: $adm_login
$thehostname

".stripslashes($body)."
**********
";
		$headers = $send_email_header;
		$headers .= "X-DTC-Support-Ticket: Reply-From-Customer\n";
		$headers .= "From: ".$conf_webmaster_email_addr;
		mail($a["email"],"$conf_message_subject_header $adm_login has submitted a support ticket",$content,$headers);
	}
}

function ticket_get_attach(){
	if( !is_array($_FILES["attach"])
			|| !isset($_FILES["attach"]["tmp_name"])
			|| !isset($_FILES["attach"]["name"])
			|| !isset($_FILES["attach"]["type"]) ){
		return "";
	}
	$tmp_name = $_FILES["attach"]["tmp_name"];
	$filename = $_FILES["attach"]["name"];
	$filetype = $_FILES["attach"]["type"];
	if( (!isset($filetype) ) || $filetype == ""){
		return "";
	}
	$types = explode("/",$filetype);
	$prim = $types[0];
	$sec = $types[1];
	switch($prim){
	case "image":
		if($sec != "gif" && $sec != "jpeg" && $sec != "png" && $sec != "tiff" && $sec != "x-ms-bmp"){
			echo _("Sorry, we do not accept this type of file attachment");
			return "";
		}
		break;
	case "application":
		if($sec != "pdf" && $sec != "rar" && $sec != "rtf" && $sec != "zip" && $sec != "vnd.ms-powerpoint" &&
				$sec != "vnd.oasis.opendocument.presentation" && $sec != "vnd.oasis.opendocument.spreadsheet" &&
				$sec != "vnd.oasis.opendocument.text" && $sec != "x-httpd-php" && $sec != "x-tar" && $sec != "x-gtar"){
			echo _("Sorry, we do not accept this type of file attachment");
			return "";
		}
		break;
	case "text":
		if($sec != "plain"){
			echo _("Sorry, we do not accept this type of file attachment");
			return "";
		}
		break;
	case "message":
		if($stt->parts[$i]->ctype_secondary != "rfc822"){
			echo _("Sorry, we do not accept this type of file attachment");
			return "";
		}
		break;
	case "video":
		if($sec != "mpeg" && $sec != "mp4" && $sec != "quicktime" && $sec != "x-ms-asf" && $sec != "x-ms-wmv" && $sec != "x-msvideo"){
			echo _("Sorry, we do not accept this type of file attachment");
			return "";
		}
		break;
	default:
		echo _("Sorry, we do not accept this type of file attachment");
		return "";
	}
	$content = file_get_contents($tmp_name);
	$hex = bin2hex($content);
	$q = "INSERT INTO tik_attach (id,filename,ctype_prim,ctype_sec,datahex)
VALUES ('','".mysqli_real_escape_string($mysqli_connection,$filename)."','".mysqli_real_escape_string($mysqli_connection,$prim)."','".mysqli_real_escape_string($mysqli_connection,$sec)."','$hex');";
	$r = mysqli_query($mysqli_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said ".mysqli_error());
	$id = mysqli_insert_id($mysqli_connection);
	return $id;
}

// action=new_ticket&subject=test+subject&server_hostname=test.vpsserver.com%3A01&issue_cat_id=network&ticketbody=I+can%27t+connect+to+my+VPS%21
if(isset($_REQUEST["action"]) && $_REQUEST["action"] == "new_ticket"){
	checkLoginPass($adm_login,$adm_pass);
	if( strlen($_REQUEST["subject"]) == 0){
		echo _("Subject line empty: cannot send ticket.");
	}else{
		$hash = createSupportHash();
		$attach = ticket_get_attach();
		$q = "INSERT INTO $pro_mysql_tik_queries_table (id,adm_login,date,time,subject,text,cat_id,initial_ticket,server_hostname,hash,attach)
		VALUES ('','$adm_login','".date("Y-m-d")."','".date("H:i:s")."','".mysqli_real_escape_string($mysqli_connection,$_REQUEST["subject"])."','".mysqli_real_escape_string($mysqli_connection,$_REQUEST["ticketbody"])."','".mysqli_real_escape_string($mysqli_connection,$_REQUEST["issue_cat_id"])."','yes','".mysqli_real_escape_string($mysqli_connection,$_REQUEST["server_hostname"])."','$hash','$attach');";
		$r = mysqli_query($mysqli_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said ".mysqli_error());
		mailTicketToAllAdmins($_REQUEST["subject"],$_REQUEST["ticketbody"],$adm_login);
	}
}

if(isset($_REQUEST["action"]) && $_REQUEST["action"] == "add_ticket_reply"){
	checkLoginPass($adm_login,$adm_pass);
	if(!isRandomNum($_REQUEST["last_tik_id"]) || !isRandomNum($_REQUEST["tik_id"])){
		echo _("last_tick_id or tik_id is not a number: hacking attempt.");
	}else{
		// Check if admin is owning the ticket
		$q = "SELECT * FROM $pro_mysql_tik_queries_table WHERE id='".$_REQUEST["last_tik_id"]."' AND reply_id='0' AND adm_login='$adm_login';";
		$r = mysqli_query($mysqli_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said ".mysqli_error());
		$n = mysqli_num_rows($r);
		if($n != 1){
			echo _("This ticket number isn't owned by you (last_tik_id is wrong).");
		}else{
			$q = "SELECT * FROM $pro_mysql_tik_queries_table WHERE id='".$_REQUEST["tik_id"]."' AND adm_login='$adm_login';";
			$r = mysqli_query($mysqli_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said ".mysqli_error());
			$n = mysqli_num_rows($r);
			if($n != 1){
				echo _("This ticket number isn't owned by you (tik_id is wrong).");
			}else{
				// Insert the new ticket
				$attach = ticket_get_attach();
				$q = "INSERT INTO $pro_mysql_tik_queries_table (id,adm_login,date,time,subject,text,cat_id,initial_ticket,server_hostname,in_reply_of_id,request_close,attach)
				VALUES ('','$adm_login','".date("Y-m-d")."','".date("H:i:s")."','".mysqli_real_escape_string($mysqli_connection,$_REQUEST["subject"])."','".mysqli_real_escape_string($mysqli_connection,$_REQUEST["ticketbody"])."','".mysqli_real_escape_string($mysqli_connection,$_REQUEST["cat_id"])."','no','".mysqli_real_escape_string($mysqli_connection,$_REQUEST["server_hostname"])."','".mysqli_real_escape_string($mysqli_connection,$_REQUEST["last_tik_id"])."','".mysqli_real_escape_string($mysqli_connection,$_REQUEST["request_to_close"])."','$attach');";
				$r = mysqli_query($mysqli_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said ".mysqli_error());
				$ins_id = mysqli_insert_id($mysqli_connection);
				// Update the chained list of tickets
				$q = "UPDATE $pro_mysql_tik_queries_table SET reply_id='$ins_id' WHERE id='".$_REQUEST["last_tik_id"]."';";
				$r = mysqli_query($mysqli_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said ".mysqli_error());
				// Set the initial ticket as reopen in case it was closed
				$q = "UPDATE $pro_mysql_tik_queries_table SET closed='no' WHERE id='".$_REQUEST["tik_id"]."';";
				$r = mysqli_query($mysqli_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said ".mysqli_error());
				mailTicketToAllAdmins($_REQUEST["subject"],$_REQUEST["ticketbody"],$adm_login);
			}
		}
	}
}

?>
