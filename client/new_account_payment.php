<?php


// This one is moved before the includes so we can use $extapi_pay_id in the string files.
if(isset($_REQUEST["action"]) && ($_REQUEST["action"] == "return_from_pay" || $_REQUEST["action"] == "enets-success")){
	switch($_REQUEST["action"]){
	case "return_from_pay":
		$extapi_pay_id = $_REQUEST["regid"];
		break;
	case "enets-success":
		$extapi_pay_id = $_REQUEST["txnRef"];
		break;
	default:
		$extapi_pay_id = -1;
		break;
	}
}

require_once("../shared/autoSQLconfig.php");
$panel_type="client";
// All shared files between DTCadmin and DTCclient
require_once("$dtcshared_path/dtc_lib.php");
require_once("new_account_form.php");

get_secpay_conf();

////////////////////////////////////
// Create the top banner and menu //
////////////////////////////////////
$anotherTopBanner = anotherTopBanner("DTC");
if(isset($txt_top_menu_entrys)){
$anotherMenu = makeHoriMenu($txt_top_menu_entrys[$lang],2);
}
$anotherLanguageSelection = anotherLanguageSelection();
$lang_sel = skin($conf_skin,$anotherLanguageSelection, _("Language") );

	$form = "";
	$print_form = "yes";
	// Register form
	$reguser = select_user();
	// If err=0 then it's already in the new_admin form!
	if($reguser["err"] == 0){
		$form .= new_account_payment($reguser);
	}else{
		$form .= $reguser["form"];
	}

$login_skined = skin($conf_skin,$form, _("Pay a new account") );
$mypage = layout_login_and_languages($login_skined,$lang_sel);
if(function_exists("skin_NewAccountPage")){
	skin_NewAccountPage($login_skined);
}else{
	echo anotherPage("Client:","","",makePreloads(),$anotherTopBanner,"",$mypage,anotherFooter(""));
}

function select_user(){
	global $pro_mysql_new_admin_table;
	if (isset($_REQUEST['username'])){
		$username = mysql_real_escape_string($_REQUEST['username']);
	}else{
		$username = "";
	}
	if (isset($_REQUEST['password'])){
		$password = mysql_real_escape_string($_REQUEST['password']);
	}else{
		$password = "";
	}

	if ($username == "" and $password == ""){
		$form = select_user_form("");
	}else{
		if ($username == ""){
			$error = _("Please complete the username.");
		}elseif ($password == ""){
			$error = _("Please complete the password.");
		}else{
			$q = "SELECT id FROM $pro_mysql_new_admin_table
			WHERE reqadm_login='".$username."' and reqadm_pass='".$password."';";
		$r = mysql_query($q)or die("Cannot query \"$q\" ! line: ".__LINE__." file: ".__FILE__." sql said: ".mysql_error());
		$n = mysql_num_rows($r);
		if($n != 1){
			$error = _("User or password invalid.");
		}else{
			$adm = mysql_fetch_array($r);
			$ret["err"] = 0;
			$ret["mesg"] = "Query ok!";
			$ret["id"] = $adm["id"];
			return $ret;
		}
		}
		$form = select_user_form($error);
	}
	$ret["err"] = 1;
	$ret['form'] = $form;
	return $ret;
}

function select_user_form($error = ""){
	global $conf_administrative_site;
	global $conf_use_ssl;
	if($conf_use_ssl == "yes"){
		$surl = "s";
	}else{
		$surl = "";
	}
	$HTML_admin_edit_data = _("This form is for the first payment only, if you are already a client go to ")
."<a href=\"http".$surl."://".$conf_administrative_site."/dtc/\">http"
.$surl."://".$conf_administrative_site."/dtc/</a><br /><form action=\"?\" method=\"post\">
<table>
<tr>\n";
if ($error != ""){
	$HTML_admin_edit_data .= "	<td align=\"right\">". _("Error") ."</td>
	<td>$error</td>
</tr><tr>\n";
}
$HTML_admin_edit_data .= "	<td align=\"right\">". _("Login: ") ."</td>
	<td><input type=\"text\" name=\"username\" value=\"\"></td>
</tr><tr>
	<td align=\"right\">". _("Password:") ."</td>
	<td><input type=\"password\" name=\"password\" value=\"\"></td>
</tr><tr>
	<td></td><td><input type=\"submit\" name=\"Login\" value=\"login\">
</td></tr>
</table></form>";
	return $HTML_admin_edit_data;
	}
?>
