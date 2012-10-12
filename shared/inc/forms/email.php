<?php

function fetchmailAccountsCreateCallback($id){
	updateUsingCron("gen_fetchmail='yes'");
}
function fetchmailAccountsDeleteCallback($id){
	updateUsingCron("gen_fetchmail='yes'");
}
function fetchmailAccountsEditCallback($id){
	updateUsingCron("gen_fetchmail='yes'");
}
function drawImportedMail($mailbox){
	global $adm_email_login;
	global $adm_email_pass;
	global $errTxt;
	global $pro_mysql_fetchmail_table;

	$mydomain = $mailbox["data"]["mbox_host"];
	$myuserid = $mailbox["data"]["id"];

	$out = "";
	$dsc = array(
		"title" => _("List of your fetchmail imported accounts:") ,
		"new_item_title" => _("New fetchmail address") ,
		"new_item_link" => _("new fetchmail address") ,
		"edit_item_title" => _("Fetchmail configuration:") ,
		"table_name" => $pro_mysql_fetchmail_table,
		"action" => "fetchmail_table_editor",
		"forward" => array("adm_email_login","adm_email_pass","addrlink"),
		"id_fld" => "id",
		"list_fld_show" => "pop3_email",
		"max_item" => 3,
		"num_item_txt" => _("Number of active fetchmail imported email boxes:") ,
		"where_list" => array(
			"domain_name" => $mydomain,
			"domain_user" => $myuserid),
		"check_unique" => array( "pop3_email" ),
		"check_unique_msg" => _("There is already a mailbox by that name") ,
		"order_by" => "pop3_email",
		"create_item_callback" => "fetchmailAccountsCreateCallback",
		"delete_item_callback" => "fetchmailAccountsDeleteCallback",
		"edit_item_callback" => "fetchmailAccountsEditCallback",
		"cols" => array(
			"id" => array(
				"type" => "id",
				"display" => "no",
				"legend" => _("Login:") ),
			"pop3_email" => array(
				"type" => "text",
				"check" => "email",
				"legend" => _("Email to fetch:") ),
			"pop3_server" => array (
				"type" => "text",
				"check" => "subdomain_or_ip",
				"legend" => _("Mail server to import from:") ),
			"pop3_login" => array(
				"type" => "text",
				"check" => "dtc_login_or_email",
				"legend" => _("Login:") ),
			"pop3_pass" => array(
				"type" => "password",
				"legend" => _("Password:") ),
			"checkit" => array(
				"type" => "checkbox",
				"values" => array( "yes","no"),
				"default" => "no",
				"legend" => _("Use it:") ),
			)
		);
        $out = dtcListItemsEdit($dsc);
	return $out;
}

