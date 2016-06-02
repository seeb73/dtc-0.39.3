<?php

////////////////////////////////////////////////////
// One domain name ftp account collection edition //
////////////////////////////////////////////////////
$mlmmj_back_color=0;
function mlmmj_color(){
	global $mlmmj_back_color;
	if($mlmmj_back_color == 1){
		$mlmmj_back_color = 0;
	}else{
		$mlmmj_back_color = 1;
	}
	return $mlmmj_back_color;
}

function drawAdminTools_MailingLists($domain){
	global $adm_login;
	global $adm_pass;
	global $edit_domain;
	global $edit_mailbox;
	global $addrlink;
	global $conf_post_or_get;

	$txt = "";
	if (isset($domain["mailinglists"])){
		$nbr_email = sizeof($domain["mailinglists"]);
	}else{
		$nbr_email = 0;
        }
	$max_email = $domain["max_lists"];
	if($nbr_email >= $max_email){
		$max_color = "color=\"#440000\"";
	}
	$nbrtxt = _("Number of active mailing lists");
	$txt .= "<font size=\"-2\">$nbrtxt</font> <font size=\"-1\">". $nbr_email ."</font> / <font size=\"-1\">" . $max_email . "</font><br><br>";

	$txt .= "<font face=\"Arial, Verdana\"><font size=\"-1\"><h3>". _("List of your mailing lists") ."</h3>";
	if (isset($domain["mailinglists"])){
		$lists = $domain["mailinglists"];
	}
	$nbr_boites = 0;
	if (isset($lists)){
		$nbr_boites = sizeof($lists);
	}
	for($i=0;$i<$nbr_boites;$i++){
		$list = $lists[$i];
		$id = $list["id"];
		$list_name = $list["name"];
		$list_owner = $list["owner"];
		if($i != 0){
			$txt .= " - ";
		}
		if(isset($_REQUEST["edit_mailbox"]) && $_REQUEST["edit_mailbox"] == $list_name){
			$txt .= "$list_name";
		}else{
			$txt .= "<a href=\"?adm_login=$adm_login&adm_pass=$adm_pass&addrlink=$addrlink&edit_domain=$edit_domain&whatdoiedit=mails&edit_mailbox=$list_name&list_owner=$list_owner\">$list_name</a>";
		}
	}

	if(isset($_REQUEST["edit_mailbox"]) && $_REQUEST["edit_mailbox"] != ""){
		$txt .= "<br><br><a href=\"?adm_login=$adm_login&adm_pass=$adm_pass&addrlink=$addrlink&edit_domain=$edit_domain&whatdoiedit=mails\">". _("new mailing list") ."</a> ";
		$txt .= "<br><br><h3>". _("Edit mailing list") ."</h3><br><br>";

		$list_name = $_REQUEST["edit_mailbox"];
		if (isset($_REQUEST["list_owner"])){
			$list_owner = $_REQUEST["list_owner"];
		} else if (isset($_REQUEST["editmail_owner"])){
			$list_owner = $_REQUEST["editmail_owner"];
		}

		$admin_path = getAdminPath($adm_login);
		$list_path = $admin_path."/".$edit_domain."/lists/".$edit_domain."_".$_REQUEST["edit_mailbox"];

// Description of the mailing list form
$dsc = array(
	"forward" => array("adm_login" => $adm_login,"adm_pass" => $adm_pass,"addrlink" => $addrlink,"whatdoiedit" => "mails","edit_mailbox" => $_REQUEST["edit_mailbox"],"modifylistdata" => "Ok"),
	"has_delete" => "yes",
	"titles" => array(
		_("List name and owner") => array(
			"form_lines" => array(
				_("List name:") => array(
					"widget" => "<b>$list_name</b>@".$edit_domain,
					"help" => _("Name of the list.")
				)
				,
				_("List owner") => array(
					"widget" => "<input type=\"text\" name=\"editmail_owner\" value=\"$list_owner\">",
					"help" => _("This is the main owner of the list.")
				)
			)
		),
		_("Header") => array(
			"form_lines" => array(
				_("Subject prefix:") => array(
					"widget" => getListOptionsValue($list_path,"prefix"),
					"help" => getTunableHelp("prefix")
				),
				_("Delete headers:") => array(
					"widget" => getListOptionsList($list_path,"delheaders"),
					"help" => getTunableHelp("delheaders")
				),
				_("Add To: header:") => array(
					"widget" => getListOptionsBoolean($list_path,"addtohdr"),
					"help" => getTunableHelp("addtohdr")
				),
				_("To: or Cc: not mandatory:") => array(
					"widget" => getListOptionsBoolean($list_path,"tocc"),
					"help" => getTunableHelp("tocc")
				),
				_("Custom headers:") => array(
					"widget" => getListOptionsTextarea($list_path,"customheaders"),
					"help" => getTunableHelp("customheaders")
				),
				_("Added footer:") => array(
					"widget" => getListOptionsTextarea($list_path,"footer"),
					"help" => getTunableHelp("footer")
				)
			)
		),
		_("Rights") => array(
			"default_close" => "yes",
			"form_lines" => array(
				_("Subscribers only post:") => array(
					"widget" => getListOptionsBoolean($list_path,"subonlypost"),
					"help" => getTunableHelp("subonlypost")
				),
				_("Closed list:") => array(
					"widget" => getListOptionsBoolean($list_path,"closedlist"),
					"help" => getTunableHelp("closedlist")
				),
				_("Owner:") => array(
					"widget" => getListOptionsList($list_path,"owner"),
					"help" => getTunableHelp("owner")
				)
			)
		),
		_("List moderation") => array(
			"default_close" => "yes",
			"form_lines" => array(
				_("Subscribers only post:") => array(
					"widget" => getListOptionsBoolean($list_path,"moderated"),
					"help" => getTunableHelp("moderated")
				),
				_("Moderators:") => array(
					"widget" => getListOptionsList($list_path,"moderators"),
					"help" => getTunableHelp("moderators")
				),
				_("No subscribtion confirmation:") => array(
					"widget" => getListOptionsBoolean($list_path,"nosubconfirm"),
					"help" => getTunableHelp("nosubconfirm")
				)
			)
		),
		_("Digest") => array(
			"default_close" => "yes",
			"form_lines" => array(
				_("Digest interval:") => array(
					"widget" => getListOptionsValue($list_path,"digestinterval"),
					"help" => getTunableHelp("digestinterval")
				),
				_("Digest max mails:") => array(
					"widget" => getListOptionsValue($list_path,"digestmaxmails"),
					"help" => getTunableHelp("digestmaxmails")
				)
			)
		),
		_("Notifications") => array(
			"default_close" => "yes",
			"form_lines" => array(
				_("Notify new subscribtions:") => array(
					"widget" => getListOptionsBoolean($list_path,"notifysub"),
					"help" => getTunableHelp("notifysub")
				),
				_("Notify when post and not subscribed:") => array(
					"widget" => getListOptionsBoolean($list_path,"nosubonlydenymails"),
					"help" => getTunableHelp("nosubonlydenymails")
				),
				_("Deny if no To: or Cc::") => array(
					"widget" => getListOptionsBoolean($list_path,"notoccdenymails"),
					"help" => getTunableHelp("notoccdenymails")
				),
				_("Notify when post and no access:") => array(
					"widget" => getListOptionsBoolean($list_path,"noaccessdenymails"),
					"help" => getTunableHelp("noaccessdenymails")
				)
			)
		),
		_("SMTP configuration") => array(
			"default_close" => "yes",
			"form_lines" => array(
				_("Max mail memory size:") => array(
					"widget" => getListOptionsValue($list_path,"memorymailsize"),
					"help" => getTunableHelp("memorymailsize")
				),
				_("SMTP relay server:") => array(
					"widget" => getListOptionsValue($list_path,"relayhost"),
					"help" => getTunableHelp("relayhost")
				),
				_("VERP:") => array(
					"widget" => getListOptionsValue($list_path,"verp"),
					"help" => getTunableHelp("verp")
				),
				_("Max VERP recipients:") => array(
					"widget" => getListOptionsValue($list_path,"maxverprecips"),
					"help" => getTunableHelp("maxverprecips")
				),
				_("Delimiter:") => array(
					"widget" => getListOptionsValue($list_path,"delimiter"),
					"help" => getTunableHelp("delimiter")
				),
				_("Bounce life:") => array(
					"widget" => getListOptionsValue($list_path,"bouncelife"),
					"help" => getTunableHelp("bouncelife")
				),
				_("Access list:") => array(
					"widget" => getListOptionsTextarea($list_path,"access"),
					"help" => getTunableHelp("access")
				)
			)
		),
		_("Archive") => array(
			"default_close" => "yes",
			"form_lines" => array(
				_("No archives:") => array(
					"widget" => getListOptionsBoolean($list_path,"noarchive"),
					"help" => getTunableHelp("noarchive")
				),
				_("No get-N function:") => array(
					"widget" => getListOptionsBoolean($list_path,"noget"),
					"help" => getTunableHelp("noget")
				),
				_("get-N function only for subscribers:") => array(
					"widget" => getListOptionsBoolean($list_path,"subonlyget"),
					"help" => getTunableHelp("subonlyget")
				)
			)
		),
		_("Web archive") => array(
			"default_close" => "yes",
			"form_lines" => array(
				_("Enable webarchive:") => array(
					"widget" => getListOptionsWABoolean($list_path,"webarchive"),
					"help" => getTunableHelp("webarchive")
				),
				_("Own template:") => array(
					"widget" => getListOptionsWATextarea($list_path,"rcfile"),
					"help" => getTunableHelp("rcfile")
				),
				_("Recreate:") => array(
					"widget" => getListOptionsWABooleanActions($list_path,"recreatewa"),
					"help" => getTunableHelp("recreatewa")
				),
				_("Delete:") => array(
					"widget" => getListOptionsWABooleanActions($list_path,"deletewa"),
					"help" => getTunableHelp("deletewa")
				),
				_("Anti-spam mode:") => array(
					"widget" => getListOptionsWABoolean($list_path,""),
					"help" => getTunableHelp("")
				)
			)
		)
	)
);
$txt .= dtcFoldingForm($dsc);

		$txt .= subscribers_list($list_path);
	}else{
		$txt .= "<br><br>". _("new mailing list");
		$txt .= "<br><br><h3>". _("New mailing list:") ."</h3><br>";

		if($nbr_email < $max_email){
			$txt .= "
<form method=\"$conf_post_or_get\" action=\"?\" method=\"post\">
<input type=\"hidden\" name=\"adm_login\" value=\"$adm_login\">
<input type=\"hidden\" name=\"adm_pass\" value=\"$adm_pass\">
<input type=\"hidden\" name=\"addrlink\" value=\"$addrlink\">
<input type=\"hidden\" name=\"edit_domain\" value=\"$edit_domain\">
<input type=\"hidden\" name=\"whatdoiedit\" value=\"mails\">";
	$txt .= dtcFormTableAttrs().dtcFormLineDraw(_("List name"),"<input type=\"text\" placeholder=\"listname\" name=\"newlist_name\" value=\"\"> @$edit_domain",0);
	$txt .= dtcFormLineDraw( _("List owner:"),"<input type=\"text\" placeholder=\"example@example.com\" name=\"newlist_owner\" value=\"\">",1,_("This is the main owner of the list."));
	$txt .= dtcFormLineDraw("","<input type=\"hidden\" name=\"addnewlisttodomain\" value=\"Ok\">".submitButtonStart()._("Ok").submitButtonEnd(),0);
	$txt .= "</table>
</form>
";
		}else{
			$txt .= _("Maximum number of lists reached") ."<br>";
		}
	}
	$txt .= "</b></font></font>";
	
	return $txt;
}

