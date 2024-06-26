<?php

function fetchmail_generate() {
	global $conf_generated_file_path;
	global $console;
	global $mysqli_connection;

	$filename=$conf_generated_file_path.'/fetchmailrc';
	$console.="Generating $filename : ";
	if (touch($filename)) {
		$console.="Done!\n";
	}else{
		$console.="Failed!";
		return false;
	}

	$result=mysqli_query($mysqli_connection,"SELECT * FROM fetchmail");
	$num=0;    
	$fetchline="";
	while ($row=mysqli_fetch_assoc($result)) {
		if ($row['checkit'] != "yes") continue;
		/* Yes, only pop3 yet. Must specify it, auto is *sloooow* */
		$fetchline.="poll ${row['pop3_server']} proto POP3\n";
		$fetchline.="qvirtual \"MFY\"\n";
		$fetchline.="envelope \"Delivered-To\"\n";
		/* Unfortunately there is no such option in fetchmail to keep mails for X days, or something, so it must be done with
		other tools. I think its safer to keep messages on remote server by default */
		$fetchline.="user ${row['pop3_login']} with password ${row['pop3_pass']} to ${row['domain_user']}@${row['domain_name']}\n";
		$fetchline.="keep\n";

		$num++;
	}
	file_put_contents($filename,$fetchline,LOCK_EX);
	chmod($filename,0600);
	$console.="Number of fetchmailrc entries generated: ".$num."\n";
	updateUsingCron("gen_fetchmail='no'");
}
?>