function drawAdminTools_emailAccount($mailbox){	
	global $adm_email_login;
	global $adm_email_pass;
	global $cyrus_used;
	global $conf_post_or_get;

	$url_start = "<a href=\"?adm_email_login=$adm_email_login&adm_email_pass=$adm_email_pass&addrlink=".$_REQUEST["addrlink"];
	$form_start = "<form method=\"$conf_post_or_get\" action=\"?\" method=\"post\">
<input type=\"hidden\" name=\"adm_email_login\" value=\"$adm_email_login\">
<input type=\"hidden\" name=\"adm_email_pass\" value=\"$adm_email_pass\">
<input type=\"hidden\" name=\"addrlink\" value=\"".$_REQUEST["addrlink"]."\">";

	// Draw the form for changing the password
	$left = "<h3>". _("Change your password:") ."</h3>
<table cellpadding=\"0\" cellspacing=\"0\">
<tr>
	<td align=\"right\">".$form_start. _("Password: ") ."</td>
	<td><input type=\"hidden\" name=\"action\" value=\"dtcemail_change_pass\"><input type=\"password\" name=\"newpass1\" value=\"\"></td>
</tr><tr>
	<td align=\"right\">". _("Confirm password: ") ."</td>
	<td><input type=\"password\" name=\"newpass2\" value=\"\"></td>
</tr><tr>
	<td></td><td>". drawSubmitButton( _("Ok") ) ."</form></td>
</tr></table>
<br><br>";

	if($mailbox["data"]["vacation_flag"] == "yes"){
		$use_vacation_msg_yes_checked = " checked ";
		$use_vacation_msg_no_checked = " ";
	}else{
		$use_vacation_msg_yes_checked = " ";
		$use_vacation_msg_no_checked = " checked ";
	}

	if (!$cyrus_used){
  	$left .= "<h3>" . _("Vacation message") . "</h3>
  	".$form_start."<input type=\"hidden\" name=\"action\" value=\"dtcemail_vacation_msg\">
  <input type=\"radio\" name=\"use_vacation_msg\" value=\"yes\" $use_vacation_msg_yes_checked>"._("Yes")."<input type=\"radio\" name=\"use_vacation_msg\" value=\"no\" $use_vacation_msg_no_checked>"._("No")."
  <br>
  <textarea cols=\"40\" rows=\"7\" name=\"vacation_msg_txt\">".$mailbox["data"]["vacation_text"]."</textarea><br>
  ". drawSubmitButton( _("Ok") ) ."</form>";
  }
	if($mailbox["data"]["localdeliver"] == "yes"){
		$deliverUrl = "$url_start&action=dtcemail_set_deliver_local&setval=no\"><font color=\"green\">"._("Yes")."</font></a>";
	}else{
		$deliverUrl = "$url_start&action=dtcemail_set_deliver_local&setval=yes\"><font color=\"red\">"._("No")."</font></a>";
	}
	if (!$cyrus_used){
    	$right = "<h3>". _("Edit your mailbox redirections:") ."</h3>
    ". _("Deliver messages locally in INBOX: ") ." $deliverUrl
    <table cellpadding=\"0\" cellspacing=\"0\">
    <tr>
    	<td align=\"right\">".$form_start. _("Redirection 1: ") ."</td>
    	<td><input type=\"hidden\" name=\"action\" value=\"dtcemail_edit_redirect\"><input type=\"text\" name=\"redirect1\" value=\"".$mailbox["data"]["redirect1"]."\"></td>
    </tr><tr>
    	<td>". _("Redirection 2: ") ."</td>
    	<td><input type=\"text\" name=\"redirect2\" value=\"".$mailbox["data"]["redirect2"]."\"></td>
    </tr><tr>
    	<td></td><td>". drawSubmitButton( _("Ok") ) ."</form></td>
    </tr></table><br><br>";

    	if($mailbox["data"]["spam_mailbox_enable"] == "yes"){
    		$spambox_yes_checked = " checked ";
    		$spambox_no_checked = " ";
    	}else{
    		$spambox_yes_checked = " ";
    		$spambox_no_checked = " checked ";
    	}
    	if($mailbox["data"]["spam_lover"] == "Y"){
    		$spam_lover_yes_checked = " checked ";
    		$spam_lover_no_checked = " ";
    	}else{
    		$spam_lover_yes_checked = " ";
    		$spam_lover_no_checked = " checked ";
    	}

    	$right .= "<h3>" . _("Anti-SPAM control") . "</h3>
    <table cellpadding=\"0\" cellspacing=\"0\">
    <tr>
    	<td align=\"right\">" . _("Enable SPAM filtering: ") . "</td><td>".$form_start."<input type=\"hidden\" name=\"action\" value=\"dtcemail_spambox\">
    <input type=\"radio\" name=\"spam_mailbox_enable\" value=\"yes\" $spambox_yes_checked>"._("Yes")."<input type=\"radio\" name=\"spam_mailbox_enable\" value=\"no\" $spambox_no_checked>"._("No")."</td>
    </tr><tr>
    	<td align=\"right\">" . _("SPAM mailbox name") . ":</td><td><input type=\"text\" name=\"spam_mailbox\" value=\"".htmlspecialchars($mailbox["data"]["spam_mailbox"])."\"></td>
    </tr><tr>
<!--spam tag level-->
    	<td align=\"right\">" . _("Spam tag level (possible spam)") . ":</td><td><input type=\"text\" name=\"spam_tag_level\" value=\"".htmlspecialchars($mailbox["data"]["spam_tag_level"])."\"></td>
    </tr><tr>
    	<td align=\"right\">" . _("Subject for possible spam") . ":</td><td><input type=\"text\" name=\"spam_subject_tag\" value=\"".htmlspecialchars($mailbox["data"]["spam_subject_tag"])."\"></td>
    </tr><tr>
<!--tag2 level-->
    	<td align=\"right\">" . _("Spam tag2 level (spam)") . ":</td><td><input type=\"text\" name=\"spam_tag2_level\" value=\"".htmlspecialchars($mailbox["data"]["spam_tag2_level"])."\"></td>
    </tr><tr>
    	<td align=\"right\">" . _("Subject for spam") . ":</td><td><input type=\"text\" name=\"spam_subject_tag2\" value=\"".htmlspecialchars($mailbox["data"]["spam_subject_tag2"])."\"></td>
    </tr><tr>
<!--tag3 level-->
    	<td align=\"right\">" . _("Spam tag3 level (extreme spam)") . ":</td><td><input type=\"text\" name=\"spam_tag3_level\" value=\"".htmlspecialchars($mailbox["data"]["spam_tag3_level"])."\"></td>
    </tr><tr>
    	<td align=\"right\">" . _("Subject for extreme spam (only Amavis above V2.7)") . ":</td><td><input type=\"text\" name=\"spam_subject_tag3\" value=\"".htmlspecialchars($mailbox["data"]["spam_subject_tag3"])."\"></td>
    </tr><tr>
<!--kill level-->
    	<td align=\"right\">" . _("Spam kill level (discart or bounce mails above this level)") . ":</td><td><input type=\"text\" name=\"spam_kill_level\" value=\"".htmlspecialchars($mailbox["data"]["spam_kill_level"])."\"></td>
    </tr><tr>
<!--spam lover-->
    	<td align=\"right\">" . _("Receive spam mails (even above kill level)") . ":</td><td>    <input type=\"radio\" name=\"spam_lover\" value=\"Y\" $spam_lover_yes_checked>"._("Yes")."<input type=\"radio\" name=\"spam_lover\" value=\"N\" $spam_lover_no_checked>"._("No")."
</td>
    </tr><tr>
<!--quarantine spam to email-->
    	<td align=\"right\">" . _("Quarantine spam to email") . ":</td><td><input type=\"text\" name=\"spam_quarantine_to\" value=\"".htmlspecialchars($mailbox["data"]["spam_quarantine_to"])."\"></td>
    </tr><tr>
<!--do not quarantine above this level-->
    	<td align=\"right\">" . _("Quarantine cutoff level (Do not quarantine above this level)") . ":</td><td><input type=\"text\" name=\"quarantine_cutoff_level\" value=\"".htmlspecialchars($mailbox["data"]["quarantine_cutoff_level"])."\"></td>
    </tr><tr>
<!--addr extension for spam mails-->
    	<td align=\"right\">" . _("Add this extension to the receivers mail address (above tag2 level)") . ":</td><td><input type=\"text\" name=\"addr_extension_spam\" value=\"".htmlspecialchars($mailbox["data"]["addr_extension_spam"])."\"></td>
    </tr><tr>
    	<td></td><td>". drawSubmitButton( _("Ok") ) ."</form></td></tr></table>";
	}
	else { $right=""; }
		
	// Output the form
	$out = "<table width=\"100%\" height=\"1\">
<tr>
	<td width=\"50%\" valign=\"top\">".$left."</td>
	<td width=\"4\" background=\"gfx/border_2.gif\"></td>
	<td valign=\"top\">".$right."</td>
</tr>
</table>
";
	return $out;
}