function subscribers_list($list_path){
	global $adm_login;
	global $adm_pass;
	global $addrlink;
	global $edit_domain;
	global $conf_post_or_get;

	$out = "<br><h3>". _("Subscriber list (click the address to unsubscribe):") ."</h3><br><br>";

	$path = $list_path."/subscribers.d";

	// Get all the subscribers in an array
	$subs = array();
	if (is_dir($path)){
		if ($dh = opendir($path)){
			while (($file = readdir($dh)) !== false){
				$fpath = $path ."/". $file;
				if(filetype($fpath) == "file"){
					$fcontent = file($fpath);
					$n = sizeof($fcontent);
					for($i=0;$i<$n;$i++){
						$subs[] = $fcontent[$i];
					}
				}
			}
		}
	}
	// Sort by alpha order
	sort($subs);
	// Display
	$n = sizeof($subs);
	for($i=0;$i<$n;$i++){
		if($i != 0){
			$out .= " - ";
		}
		$out .= "<a href=\"?adm_login=$adm_login&adm_pass=$adm_pass&addrlink=$addrlink&edit_domain=$edit_domain&whatdoiedit=mails&edit_mailbox=".$_REQUEST["edit_mailbox"]."&action=unsubscribe_user&subscriber_email=".$subs[$i]."\">".$subs[$i]."</a>";
	}
	$out .= "<br><br><h3>". _("Subscribe a new user") .":</h3>";
	$out .= dtcFormTableAttrs();
	$out .= dtcFormLineDraw(_("New subscriber email address:"),"<form method=\"$conf_post_or_get\" action=\"?\" method=\"post\">
		<input type=\"hidden\" name=\"adm_login\" value=\"$adm_login\">
		<input type=\"hidden\" name=\"adm_pass\" value=\"$adm_pass\">
		<input type=\"hidden\" name=\"addrlink\" value=\"$addrlink\">
		<input type=\"hidden\" name=\"edit_domain\" value=\"$edit_domain\">
		<input type=\"hidden\" name=\"whatdoiedit\" value=\"mails\">
		<input type=\"hidden\" name=\"edit_mailbox\" value=\"".htmlspecialchars($_REQUEST["edit_mailbox"])."\">
		<input type=\"hidden\" name=\"action\" value=\"subscribe_new_user\">
		<input type=\"text\" size=\"40\" name=\"subscriber_email\" value=\"\">",1);
	$out .= dtcFormLineDraw("","<input type=\"hidden\" value=\"Ok\">".submitButtonStart()._("Ok").submitButtonEnd()."</form>",0);
	$out .= "</table>";

	// Get a list of existing mlmmj translation templates
	// TODO: adapt for the path in FreeBSD, CentOS, etc.
	$options = "";
	$dir = "/usr/share/mlmmj/text.skel/";
	if (is_dir($dir)) {
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if($file != "." && $file != ".."){
					$options .= "<option value=\"$file\">$file</option>";
				}
			}
			closedir($dh);
		}
	}

	$out .= "<br><br><h3>". _("Language of the list:") ."</h3>";
	$out .= dtcFormTableAttrs();
	$out .= dtcFormLineDraw(_("Language:"),"<form method=\"$conf_post_or_get\" action=\"?\" method=\"post\">
		<input type=\"hidden\" name=\"adm_login\" value=\"$adm_login\">
		<input type=\"hidden\" name=\"adm_pass\" value=\"$adm_pass\">
		<input type=\"hidden\" name=\"addrlink\" value=\"$addrlink\">
		<input type=\"hidden\" name=\"edit_domain\" value=\"$edit_domain\">
		<input type=\"hidden\" name=\"whatdoiedit\" value=\"mails\">
		<input type=\"hidden\" name=\"edit_mailbox\" value=\"".htmlspecialchars($_REQUEST["edit_mailbox"])."\">
		<input type=\"hidden\" name=\"action\" value=\"copy_list_text_language\">
		<select name=\"list_text_lang\">$options</select>",1,_("Select the language of the template to copy in the list folder"));
	$out .= dtcFormLineDraw("","<input type=\"hidden\" value=\"Ok\">".submitButtonStart()._("Ok").submitButtonEnd()."</form>",0);
	$out .= "</table>";
	return $out;
}

