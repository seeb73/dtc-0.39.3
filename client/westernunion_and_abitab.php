<?php

require_once("../shared/autoSQLconfig.php");
$panel_type="client";
// All shared files between DTCadmin and DTCclient
require_once("$dtcshared_path/dtc_lib.php");
require_once("new_account_form.php");
require_once("new_account_renewal.php");

get_secpay_conf();

// The language stuff...
$anotherTopBanner = anotherTopBanner("DTC");
if(isset($txt_top_menu_entrys)){
	$anotherMenu = makeHoriMenu($txt_top_menu_entrys[$lang],2);
}
$anotherLanguageSelection = anotherLanguageSelection();
$lang_sel = skin($conf_skin,$anotherLanguageSelection, _("Language") );

$proceed = "yes";
if( !isset($_REQUEST["hash_check"]) || !isRandomNum($_REQUEST["hash_check"]) ){
	$form = _("Hash check not in correct format: cannot validate payment.");
	$proceed = "no";
}
if( !isset($_REQUEST["item_id"]) || !isRandomNum($_REQUEST["item_id"]) ){
	$form = _("Hash check not in correct format: cannot validate payment.");
	$proceed = "no";
}
if( $proceed == "yes"){
	$q = "SELECT * FROM $pro_mysql_pay_table WHERE hash_check_key='" . $_REQUEST["hash_check"] . "' AND id='" . $_REQUEST["item_id"] . "'";
	$r = mysqli_query($mysql_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__);
	$n = mysqli_num_rows($r);
	if($n != 1){
		$form = _("Could not find your registration in the database.");
		$proceed = "no";
	}
}

if( $proceed == "yes"){
	if( isset($_REQUEST["payment_type"]) && $_REQUEST["payment_type"] == "westernunion"){
		$payment_type = 'westernunion';
		$pending_reason = "Western Union";
	}else{
		$payment_type = 'abitab';
		$pending_reason = "Abitab";
	}
	$q = "UPDATE $pro_mysql_pay_table SET paiement_type='$payment_type',valid='pending',pending_reason='$pending_reason' WHERE hash_check_key='" .
	mysql_real_escape_string($_REQUEST["hash_check"]) . "' AND id='" . mysql_real_escape_string($_REQUEST["item_id"]) . "'";
	$r = mysqli_query($mysql_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
}

// Display the payment infos
if( $proceed == "yes"){
	$form = "<pre>";
	if( isset($_REQUEST["payment_type"]) && $_REQUEST["payment_type"] == "westernunion"){
		$form .= $secpayconf_westernunion_details;
	}else{
		$form .= $secpayconf_abitab_details;
	}
	$form .= "</pre><br><br><b>" . _("Thanks for your order. Your order has been placed on hold until your payment is verified.") . "</b>  <a href=\"/\">" . _("Continue") . "</a><br><br>";
}

$login_skined = skin($conf_skin,$form, _("Register a new account") );
$mypage = layout_login_and_languages($login_skined,$lang_sel);
if(function_exists("skin_NewAccountPage")){
	skin_NewAccountPage($login_skined);
}else{
	echo anotherPage("Client:","","",makePreloads(),$anotherTopBanner,"",$mypage,anotherFooter(""));
}
?>