function drawAdminTools_emailPanel($mailbox){
	global $conf_skin;
	global $addrlink;

	global $adm_email_login;
	global $adm_email_pass;

	$user_menu[] = array(
		"text" => _("My e-mail") ,
		"icon" => "box_wnb_nb_picto-mailboxes.gif",
		"type" => "link",
		"link" => "my-email");
	$user_menu[] = array(
		"text" => _("Fetchmail") ,
		"icon" => "box_wnb_nb_picto-mailinglists.gif",
		"type" => "link",
		"link" => "fetchmail");

	$logout = "<a href=\"?action=logout\">". _("Logout") ."</a>";
	$mymenu = makeTreeMenu($user_menu,$addrlink,"?adm_email_login=$adm_email_login&adm_email_pass=$adm_email_pass","addrlink");

	switch($addrlink){
	case "my-email":
		$title = _("Mailbox configuration: ") ;
		$panel = drawAdminTools_emailAccount($mailbox);
		break;
	case "fetchmail":
		$title = _("Your list of imported mail") ;
		$panel = drawImportedMail($mailbox);
		break;
/*	case "antispam":
		$title = _("Protect your mailbox with efficient tools:") ;
		$panel = drawAntispamRules($mailbox);
		break;
	case "quarantine":
		$title = _("Those mail are in quarantine, and were not delivered to your pop account:") ;
		$panel = drawQuarantine($mailbox);
		break;*/
	default:
		$title = _("Welcome to the email panel!");
		$panel = _("Login successfull. Please select a menu entry on the left...");
		break;
	}

	if(function_exists("layoutEmailPanel")){
		$content = layoutEmailPanel($adm_email_login,"<br>".$mymenu."<center>$logout</center>",$title,$panel);
	}else{
		$mymenu_skin = skin($conf_skin,"<br>".$mymenu."<center>$logout</center>",$adm_email_login);
		$left = "<table width=\"1\" height=\"100%\"><tr>
		<td width=\"1\" height=\"1\">$mymenu_skin</td>
</tr><tr>
		<td height=\"100%\">&nbsp;</td>
</tr></table>";

		$right = skin($conf_skin,$panel,$title);

		$right = "<table width=\"100%\" height=\"100%\"><tr>
		<td width=\"100%\" height=\"100%\">$right</td>
</tr><tr>
	<td height=\"1\">&nbsp;</td>
</tr></table>";

		$content = "<table width=\"100%\" height=\"100%\"><tr>
		<td width=\"1\"  height=\"100%\">$left</td>
		<td width=\"100%\" height=\"100%\">$right</td>
</tr></table>";
	}
	return $content;
//	return drawAdminTools_emailAccount($mailbox);
}

