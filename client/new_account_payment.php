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

// Uppon success, $ret["id"] contains the ID in the new_admin table, and $ret["err"] == 0
// Uppon error, $ret["form"] contains the select_user form and $ret["err"] == 1, plus $ret["mesg"] contains an error message
function select_user(){
	global $pro_mysql_new_admin_table;
	$ret = array( "err" => 0, "mesg" => "Query ok!", "form" => "");

	if(!isset($_REQUEST['username']) && !isset($_REQUEST['password']) ){
		$ret["form"] = select_user_form("");
		$ret["err"] = 1;
		return $ret;
	}
	if( !isDTCLogin($_REQUEST['username']) ){
		$ret["mesg"] = _("User or password invalid.");
		$ret["err"] = 1;
		return $ret;
	}
	if( !isDTCPassword($_REQUEST['password']) ){
		$ret["mesg"] = _("User or password invalid.");
		$ret["err"] = 1;
		return $ret;
	}

	$sqls_username = mysql_real_escape_string($_REQUEST['username']);
	$sqls_password = mysql_real_escape_string($_REQUEST['password']);
	$q = "SELECT id FROM $pro_mysql_new_admin_table WHERE reqadm_login='".$sqls_username."' AND reqadm_pass='".$sqls_password."';";
	$r = mysqli_query($mysql_connection,$q)or die("Cannot query \"$q\" ! line: ".__LINE__." file: ".__FILE__." sql said: ".mysql_error());
	$n = mysqli_num_rows($r);
	if($n != 1){
		$ret["mesg"] = _("User or password invalid.");
		$ret["err"] = 1;
		$ret["form"] = select_user_form($ret["mesg"]);
		return $ret;
	}
	$adm = mysqli_fetch_array($r);
	$ret["id"] = $adm["id"];
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
	$out = _("This form is for the first payment only, if you are already a client go to: ");
	$out .= "<a href=\"http".$surl."://".$conf_administrative_site."/dtc/\">http".$surl."://".$conf_administrative_site."/dtc/</a><br />";

	if ($error != ""){
		$out .= _("Error:") . " $error<br />";
	}
	$out .= dtcFormTableAttrs();
	$out .= "<form action=\"?\" method=\"post\"><input type=\"hidden\" name=\"Login\" value=\"login\">";
	$out .= dtcFormLineDraw(_("Login:"), "<input type=\"text\" name=\"username\" value=\"\">", 0);
	$out .= dtcFormLineDraw(_("Password:"), "<input type=\"password\" name=\"password\" value=\"\">", 1);
	$out .= dtcFormLineDraw("", submitButtonStart() . _("Login") . submitButtonEnd(), 0);
	$out .= "</table></form>";
	return $out;
}
?>
