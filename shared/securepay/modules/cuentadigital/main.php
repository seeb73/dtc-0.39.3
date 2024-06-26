<?php

// Should return a decimal with added gateway fees.
function cuentadigital_calculate_fee($amount){
	//global $secpayconf_cuentadigital_tipospago; // this will be used on module upgrade to manage different fees
	global $secpayconf_cuentadigital_cargocomision;
	global $secpayconf_cuentadigital_porcentajecomision;
	$total = $amount + ($amount * $secpayconf_cuentadigital_porcentajecomision / 100) + $secpayconf_cuentadigital_cargocomision;
	return $total;
}

// Display the payment link option
function cuentadigital_display_icon($product_id,$amount,$item_name,$return_url,$use_recurring = "no"){
	global $secpayconf_cuentadigital_nrocuenta;
	global $secpayconf_cuentadigital_language;
	global $secpayconf_cuentadigital_country;
	global $secpayconf_cuentadigital_logo_url;
	global $secpayconf_cuentadigital_replacelogin;
	global $lang;

	$amount = round(floatval(str_replace(",",".",$amount)), 2);

	switch( substr($lang,0,2) ){
		case "en":
		case "pt":
		case "it":
		case "fr":
		case "de":
				$cuenta_lang = $lang;
				break;
		default:
				$cuenta_lang = "en";
		}

	$finder = array(':', '\"', '\"', '(', ')', '.', ',');
	if ($secpayconf_cuentadigital_replacelogin == "yes") {
		$finder[] = ' login';
		}
	$item_name_joned = str_replace($finder,'',$item_name);
	$desccode = str_replace(' ',$secpayconf_cuentadigital_separator,$item_name_joned);

	$out = '<form action="https://www.CuentaDigital.com/api.php" method="get">'."\n";
	$out .= '<input type="hidden" name="id" value="'.$secpayconf_cuentadigital_nrocuenta.'">'."\n"; // account number
	$out .= '<input type="hidden" name="concepto" value="'.$item_name.'">'."\n"; // description of the phurchased service
	$out .= '<input type="hidden" name="precio" value="'.str_replace(',','.',$amount).'">'."\n"; // payment ammount
	$out .= '<input type="hidden" name="codigo" value="'.$desccode.'">'."\n"; // item id
	$out .= '<input type="hidden" name="l" value="'.$cuenta_lang.'">'."\n"; // language
	$out .= '<input type="hidden" name="cuntry" value="'.$secpayconf_cuentadigital_country.'">'."\n"; // country id
	$out .= '<input type="image" src="';
	if (empty($secpayconf_cuentadigital_logo_url))
		{
		$out .= 'logo_cuentadigital.gif';
		}
	else
		{
		$out .= $secpayconf_cuentadigital_logo_url;
		}
	$out .= '" border="0" name="submit" alt="';
	$out .= _("Pay Cuenta Digital") . '">';
	$out .= '</form>'."\n";

	return $out;
}

$secpay_modules[] = array(
	"display_icon" => "cuentadigital_display_icon",
	"use_module" => $secpayconf_use_cuentadigital,
	"calculate_fee" => "cuentadigital_calculate_fee",
	"instant_account" => _("No")
);

?>