/////////////////////////////////////////
// Check the used quota for cyrus      //
/////////////////////////////////////////
function getCyrusUsedQuota ($id) {
	global $pro_mysql_pop_table;

	$q = "SELECT fullemail FROM $pro_mysql_pop_table WHERE autoinc='$id';";
	$r = mysql_query($q)or die ("Cannot query $q line: ".__LINE__." file ".__FILE__." sql said:" .mysql_error());
	$n = mysql_num_rows($r);
	if($n != 1){
		 die("Cannot find created email line ".__LINE__." file ".__FILE__);
	}
	$a = mysql_fetch_array($r);
	$fullemail = $a["fullemail"];
	// login to cyradm
	$cyr_conn = new cyradm;
	$error=$cyr_conn -> imap_login();
	if ($error!=0){
		die ("imap_login Error $error");
	}
	// get the quota used
	$cyrus_quota=$cyr_conn->getquota("user/" . $fullemail);
	/*
	$max_quota=$cyrus_quota['qmax'];
	$quota_used=$cyrus_quota['used'];
	$percent=100*$quota_used/$max_quota;
	*/
	$value=$cyrus_quota['used'];
	$happen="/ ".$cyrus_quota['qmax']. " (" . round(100 * $cyrus_quota['used'] / $cyrus_quota['qmax'],2) . "%)";
	$cyrq = array(
		"value" => $value,
		"happen" => $happen);
	return $cyrq;
}

/////////////////////////////////////////
// One domain email collection edition //
/////////////////////////////////////////
function emailAccountsCreateCallback ($id){
	global $pro_mysql_pop_table;
	global $pro_mysql_list_table;
	global $conf_dtc_system_uid;
	global $conf_dtc_system_gid;
	global $adm_login;
	global $edit_domain;
	global $cyrus_used;
	global $pro_mysql_mailaliasgroup_table;
	global $CYRUS;

	$q = "SELECT * FROM $pro_mysql_pop_table WHERE autoinc='$id';";
	$r = mysql_query($q)or die ("Cannot query $q line: ".__LINE__." file ".__FILE__." sql said:" .mysql_error());
	$n = mysql_num_rows($r);
	if($n != 1){
		die("Cannot find created email line ".__LINE__." file ".__FILE__);
	}
	$a = mysql_fetch_array($r);

	$test_query = "SELECT * FROM $pro_mysql_list_table WHERE name='".$a["id"]."' AND domain='$edit_domain'";
	$test_result = mysql_query ($test_query)or die("Cannot execute query \"$test_query\" line ".__LINE__." file ".__FILE__. " sql said ".mysql_error());
	$testnum_rows = mysql_num_rows($test_result);
	if($testnum_rows >= 1){
		$q = "DELETE FROM $pro_mysql_pop_table WHERE autoinc='$id';";
		$r = mysql_query($q)or die ("Cannot query $q line: ".__LINE__." file ".__FILE__." sql said:" .mysql_error());
		return "<font color=\"red\">". _("Error: a mailing list already exists with this name!") ."</font>";
	}
	$test_query = "SELECT * FROM $pro_mysql_mailaliasgroup_table WHERE id='".$a["id"]."' AND domain_parent='$edit_domain'";
	$test_result = mysql_query ($test_query) or die("Cannot execute query \"$test_query\" line ".__LINE__." file ".__FILE__. " sql said ".mysql_error());
	$testnum_rows = mysql_num_rows($test_result);
	if($testnum_rows >= 1){
		$q = "DELETE FROM $pro_mysql_pop_table WHERE autoinc='$id';";
		$r = mysql_query($q) or die ("Cannot query $q line: ".__LINE__." file ".__FILE__." sql said:" .mysql_error());
		return "<font color=\"red\">". _("Error: Email group alias already exists with this name!") ."</font><br />";
	}
	$crypted_pass = crypt($a["passwd"], dtc_makesalt());
	if (!$cyrus_used){
		writeDotQmailFile($a["id"],$a["mbox_host"]);
	}
	$admin_path = getAdminPath($adm_login);
	$box_path = "$admin_path/$edit_domain/Mailboxs/".$a["id"];
	$q = "UPDATE $pro_mysql_pop_table SET crypt='$crypted_pass',home='$box_path',uid='$conf_dtc_system_uid',gid='$conf_dtc_system_gid',fullemail='".$a["id"].'@'.$a["mbox_host"]."',quota_couriermaildrop=CONCAT(1024000*quota_size,'S,',quota_files,'C') WHERE autoinc='$id';";
	$r2 = mysql_query($q)or die ("Cannot query $q line: ".__LINE__." file ".__FILE__." sql said:" .mysql_error());
	triggerMXListUpdate();
	if ($cyrus_used){
		# login to cyradm
		$cyr_conn = new cyradm;
		$error=$cyr_conn -> imap_login();
		if ($error!=0){
			die ("imap_login Error $error");
		}
		$result=$cyr_conn->createmb("user/" . $a["id"]."@".$edit_domain);
		$result=$cyr_conn->createmb("user/" . $a["id"]."/".$a["spam_mailbox"]."@".$edit_domain);
		$result = $cyr_conn->setacl("user/" . $a["id"]."@".$edit_domain, $CYRUS['ADMIN'], "lrswipcda");
		$result = $cyr_conn->setmbquota("user/" . $a["id"]."@".$edit_domain, $a["quota_size"]);
	}
	updateUsingCron("gen_qmail='yes', qmail_newu='yes'");
	return "";
}
function emailAccountsEditCallback ($id){
	global $cyrus_used;
	global $pro_mysql_pop_table;

	$q = "SELECT * FROM $pro_mysql_pop_table WHERE autoinc='$id';";
	$r = mysql_query($q)or die ("Cannot query $q line: ".__LINE__." file ".__FILE__." sql said:" .mysql_error());
	$n = mysql_num_rows($r);
	if($n != 1){
		die("Cannot find created email line ".__LINE__." file ".__FILE__);
	}
	$a = mysql_fetch_array($r);

	$crypted_pass = crypt($a["passwd"], dtc_makesalt());
	$q = "UPDATE $pro_mysql_pop_table SET crypt='$crypted_pass',quota_couriermaildrop=CONCAT(1024000*quota_size,'S,',quota_files,'C') WHERE autoinc='$id';";
	$r = mysql_query($q)or die ("Cannot query $q line: ".__LINE__." file ".__FILE__." sql said:" .mysql_error());

	if(!$cyrus_used){
		writeDotQmailFile($a["id"],$a["mbox_host"]);
	}
	updateUsingCron("gen_qmail='yes', qmail_newu='yes'");

	if ($cyrus_used){
		// login to cyradm
		$cyr_conn = new cyradm;
		$error=$cyr_conn -> imap_login();
		if ($error!=0){
			die ("imap_login Error $error");
		}
		if (!$a["quota_size"]){
                        die ("invalid quota");
                }
                $result = $cyr_conn->setmbquota("user/" . $a["fullemail"], $a["quota_size"]);
	}
	return "";
}

