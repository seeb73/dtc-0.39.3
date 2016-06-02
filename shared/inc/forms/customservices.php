<?php

function drawAdminTools_Custom($admin,$custom_id){
	global $adm_login;
	global $adm_pass;
	global $rub;
	global $addrlink;

	global $pro_mysql_product_table;
	global $pro_mysql_custom_product_table;
	global $pro_mysql_custom_heb_types_table;
	global $pro_mysql_admin_table;

	global $secpayconf_currency_letters;
	global $secpayconf_use_products_for_renewal;
	global $conf_post_or_get;
	global $conf_custom_renewal_shutdown;
	global $conf_global_extend;
	global $conf_show_invoice_info;

	global $submit_err_custom;

	get_secpay_conf();

	$out = "<font color=\"red\">$submit_err_custom</font>";

	// Check owner and fetch!
	checkCustomAdmin($adm_login,$adm_pass,$custom_id);
	$q = "SELECT * FROM $pro_mysql_custom_product_table WHERE id='$custom_id';";
	$r = mysqli_query($mysql_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
	$n = mysqli_num_rows($r);
	if($n != 1){
		$out .= _("Custom id not found!");
		return $out;
	}
	$custom_prod = mysql_fetch_array($r);

	// Display the current contract
	$q = "SELECT * FROM $pro_mysql_product_table WHERE id='".$custom_prod["product_id"]."';";
	$r = mysqli_query($mysql_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
	$n = mysqli_num_rows($r);
	if($n == 1){
		$server_prod = mysql_fetch_array($r);
		$contract = $server_prod["name"];
	}else{
		$contract = _("Not found!");
	}

	// Get the current admin
	$q = "SELECT * FROM $pro_mysql_admin_table WHERE adm_login='".$adm_login."';";
	$r = mysqli_query($mysql_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
	$n = mysqli_num_rows($r);
	if($n == 1){
		$admin = mysql_fetch_array($r);
	}

	$additiona_info = "";
	if($server_prod["custom_heb_type"] != 0){
		$q = "SELECT * FROM $pro_mysql_custom_heb_types_table WHERE id='".$server_prod["custom_heb_type"]."'";
		$r = mysqli_query($mysql_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
		$n = mysqli_num_rows($r);
		if($n == 1){
			$custom_heb_types = mysql_fetch_array($r);
			if($custom_heb_types["reqdomain"] == "yes"){
				$additiona_info .= "<br>"._("Domain or user name:")." ".$custom_prod["domain"];
			}
		}else{
			$additiona_info .= "<br>"._("Warning: no custom type found")." line ".__LINE__." file ".__FILE__;
		}
	}
	$out .= "<h3>". _("Custom product contract:") ."</h3>
<br>
"._("Custom product contract:")." ".$contract.$additiona_info."
<br><br>";

	$ar = explode("-",$custom_prod["expire_date"]);
	$out .= "<b><u>". _("Custom product expiration dates:") ."</u></b><br>";
	$out .= _("Your custom product was first registered on the:") ." ".$custom_prod["start_date"]."<br>";
	if(date("Y") > $ar[0] ||
			(date("Y") == $ar[0] && date("m") > $ar[1]) ||
			(date("Y") == $ar[0] && date("m") == $ar[1] && date("d") > $ar[2])){
		$out .= "<font color=\"red\">". _("Your custom product has expired on the: ") .$custom_prod["expire_date"]."</font>";
	}else{
		$out .= _("Your custom product will expire on the: ") .$custom_prod["expire_date"];
	}
	$out .= "<BR>"._("Your can pay your custom service without overdue charges until:")." ".calculateExpirationDate($custom_procustom_pro["expire_date"],'00-00-'.$conf_global_extend);
	$out .= "<br>"._("Your custom service be shutdown on:")." ";
	$period = "00-00-".($admin["permanent_extend"]+$admin["temporary_extend"]+$conf_custom_renewal_shutdown);
	$out .= " ".calculateExpirationDate($custom_prod["expire_date"],$period)."<br>";

	$q = "SELECT * FROM $pro_mysql_admin_table WHERE adm_login='".$adm_login."'";
	$r = mysqli_query($mysql_connection,$q) or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
	$admin = mysql_fetch_array($r);

	if($admin["show_invoice_info"] == 'yes' && $conf_show_invoice_info == 'yes'){
		$out .= "<br>". _("Please renew it with one of the following options") ."<br>";
		if ($secpayconf_use_products_for_renewal == 'yes'){
			$q = "SELECT name, price_dollar FROM $pro_mysql_product_table WHERE id='".$custom_prod["product_id"]."';";
			$r = mysqli_query($mysql_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
			$n = mysqli_num_rows($r);
			if($n == 1){
				$a = mysql_fetch_array($r);
				$out .= "<br><form method=\"$conf_post_or_get\" action=\"/dtc/new_account.php\">
		<input type=\"hidden\" name=\"action\" value=\"contract_renewal\">
		<input type=\"hidden\" name=\"renew_type\" value=\"custom\">
		<input type=\"hidden\" name=\"product_id\" value=\"".$custom_prod["product_id"]."\">
		<input type=\"hidden\" name=\"custom_id\" value=\"".$custom_prod["id"]."\">
		<input type=\"hidden\" name=\"adm_login\" value=\"$adm_login\">
		".submitButtonStart().$a["name"]." (".$a["price_dollar"]." $secpayconf_currency_letters)".submitButtonEnd()."
		</form><br>";
			}
		}

		$q = "SELECT * FROM $pro_mysql_product_table WHERE renew_prod_id='".$custom_prod["product_id"]."';";
		$r = mysqli_query($mysql_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
		$n = mysqli_num_rows($r);
		for($i=0;$i<$n;$i++){
			$a = mysql_fetch_array($r);
			$out .= "<br><form method=\"$conf_post_or_get\" action=\"/dtc/new_account.php\">
		<input type=\"hidden\" name=\"action\" value=\"contract_renewal\">
		<input type=\"hidden\" name=\"renew_type\" value=\"custom\">
		<input type=\"hidden\" name=\"product_id\" value=\"".$a["id"]."\">
		<input type=\"hidden\" name=\"custom_id\" value=\"".$custom_prod["id"]."\">
		<input type=\"hidden\" name=\"adm_login\" value=\"$adm_login\">
		".submitButtonStart().$a["name"]." (".$a["price_dollar"]." $secpayconf_currency_letters)".submitButtonEnd()."
		</form><br>";
		}
	}

	return $out;
}

?>
