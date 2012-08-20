<?php

// Should return a decimal with added gateway fees.
function abitab_calculate_fee($amount){
	global $secpayconf_abitab_flat_fees;
	$total = $amount + $secpayconf_abitab_flat_fees;
	return $total;
}

// Display the payment link option
function abitab_display_icon($pay_id,$amount,$item_name,$return_url,$use_recurring = "no"){
	global $conf_administrative_site;
	global $pro_mysql_pay_table;

	global $secpayconf_currency_letters;
	global $secpayconf_abitab_logo_url;
	global $conf_use_ssl;

	if($conf_use_ssl == "yes"){
		$goback_start = "https://";
	}else{
		$goback_start = "http://";
	}

	// Get the hash check key to be able to forward it in the form
	// We need to use a hash key otherwise anybody could set all payments as validated
	// if we don't check for it.
	$q = "SELECT * FROM $pro_mysql_pay_table WHERE id='$pay_id'";
	$r = mysql_query($q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
	$n = mysql_num_rows($r);
	if($n != 1){
		die("Impossible to get the pay_id line ".__LINE__." file ".__FILE__);
	}
	$a = mysql_fetch_array($r);
	$hash = $a["hash_check_key"];

	$add_to_form = '<input type="hidden" name="amount" value="'.str_replace(",",".",$amount).'">';
	$out = '<form action="'.$goback_start.$conf_administrative_site."/dtc/westernunion_and_abitab.php".'" method="post" target="_top">
<input type="hidden" name="item_name" value="'.$item_name.'">
<input type="hidden" name="hash_check" value="'.$hash.'">
<input type="hidden" name="item_id" value="'.$pay_id.'">
<input type="hidden" name="payment_type" value="abitab">
<input type="hidden" name="currency_code" value="'.$secpayconf_currency_letters.'">
'.$add_to_form.'
<input type="image" src="';
	if (empty($secpayconf_abitab_logo_url))
		{
		$out .= '/dtc/abitab.jpg';
		}
	else
		{
		$out .= $secpayconf_abitab_logo_url;
		}
	$out .= '" border="0"
name="submit" alt="'. _("Pay by Abitab") .'">
</form>';
	return $out;

}

$secpay_modules[] = array(
	"display_icon" => "abitab_display_icon",
	"use_module" => $secpayconf_accept_abitab,
	"calculate_fee" => "abitab_calculate_fee",
	"instant_account" => _("No")
);

?>