function emailAccountsDeleteCallback ($id){
	global $cyrus_used;
	global $pro_mysql_pop_table;
	global $pro_mysql_fetchmail_table;

	triggerMXListUpdate();
	updateUsingCron("gen_qmail='yes', qmail_newu='yes'");
	$q = "SELECT id, mbox_host, home FROM $pro_mysql_pop_table WHERE autoinc='$id';";
	$r = mysql_query($q)or die ("Cannot query $q line: ".__LINE__." file ".__FILE__." sql said:" .mysql_error());
	$n = mysql_num_rows($r);
	if($n != 1){
		die("Cannot find created email line ".__LINE__." file ".__FILE__);
	}
	$v = mysql_fetch_array($r);
	if ($cyrus_used){
		# login to cyradm
		$cyr_conn = new cyradm;
		$error=$cyr_conn -> imap_login();
		if ($error!=0){
			die ("imap_login Error $error");
		}
		$result=$cyr_conn->deletemb("user/" . $v["id"]."@".$v["mbox_host"]);
	}
	$cmd = "rm -rf " . $v["home"];
	exec($cmd,$exec_out,$return_val);
	$q = "DELETE FROM $pro_mysql_fetchmail_table WHERE domain_user='".$v["id"]."' AND domain_name='".$v["mbox_host"]."';";
	$r = mysql_query($q)or die ("Cannot query $q line: ".__LINE__." file ".__FILE__." sql said:" .mysql_error());
	updateUsingCron("qmail_newu='yes',restart_qmail='yes',gen_qmail='yes'");
	return "";
}
function drawAdminTools_Emails($domain){
	global $adm_login;
	global $adm_pass;
	global $edit_domain;
	global $edit_mailbox;
	global $addrlink;

	global $cyrus_used;
	global $cyrus_default_quota;
	global $CYRUS;

	global $conf_hide_password;
	global $pro_mysql_pop_table;
	global $conf_post_or_get;
	global $conf_addr_mail_server;

	checkLoginPassAndDomain($adm_login,$adm_pass,$domain["name"]);

	$out = "";
	$dsc = array(
		"title" => _("List of your mailboxes:") ,
		"new_item_title" => _("New mailbox") ,
		"new_item_link" => _("new mailbox") ,
		"edit_item_title" => _("Mailbox configuration:") ,
		"table_name" => $pro_mysql_pop_table,
		"action" => "pop_access_editor",
		"forward" => array("adm_login","adm_pass","addrlink"),
		"id_fld" => "autoinc",
		"list_fld_show" => "id",
		"max_item" => $domain["max_email"],
		"num_item_txt" => _("Number of active mailboxes:") ,
		"create_item_callback" => "emailAccountsCreateCallback",
		"delete_item_callback" => "emailAccountsDeleteCallback",
		"edit_item_callback" => "emailAccountsEditCallback",
		"where_list" => array(
			"mbox_host" => $domain["name"]),
		"check_unique" => array( "id" ),
		"check_unique_msg" => _("There is already a mailbox by that name") ,
		"order_by" => "id",
		"cols" => array(
			"autoinc" => array(
				"type" => "id",
				"display" => "no",
				"legend" => _("Login:") ),
                        "login_title" => array("type" => "title", "legend" => _("Mailbox") ),
			"id" => array(
				"type" => "text",
				"disable_edit" => "yes",
				"check" => "dtc_login",
				"happen" => "@".$domain["name"],
				"fixup" => 'strtolower',
				"placeholder" => "mailboxname",
				"legend" => _("Login:") ),
			"passwd" => array(
				"type" => "password",
				"check" => "dtc_pass",
				"legend" => _("Password:") ),
			"memo" => array (
				"type" => "text",
				"help" => _("This text is just a memo for yourself, and will not really be used."),
				"placeholder" => "John Doe",
				"legend" => _("Name:") ),
			)
		);

	if($cyrus_used) {
		$dsc["cols"]["quota_title"] = array( "type" => "title", "legend" => _("Quota limit") );
		$dsc["cols"]["quota_size"] = array(
			"type" => "text",
			"check" => "number",
			"default" => "$cyrus_default_quota",
			"legend" => _("Mailbox quota: ") );
		$dsc["cols"]["quota_used"] = array(
			"type" => "readonly",
			"hide_create" => "yes",
			"callback" => "getCyrusUsedQuota",
			"happen" => _("MBytes"),
			"legend" => _("Used quota: ") );
	} else {
		$dsc["cols"]["quota_title"] = array( "type" => "title", "legend" => _("Quota limit") );
		$dsc["cols"]["quota_size"] = array(
			"type" => "text",
			"check" => "max_value_2096",
			"default" => "10",
			"happen" => _("MBytes"),
			"help" => _("Setting BOTH the number of files and overall mailbox size to zero will disable quota."),
			"legend" => _("Mailbox quota: ") );
		$dsc["cols"]["quota_files"] = array(
			"type" => "text",
			"check" => "number",
			"default" => "1024",
			"happen" => _("files"),
			"legend" => _("Mailbox max files quota: ") );
                $dsc["cols"]["delivery_title"] = array( "type" => "title", "legend" => _("Delivery configuration") );
		$dsc["cols"]["localdeliver"] = array(
			"type" => "checkbox",
			"values" => array( "yes","no"),
			"legend" => _("Deliver messages locally in INBOX: ") );
		$dsc["cols"]["redirect1"] = array(
			"type" => "text",
			"check" => "email",
			"can_be_empty" => "yes",
			"empty_makes_sql_null" => "yes",
			"placeholder" => "mailbox@example.com",
			"legend" => _("Redirection 1: ") );
		$dsc["cols"]["redirect2"] = array(
			"type" => "text",
			"check" => "email",
			"can_be_empty" => "yes",
			"empty_makes_sql_null" => "yes",
			"placeholder" => "mailbox2@example.com",
			"legend" => _("Redirection 2: ") );
                $dsc["cols"]["vacation_title"] = array("type" => "title", "legend" => _("Vacation bounce message") );
		$dsc["cols"]["vacation_flag"] = array(
			"type" => "checkbox",
			"values" => array( "yes","no"),
			"default" => "no",
			"legend" => _("Check to send a bounce (vacation) message: ") );
		$dsc["cols"]["vacation_text"] = array(
			"type" => "textarea",
			"legend" => _("Bounce message content: ") ,
			"cols" => "40",
			"rows" => "7");
	}
	$dsc["cols"]["spam_title"] = array("type" => "title","legend" => _("SPAM control"));
	$dsc["cols"]["spam_mailbox_enable"] = array(
		"type" => "checkbox",
		"help" => _("If selected, spam will be saved in a SPAM folder and won't reach your inbox. Later you may check this folder with webmail or an IMAP client."),
		"values" => array( "yes","no"),
		"legend" => _("Enable SPAM filtering: ") );
	$dsc["cols"]["spam_mailbox"] = array(
		"type" => "text",
		"help" => _("Name of the SPAM folder (the above option has to be activated)."),
		"default" => "SPAM",
		"check" => "IMAPMailbox",
		"can_be_empty" => "yes",
		"legend" => _("SPAM mailbox destination: ") );
	$dsc["cols"]["virus_lover"] = array(
		"type" => "checkbox",
		"help" => _("If selected, virus infected messages will be delivered to your inbox"),
		"values" => array( "Y","N"),
		"legend" => _("Receive virus infected messages: ") );
	$dsc["cols"]["spam_lover"] = array(
		"type" => "checkbox",
		"help" => _("If selected, spam  messages will be delivered to your inbox even if they where tagged above the kill level, which normally would discart them. Keep in mind, that you still can move them to a separate folder by activating that option."),
		"values" => array( "Y","N"),
		"legend" => _("Receive spam messages: ") );
	$dsc["cols"]["banned_files_lover"] = array(
		"type" => "checkbox",
		"help" => _("If selected, messages containing banned_files will be delivered to your inbox."),
		"values" => array( "Y","N"),
		"legend" => _("Receive messages containing banned_files: ") );
	$dsc["cols"]["bad_header_lover"] = array(
		"type" => "checkbox",
		"help" => _("If selected, messages containing bad_headers will be delivered to your inbox."),
		"values" => array( "Y","N"),
		"legend" => _("Receive messages containing bad_headers: ") );
	$dsc["cols"]["spam_tag_level"] = array(
		"type" => "text",
		"help" => _("Tag mails above this level as possible spam. (No further actions taken - you can define a subject tag that should be added to the messages though.) Typical values are -999.99 - 4.00"),
		"default" => "-999.99",
		"check" => "numeric",
		"can_be_empty" => "yes",
		"legend" => _("Spam tag level: ") );
	$dsc["cols"]["spam_tag2_level"] = array(
		"type" => "text",
		"help" => _("Tag mails above this level as spam. (defined spam actions apply. e.g. Mails will be tagged as spam or be forwarded... You can also define a subject tag that should be added to the messages.) Typical values are 4.01 - 5.99"),
		"default" => "4.3",
		"check" => "numeric",
		"can_be_empty" => "yes",
		"legend" => _("Spam tag2 level: ") );
	$dsc["cols"]["spam_tag3_level"] = array(
		"type" => "text",
		"help" => _("Tag mails above this level as extreme spam. (The same actions apply as the ones for tag2 level mails. You can define a separate subject tag that should be added to the messages.) !Only works with Amavis-New above 2.7 otherwise this level and subject are ignored! Typical values are 6.00 - 10.00"),
		"default" => "5.00",
		"check" => "numeric",
		"can_be_empty" => "yes",
		"legend" => _("Spam tag3 level: ") );
	$dsc["cols"]["spam_kill_level"] = array(
		"type" => "text",
		"help" => _("discart or bounce mails above this level. (If you did not check the option to receive spam mails, these mails will be deleted or bounced following your dsn-cutoff-level.) Typical values are 6.00 - 12.00"),
		"default" => "6.00",
		"check" => "numeric",
		"can_be_empty" => "yes",
		"placeholder" => "9",
		"legend" => _("Spam kill level: ") );
	$dsc["cols"]["spam_dsn_cutoff_level"] = array(
		"type" => "text",
		"help" => _("Up to this level bounce messages will be send. If you do not want to inform spammers that  their mails where tagged as spam, set this level to the kill level."),
		"default" => "6.0",
		"check" => "numeric",
		"can_be_empty" => "yes",
		"legend" => _("DSN cutoff level: ") );
	$dsc["cols"]["quarantine_cutoff_level"] = array(
		"type" => "text",
		"help" => _("Up to this level mails will be put into quarantine due to your settings. Typical values are 15.00 - 20.00"),
		"default" => "20.00",
		"check" => "numeric",
		"can_be_empty" => "yes",
		"legend" => _("Quarantine cutoff level: ") );
	$dsc["cols"]["spam_subject_tag"] = array(
		"type" => "text",
		"help" => _("Add this tag to the subject of mails above tag level."),
		"default" => "",
		"check" => "ExtendedPassword",
		"can_be_empty" => "yes",
		"placeholder" => "[possible spam] ",
		"legend" => _("Spam subject tag: ") );
	$dsc["cols"]["spam_subject_tag2"] = array(
		"type" => "text",
		"help" => _("Add this tag to the subject of mails above tag2 level."),
		"default" => "",
		"check" => "ExtendedPassword",
		"can_be_empty" => "yes",
		"placeholder" => "***spam*** ",
		"legend" => _("Spam subject tag2: ") );
	$dsc["cols"]["spam_subject_tag3"] = array(
		"type" => "text",
		"help" => _("Add this tag to the subject of mails above tag3 level. (Only works with Amavis-New above 2.7)"),
		"default" => "",
		"check" => "ExtendedPassword",
		"can_be_empty" => "yes",
		"placeholder" => "[extreme spam] ",
		"legend" => _("Spam subject tag3: ") );
	$dsc["cols"]["newvirus_admin"] = array(
		"type" => "text",
		"help" => _("Email address of an admin to inform about new viruses."),
		"default" => "",
		"check" => "email",
		"can_be_empty" => "yes",
		"placeholder" => _("mailbox@example.com"),
		"legend" => _("New virus admin email: ") );
	$dsc["cols"]["virus_admin"] = array(
		"type" => "text",
		"help" => _("Email address of an admin to inform about viruses."),
		"default" => "",
		"check" => "email",
		"can_be_empty" => "yes",
		"placeholder" => _("mailbox@example.com"),
		"legend" => _("Virus admin email: ") );
	$dsc["cols"]["spam_admin"] = array(
		"type" => "text",
		"help" => _("Email address of an admin to inform about spam."),
		"default" => "",
		"check" => "email",
		"can_be_empty" => "yes",
		"placeholder" => _("mailbox@example.com"),
		"legend" => _("Spam admin email: ") );
	$dsc["cols"]["banned_admin"] = array(
		"type" => "text",
		"help" => _("Email address of an admin to inform about mails containing banned_files."),
		"default" => "",
		"check" => "email",
		"can_be_empty" => "yes",
		"placeholder" => _("mailbox@example.com"),
		"legend" => _("Banned_files admin email: ") );
	$dsc["cols"]["bad_header_admin"] = array(
		"type" => "text",
		"help" => _("Email address of an admin to inform about mails containing bad_headers."),
		"default" => "",
		"check" => "email",
		"can_be_empty" => "yes",
		"placeholder" => _("mailbox@example.com"),
		"legend" => _("Bad header admin email: ") );
	$dsc["cols"]["virus_quarantine_to"] = array(
		"type" => "text",
		"help" => _("Quarantine virus mails to this email address."),
		"default" => "",
		"check" => "email",
		"can_be_empty" => "yes",
		"placeholder" => _("mailbox@example.com"),
		"legend" => _("Quarantine viruses to email: ") );
	$dsc["cols"]["spam_quarantine_to"] = array(
		"type" => "text",
		"help" => _("Quarantine spam mails to this email address."),
		"default" => "",
		"check" => "email",
		"can_be_empty" => "yes",
		"placeholder" => _("mailbox@example.com"),
		"legend" => _("Quarantine spam to email: ") );
	$dsc["cols"]["banned_quarantine_to"] = array(
		"type" => "text",
		"help" => _("Quarantine mails containing banned_files to this email address."),
		"default" => "",
		"check" => "email",
		"can_be_empty" => "yes",
		"placeholder" => _("mailbox@example.com"),
		"legend" => _("Quarantine banned_files to email: ") );
	$dsc["cols"]["bad_header_quarantine_to"] = array(
		"type" => "text",
		"help" => _("Quarantine mails containing bad_headers to this email address."),
		"default" => "",
		"check" => "email",
		"can_be_empty" => "yes",
		"placeholder" => _("mailbox@example.com"),
		"legend" => _("Quarantine bad_headers to email: ") );
	$dsc["cols"]["message_size_limit"] = array(
		"type" => "text",
		"help" => _("Only process filters on mails up to this size."),
		"default" => "256000",
		"check" => "number",
		"can_be_empty" => "yes",
		"happen" => _("Bytes"),
		"legend" => _("Message size limit for filters: ") );
	$dsc["cols"]["addr_extension_virus"] = array(
		"type" => "text",
		"help" => _("Add this extension to the receivers email address of virus mails."),
		"default" => "",
		"check" => "IMAPMailbox",
		"can_be_empty" => "yes",
		"placeholder" => "virus",
		"legend" => _("Virus address extension: ") );
	$dsc["cols"]["addr_extension_spam"] = array(
		"type" => "text",
		"help" => _("Add this extension to the receivers email address of spam mails."),
		"default" => "",
		"check" => "IMAPMailbox",
		"can_be_empty" => "yes",
		"placeholder" => "spam",
		"legend" => _("Spam address extension: ") );
	$dsc["cols"]["addr_extension_banned"] = array(
		"type" => "text",
		"help" => _("Add this extension to the receivers email address of mails containing banned_files."),
		"default" => "",
		"check" => "IMAPMailbox",
		"can_be_empty" => "yes",
		"placeholder" => "banned-files",
		"legend" => _("Banned_files address extension: ") );
	$dsc["cols"]["addr_extension_bad_header"] = array(
		"type" => "text",
		"help" => _("Add this extension to the receivers email address of mails containing bad_headers."),
		"default" => "",
		"check" => "IMAPMailbox",
		"can_be_empty" => "yes",
		"placeholder" => "bad-header",
		"legend" => _("Bad_headers address extension: ") );
        $list_items = dtcListItemsEdit($dsc);

        // We have to query again, in case something has changed
        $q = "SELECT id FROM $pro_mysql_pop_table WHERE mbox_host='".$domain["name"]."';";
        $r = mysql_query($q)or die ("Cannot query $q line: ".__LINE__." file ".__FILE__." sql said:" .mysql_error());
        $n = mysql_num_rows($r);
	$catch_popup = "<option value=\"no-mail-account\">". _("No catch-all") ."</option>";
        for($i=0;$i<$n;$i++){
        	$a = mysql_fetch_array($r);
        	if($a["id"] == $domain["catchall_email"]){
        		$selected = " selected ";
		}else{
			$selected = " ";
		}
		$catch_popup .= "<option value=\"".$a["id"]."\" $selected>".$a["id"]."</option>";
        }
	if ( $domain["primary_mx"] == $conf_addr_mail_server ) {
		$out .= "<font color=\"#FF0000\"><br><br><b>". _("WARNING! You are in SMTP relay mode, the mails received will not be stored in these mailboxes. Use this screen only to set anti-SPAM and anti-VIRUS preferences.") ."</b><br><br></font>";
	}
	$out .= "<b><u>". _("Catch-all email set to deliver to") .":</u></b><br>";
	$out .= "<form method=\"$conf_post_or_get\" action=\"?\" method=\"post\">
	<input type=\"hidden\" name=\"adm_login\" value=\"$adm_login\">
	<input type=\"hidden\" name=\"adm_pass\" value=\"$adm_pass\">
	<input type=\"hidden\" name=\"addrlink\" value=\"$addrlink\">
	<input type=\"hidden\" name=\"edit_domain\" value=\"$edit_domain\">
	Catchall: <input type=\"hidden\" name=\"action\" value=\"set_catchall_account\">
	<select name=\"catchall_popup\">$catch_popup</select><input type=\"image\" src=\"gfx/stock_apply_20.png\">
</form>";

	$out .= $list_items;
	$out .= helpLink("PmWiki/Email-Accounts");
	return $out;
}

?>