function getTunableHelp($tunable_name){
	$hlp = "<b>". $tunable_name.":</b> ";
	switch($tunable_name){
	case "subonlypost":
		$hlp .= _("When this flag is set, only people who are subscribed to the list, are allowed to post to it. The check is made against the &quot;From:&quot; header.") ;
	case "closedlist":
		$hlp .= _("Is the list is open or closed. If it\'s closed subscribtion and unsubscription via mail is disabled.") ;
		break;
	case "owner":
		$hlp .= _("The email addresses in this fields (1 per line) will get mails to listname-owner@listdomain.tld") ;
		break;
	case "moderated":
		$hlp .= _("If this flag is set, the email addresses in the field moderators will act as moderators for the list.") ;
		break;
	case "moderators":
		$hlp .= _("This is the list of moderators.") ;
		break;
	case "nosubconfirm":
		$hlp .= _("If this flag exists, no mail confirmation is needed to subscribe to the list. This should in principle never ever be used, but there is times on local lists etc. where this is useful. HANDLE WITH CARE!") ;
		break;
	case "prefix":
		$hlp .= _("The prefix for the Subject: line of mails to the list. This will alter the Subject: line, and add a prefix if it\'s not present elsewhere.") ;
		break;
	case "delheaders":
		$hlp .= _("In those fields is specified *ONE* headertoken to match per line. If the fields are like this:<br><br>Received:<br>Message-ID:<br><br>Then all occurences of these headers in incoming list mail will be deleted. From: and Return-Path: are deleted no matter what.") ;
		break;
	case "addtohdr":
		$hlp .= _("When this flag is present, a To: header including the recipients emailaddress will be added to outgoing mail. Recommended usage is to remove existing To: headers with delheaders (see above) first.") ;
		break;
	case "tocc":
		$hlp .= _("If this flag is set, the list address does not have to be in the To: or Cc: header of the email to the list (interesting for aliases addressing multiple lists).") ;
		break;
	case "customheaders":
		$hlp .= _("These headers are added to every mail coming through. This is the place you want to add Reply-To: header in case you want such.") ;
		break;
	case "footer":
		$hlp .= _("Fill this if you want every mail to have something like:<br>--<br>To unsubscribe send a mail to coollist+unsubscribe@lists.domain.net.") ;
		break;
	case "noarchive":
		$hlp .= _("If this flag exists, the mail won\'t be saved in the archive but simply deleted.") ;
		break;
	case "noget":
		$hlp .= _("If this file exists, then retrieving old posts with -get-N (for exemple mylist-get-12@my-domain.tld) is disabled") ;
		break;
	case "subonlyget":
		$hlp .= _("If this file exists, then retrieving old posts with -get-N is only possible for subscribers. The above mentioned \'noget\' have precedence.") ;
		break;
	case "digestinterval":
		$hlp .= _("This value specifies how many seconds will pass before the next digest is sent. Defaults to 604800 seconds, which is 7 days.") ;
		break;
	case "digestmaxmails":
		$hlp .= _("This file specifies how many mails can accumulate before digest sending is triggered. Defaults to 50 mails, meaning that if 50 mails arrive to the list before digestinterval have passed, the digest is delivered.") ;
		break;
	case "notifysub":
		$hlp .= _("If this flag is present, the owner(s) will get a mail with the address of someone sub/unsubscribing to a mailinglist.") ;
		break;
	case "nosubonlydenymails":
		$hlp .= _("Help missing for nosubonlydenymails") ;
		break;
	case "notoccdenymails":
		$hlp .= _("Reject mails that don\'t have the list address in the To: or Cc:.") ;
		break;
	case "noaccessdenymails":
		$hlp .= _("Help missing for noaccessdenymails") ;
		break;
	case "relayhost":
		$hlp .= _("Mail server used to send the messages.") ;
		break;
	case "memorymailsize":
		$hlp .= _("Here is specified in bytes how big a mail can be and still be prepared for sending in memory. It\'s greatly reducing the amount of write system calls to prepare it in memory before sending it, but can also lead to denial of service attacks. Default is 16k (16384 bytes).") ;
		break;
	case "verp":
		$hlp .= _("Enable VERP support.") ;
		break;
	case "bouncelife":
		$hlp .= _("Here is specified for how long time in seconds an address can bounce before it\'s unsubscribed. Defaults to 432000 seconds, which is 5 days.") ;
		break;
	case "maxverprecips":
		$hlp .= _("How many recipients pr. mail delivered to the smtp server. Defaults to 100.") ;
		break;
	case "delimiter":
		$hlp .= _("Do not change unless you really know what you are doing.") ;
		break;
	case "access":
		$hlp .= _("If this file exists, all headers of a post to the list is matched against the rules. The first rule to match wins. NOTE: the default action is to deny access (reject the mail) so take care if you write something here") ;
		break;
	case "webarchive":
		$hlp .= _("Enable webarchive.") ;
		break;
	case "rcfile":
		$hlp .= _("Insert here the template\'s code that you want use for your web archive. Read <a href=\'http://www.mhonarc.org/MHonArc/doc/resources/rcfile.html\' target=\'_blank\'>documentation</a> and see <a href=\'http://www.mhonarc.org/MHonArc/doc/app-rcfileexs.html\' target=\'_blank\'>examples</a>.") ;
		break;
	case "recreatewa":
		$hlp .= _("Recreate all messages of webarchive. Use this only if you have changed the webarchive\'s template. NOTE: this works only if you have &quot;web archive&quot; checked.") ;
		break;
	case "deletewa":
		$hlp .= _("Delete all messages of webarchive. NOTE: this works only if you have &quot;web archive&quot; not checked.") ;
		break;
	case "spammode":
		$hlp .= _("Hide email addresses to avoid spam.") ;
		break;
	default:
		break;
	}

	return $hlp;
}

