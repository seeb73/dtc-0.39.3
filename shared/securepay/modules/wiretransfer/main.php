<?php

// Should return a decimal with added gateway fees.
function wire_calculate_fee($amount){
	global $secpayconf_wiretransfers_flat_fees;
	$total = $amount + $secpayconf_wiretransfers_flat_fees;
	return $total;
}

// Display the payment link option
function wire_display_icon($pay_id,$amount,$item_name,$return_url,$use_recurring = "no"){
	global $paypal_account;
	global $conf_administrative_site;
	global $pro_mysql_pay_table;
	global $mysqli_connection;

	global $secpayconf_currency_letters;
	global $secpayconf_wiretransfers_logo_url;
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
	$r = mysqli_query($mysqli_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said: ".mysqli_error($mysqli_connection));
	$n = mysqli_num_rows($r);
	if($n != 1){
		die("Impossible to get the pay_id line ".__LINE__." file ".__FILE__);
	}
	$a = mysqli_fetch_array($r);
	$hash = $a["hash_check_key"];

	$add_to_form = '<input type="hidden" name="amount" value="'.str_replace(",",".",$amount).'">';
	$out = '<form action="'.$goback_start.$conf_administrative_site."/dtc/cheques_and_transfers.php".'" method="post" target="_top">
<input type="hidden" name="item_name" value="'.$item_name.'">
<input type="hidden" name="hash_check" value="'.$hash.'">
<input type="hidden" name="item_id" value="'.$pay_id.'">
<input type="hidden" name="payment_type" value="wire_transfer">
<input type="hidden" name="currency_code" value="'.$secpayconf_currency_letters.'">
'.$add_to_form.'
<input type="image" src="';
	if (empty($secpayconf_wiretransfers_logo_url))
		{
		$out .= '/dtc/wire.gif';
		}
	else
		{
		$out .= $secpayconf_wiretransfers_logo_url;
		}
	$out .= '" border="0"
name="submit" alt="'. _("Pay by wire transfer") .'">
</form>';
	return $out;

}

$secpay_modules[] = array(
	"display_icon" => "wire_display_icon",
	"use_module" => $secpayconf_accept_wiretransfers,
	"calculate_fee" => "wire_calculate_fee",
	"instant_account" => _("No")
);

?>
