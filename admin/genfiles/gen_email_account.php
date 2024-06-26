<?php

require("genfiles/remote_mail_list.php");
require("genfiles/gen_qmail_email_account.php");
require("genfiles/gen_postfix_email_account.php");
require("genfiles/gen_maildrop_userdb.php");

function genDotMailfilterFile($home,$id,$domain_full_name,$spam_mailbox_enable,$spam_mailbox,$localdeliver,$vacation_flag="no",$vacation_text="",$redirection="",$redirection2=""){
	global $conf_dtc_system_username;
	global $conf_dtc_system_groupname;
	$MAILFILTER_FILE="$home/.mailfilter";

	if($id == "cyrus" || $id == "cyradm"){
		return true;
	}

	// Check if the maildir exists, create if not
	if(! is_dir("$home/Maildir")){
		if(! is_dir($home)){
			mkdir($home,0777,true);
		}
		$PATH = getenv('PATH');
		putenv("PATH=/usr/lib/courier-imap/bin:$PATH");
		system("maildirmake $home/Maildir");
		putenv("PATH=$PATH");
	}

	// Setup the anti-loop system
	$mlfilter_content = <<<MAILFILTER_EOF
# This file is automaticaly generated by DTC
# do not edit by hand, it will be overwritten each
# time the cron job generates the mail accounts

PATH=/usr/lib/courier-imap/bin:\$PATH

DEFAULT="\$HOME/Maildir"

if (/^X-DTC-LoopDetected:\s*(.*)/:h)
{
        exit
}

if (/^X-DTC-Counter:\s*(.*)/:h)
{
        ZERO=\$MATCH
        DTCCOUNTER=length(\$ZERO) - 15

        if (\$DTCCOUNTER > 6)
        {
                # SUBJECT=xfilter "reformail -x Subject:"
                # xfilter "reformail -I \"Subject: [DTC Email Loop] \$SUBJECT\""
                xfilter "reformail -I 'X-DTC-LoopDetected: X'"
        }
        else
        {
                DTCHEADERVALUE="\$ZERO"'X'
                xfilter "reformail -I \"\$DTCHEADERVALUE\""
                xfilter "reformail -I \"X-DTC-Counter-Value: \$DTCCOUNTER\""
        }
}
else
{
        xfilter "reformail -I 'X-DTC-Counter: X'"
}

MAILFILTER_EOF;
	// Manage the redirections
	if($redirection2 == ""){
		if($redirection != ""){
			$mlfilter_content .= "cc \"! $redirection\" \n";
		}
	}else{
		if($redirection != "" && $redirection2 != ""){
			$mlfilter_content .= "cc \"! $redirection\" \ncc \"! $redirection2\" \n";
		}
	}

	if($spam_mailbox_enable == "yes"){
		$mlfilter_content .= <<<MAILFILTER_EOF
if (/^X-Spam-Flag: .*YES.*/)
{
	`[ -d \$DEFAULT ] || maildirmake \$DEFAULT && [ -d "\$DEFAULT/.$spam_mailbox" ] || maildirmake -f "$spam_mailbox" \$DEFAULT`
	exception {
		to "\$DEFAULT/.$spam_mailbox/"
	}
}

MAILFILTER_EOF;
	}

	// Manage the silly sqwebmail stuff for Damien
	if(! file_exists("$home/Maildir/maildirfilterconfig")){
		$fp = fopen("$home/Maildir/maildirfilterconfig","w+");
		if($fp != FALSE){
			fwrite($fp,"MAILDIRFILTER=../.mailfilter.sqwebmail
MAILDIR=\$DEFAULT\n");
			fclose($fp);
		}
		@chmod("$home/Maildir/maildirfilterconfig",0550);
		@chown("$home/Maildir/maildirfilterconfig",$conf_dtc_system_username);
	}
	if(file_exists("$home/.mailfilter.sqwebmail")){
		$mlfilter_content .= "include \".mailfilter.sqwebmail\"\n";
	}

	$mlfilter_content .= "# If you want to customize this file and include custom directives
# in HERE, then edit a file called .mailfilter.custom and it will be included below this line\n";
	if( file_exists("$home/.mailfilter.custom")){
		$mlfilter_content .= "include \".mailfilter.custom\"\n";
	}

	if($vacation_flag == "yes"){
		$mlfilter_content_filename = "genfiles/mailfilter_vacation_template";
		$mlfilter_content_handle = fopen($mlfilter_content_filename, "r");
		$mlfilter_content .= fread($mlfilter_content_handle, filesize($mlfilter_content_filename));
		fclose($mlfilter_content_handle);
		// The following commented thing is replaced by the above that get rid of the double \n at end of lines
		//$mlfilter_content .= "\n".implode("\n",file("genfiles/mailfilter_vacation_template"))."\n";
		@chmod("$home/.vacation.msg",0660);
		$vac_fp = fopen("$home/.vacation.msg","w+");
		if($vac_fp != FALSE){
			fwrite($vac_fp,$vacation_text);
			fclose($vac_fp);
		}
		@chmod("$home/.vacation.msg",0550);
		@chown("$home/.vacation.msg",$conf_dtc_system_username);
	}
	if ($localdeliver == "no")
	{
		$mlfilter_content .= <<<MAILFILTER_EOF
# Exit here since we don't want to deliver locally
exit

MAILFILTER_EOF;
	}else{
		$mlfilter_content .= <<<MAILFILTER_EOF
`[ -d \$DEFAULT ] || maildirmake \$DEFAULT`
to \$DEFAULT

MAILFILTER_EOF;
	}

	// Write the file and manage rights
	@chmod($MAILFILTER_FILE,0660);
	$fp = fopen($MAILFILTER_FILE,"w+");
	if($fp != FALSE){
		fwrite($fp, $mlfilter_content);
		fclose($fp);
	}
	@chmod($MAILFILTER_FILE,0500);
	@chown($MAILFILTER_FILE,$conf_dtc_system_username);
	// This shouldn't be needed as we set 500 in the chmod anyway
	// chgrp($MAILFILTER_FILE,$conf_dtc_system_groupname);
	return true;
}

function genSasl2PasswdDBStart(){
	// Note that this function is REALLY problematic as it will keep SASL accounts forever:
	// how can we delete an account from SASL? Currently DTC simply don't do it...
	global $conf_dtc_system_username;
	global $conf_dtc_system_groupname;
	global $conf_generated_file_path;

	if(is_dir("/var/spool/postfix/etc")){
		$fpath = "/var/spool/postfix/etc/sasldb2";
	}else{
		if(is_dir("/etc/sasl2")){
			$fpath = "/etc/sasl2/sasldb2";
		}else{
			if(is_dir("/usr/local/etc") && !file_exists("/etc/sasldb2")){
				$fpath = "/usr/local/etc/sasldb2";
			}else{
				$fpath = "/etc/sasldb2";
			}
		}
	}
	system("cat $fpath > $conf_generated_file_path/sasldb2");
	@chmod("$conf_generated_file_path/sasldb2",0664);
	@chown("$conf_generated_file_path/sasldb2","postfix");
	@chgrp("$conf_generated_file_path/sasldb2",$conf_dtc_system_groupname);
}

function genDotSieveFile($home,$id,$domain_full_name,$spam_mailbox_enable,$spam_mailbox,$localdeliver,$vacation_flag="no",$vacation_text="",$redirection="",$redirection2=""){
	global $conf_dtc_system_username;
	global $conf_dtc_system_groupname;
	$SIEVE_SYM_LINK="$home/.dovecot.sieve";
	$SIEVE_FILE="$home/sieve/dtc.sieve";
	$SIEVE_CUSTOM_FILE="$home/sieve/custom.sieve";
	$recipient="$id@$domain_full_name";

	// Check if the maildir exists, create if not
	if(! is_dir("$home/Maildir")){
		if(! is_dir($home)){
			mkdir($home,0777,true);
		}
		$PATH = getenv('PATH');
		putenv("PATH=/usr/lib/courier-imap/bin:$PATH");
		system("maildirmake $home/Maildir");
		putenv("PATH=$PATH");
	}

	// Setup the anti-loop system
	$sieve_filter_content = <<<MAILFILTER_EOF
# This file is automaticaly generated by DTC
# do not edit by hand, it will be overwritten each
# time the cron job generates the mail accounts

require ["fileinto", "include", "variables", "vacation", "envelope", "imap4flags", "subaddress", "copy", "mailbox"];
include "custom";

MAILFILTER_EOF;
	// Manage the redirections
	if($redirection2 == ""){
		if($redirection != ""){
			$sieve_filter_content .= "redirect :copy \"$redirection\"; \n";
		}
	}else{
		if($redirection != "" && $redirection2 != ""){
			$sieve_filter_content .= "redirect :copy \"$redirection\"; \nredirect :copy \"$redirection2\"; \n";
		}
	}

	if($spam_mailbox_enable == "yes"){
		$spambox = str_replace("\\", "\\\\", $spam_mailbox);
		$spambox = str_replace('"', "\\\"", $spambox);
		$sieve_filter_content .= <<<MAILFILTER_EOF
		if header :contains "X-Spam-Flag" "YES" {
			fileinto :create "$spambox";
		}

MAILFILTER_EOF;
	}

	if($vacation_flag == "yes"){
		$vacation_text = str_replace( "\n.", "\n..", $vacation_text );
		$sieve_filter_content .= <<<MAILFILTER_EOF

if header :matches "Subject" "*" {
        set "oldsub" ": \${1}";
}
if allof (
not header :contains "Precedence" ["bulk","list","junk"],
not header :contains "List-Id" ["YES"],
not header :contains "List-Unsubscribe" ["YES"],
not header :contains "Return-path" "*<#@\[\]>",
not header :contains "Return-path" "*<>",
not header :contains "From" ["*MAILER-DAEMON"],
not header :contains "Content-Type" "delivery-status",
not header :contains "Subject" "*Delivery Status Notification",
not header :contains "Subject" "*Undelivered Mail Returned to Sender",
not header :contains "Subject" "*Delivery failure",
not header :contains "Subject" "*Mail Delivery Subsystem",
not header :contains "Subject" "*Mail System Error.*Returned Mail",
not header :contains "X-AutoReply" "*",
not header :contains "X-Mail-Autoreply" "*",
not allof (
        exists "Auto-Submitted",
        not header :matches "Auto-Submitted" "no"
	),
not header :contains "X-DTC-Support-Ticket" "*",
not header :contains "X-ClamAV-Notice-Flag" ["YES"],
not header :contains "X-Spam-Flag" ["YES"] ) {
vacation :days 2 :addresses ["$recipient"] :subject "Auto Response from $recipient\${oldsub}" 
text:
$vacation_text
.
;}

MAILFILTER_EOF;
	}

	if ($localdeliver == "no"){
		$sieve_filter_content .= <<<MAILFILTER_EOF
# Exit here since we don't want to deliver locally
stop;

MAILFILTER_EOF;
	}

$sieve_custom_content="# Custom sieve rules

";

	// Write the file and manage rights
	@chmod($SIEVE_FILE,0650);
	@chmod($SIEVE_CUSTOM_FILE,0660);
	if (! is_dir("$home/sieve")) {
		mkdir("$home/sieve", 0755);
		@chown("$home/sieve",$conf_dtc_system_username);
		if (! file_exists($SIEVE_CUSTOM_FILE)) {
			$fp_custom = fopen($SIEVE_CUSTOM_FILE,"w+");
			fwrite($fp_custom, $sieve_custom_content);
			fclose($fp_custom);
		}
	}
	$fp = fopen($SIEVE_FILE,"w+");
	if($fp != FALSE){
		fwrite($fp, $sieve_filter_content);
		fclose($fp);
	}
	@chmod($SIEVE_FILE,0650);
	@chown($SIEVE_FILE,$conf_dtc_system_username);
	@chgrp($SIEVE_FILE,$conf_dtc_system_groupname);
	@chmod($SIEVE_CUSTOM_FILE,0750);
	@chown($SIEVE_CUSTOM_FILE,$conf_dtc_system_username);
	@chgrp($SIEVE_CUSTOM_FILE,$conf_dtc_system_groupname);	
	// if a normal file exists here, delete it, and use the symlink
	if(file_exists($SIEVE_SYM_LINK) && !is_link($SIEVE_SYM_LINK)){
		unlink($SIEVE_SYM_LINK);
	}
	// point symlink to dtc.sieve if the symlink doesn't exist
	if (! file_exists($SIEVE_SYM_LINK)){
		symlink($SIEVE_FILE, $SIEVE_SYM_LINK);
	} 
	@chown($SIEVE_SYM_LINK,$conf_dtc_system_username);
	@chgrp($SIEVE_SYM_LINK,$conf_dtc_system_groupname);	

	return true;
}

// This is here so we don't have to do that at each function call
//$genSaslDatabaseEntry_SASLPWD2 = "";
//if(file_exists("/usr/sbin/saslpasswd2")){
//	$genSaslDatabaseEntry_SASLPWD2 = "/usr/sbin/saslpasswd2";
//}else{
//	if(file_exists("/usr/local/sbin/saslpasswd2")){
//		$genSaslDatabaseEntry_SASLPWD2 = "/usr/local/sbin/saslpasswd2";
//	}
//}

function genSasl2PasswdDBEntry($domain_full_name,$id,$passwdtemp,$mailname){
	global $genSaslDatabaseEntry_SASLPWD2;
	global $conf_generated_file_path;

	if($genSaslDatabaseEntry_SASLPWD2 == ""){
		return false;
	}
	if(isset($passwdtemp) && $passwdtemp != "" && $passwdtemp != NULL){
		system("echo $passwdtemp | $genSaslDatabaseEntry_SASLPWD2 -c -p -f $conf_generated_file_path/sasldb2 -u $mailname $id\@$domain_full_name");
	}
	return true;
}

function genSaslFinishConfigAndRights(){
	global $conf_dtc_system_username;
	global $conf_dtc_system_groupname;
	global $conf_generated_file_path;

	@chmod("$conf_generated_file_path/sasldb2",0664);
	if(is_dir("/var/spool/postfix/etc")){
		$fpath = "/var/spool/postfix/etc/sasldb2";
	}else{
		if(is_dir("/etc/sasl2")){
			$fpath = "/etc/sasl2/sasldb2";
		}else{
			if(is_dir("/usr/local/etc") && !file_exists("/etc/sasldb2")){
				$fpath = "/usr/local/etc/sasldb2";
			}else{
				$fpath = "/etc/sasldb2";
			}
		}
	}
	system("cat $conf_generated_file_path/sasldb2 > $fpath");
	@chmod($fpath,0664);
	@chown($fpath,"postfix");
	@chgrp($fpath,$conf_dtc_system_groupname);
}

function mail_account_generate(){
	global $conf_mta_type;
	global $conf_use_cyrus;

	switch($conf_mta_type){
	case "postfix":
		mail_account_generate_postfix();
		break;
	default:
	case "qmail":
		mail_account_generate_qmail();
		break;
	}

	// always generate maildrop
	// this will allow qmail to use maildrop along with postfix
	if($conf_use_cyrus != "yes"){
		mail_account_generate_maildrop();
	}
}

?>