function getListOptionsBoolean($ctrl_path,$tunable_name,$tunable_title){
	$option_file = $ctrl_path."/control/".$tunable_name;
	if (file_exists($option_file)){
		$check_option = " checked";
	}else{
		$check_option = "";
	}
	return "<input type=\"checkbox\" value=\"yes\" name=\"".$tunable_name."\"".$check_option.">";
}

function getListOptionsValue($ctrl_path,$tunable_name,$tunable_title){
	$option_file = $ctrl_path."/control/".$tunable_name;
	if (!file_exists($option_file)){
		$value = "";
	}else{
		$a = file($option_file);
		$value = $a[0];
	}
	return "<input size=\"40\" type=\"text\" value=\"".htmlspecialchars($value)."\" name=\"".$tunable_name."\">";
}

function getListOptionsTextarea($ctrl_path,$tunable_name,$tunable_title){
	$option_file = $ctrl_path."/control/".$tunable_name;
	$value = "";
	if (file_exists($option_file)){
		$a = file($option_file);
		foreach ($a as $line_num => $line) {
			$value .= str_replace("\r","",str_replace("\n","",$line))."\n";
		}
	}
	return "<textarea rows=\"5\" cols=\"60\" name=\"".$tunable_name."\">".htmlspecialchars($value)."</textarea>";
}


function getListOptionsList($ctrl_path,$tunable_name,$tunable_title){
	$option_file = $ctrl_path."/control/".$tunable_name;
	if (!file_exists($option_file)){
		$values = array();
	}else{
		$values = file($option_file);
	}
	$start=0;

        $mouseover = "onmouseover=\"Tip('".getTunableHelp($tunable_name)."',STICKY,true,CLICKCLOSE,true,FADEIN,600)\"";
	$out = "<table border=\"0\" cellpadding=\"0\" cellspacing=\"2\">";
	
	for($i=0;$i<sizeof($values);$i++){
		$out .= "<tr><td><input size=\"40\" type=\"text\" value=\"".htmlspecialchars($values[$i])."\" name=\"".$tunable_name."[]\"></td></tr>";
	}
	$out .= "<tr><td><input size=\"40\" type=\"text\" value=\"\" name=\"".$tunable_name."[]\"></td></tr>";
	$out .= "</table>";
	return $out;
}

function getListOptionsWABoolean($tunable_name, $tunable_title){
	global $pro_mysql_list_table;
	global $edit_domain;
	$name = $_REQUEST["edit_mailbox"];
	$test_query = "SELECT webarchive FROM $pro_mysql_list_table	WHERE domain='$edit_domain' AND name='$name' LIMIT 1";
	$test_result = mysqli_query($mysql_connection,$test_query)or die("Cannot execute query \"$test_query\" line ".__LINE__." file ".__FILE__. " sql said ".mysql_error());
	$test = mysql_fetch_array($test_result);
	if ($test[0]== "yes"){
		$check_option = " checked";
	}else{
		$check_option = "";
	}
	return "<input type=\"checkbox\" value=\"yes\" name=\"".$tunable_name."\"".$check_option.">";
}

function getListOptionsWATextarea($ctrl_path,$tunable_name,$tunable_title){
	$option_file = $ctrl_path."/".$tunable_name;
	$value = "";
	if (file_exists($option_file)){
		$a = file($option_file);
		foreach ($a as $line_num => $line) {
			$value .= $line."\n";
		}
	}
	return "<textarea rows=\"5\" cols=\"40\" name=\"".$tunable_name."\">".htmlspecialchars($value)."</textarea>";
}

function getListOptionsWABooleanActions($tunable_name,$tunable_title){
	return "<input type=\"checkbox\" value=\"yes\" name=\"".$tunable_name."\">";
}

?>
