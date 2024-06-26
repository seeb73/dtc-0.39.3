<?php

function drawAdminTools_MultipleRenew($admin){
	global $adm_login;
	global $adm_pass;
	global $addrlink;
	global $pro_mysql_product_table;
	global $secpayconf_currency_letters;
	global $conf_post_or_get;
	global $secpayconf_use_products_for_renewal;
	global $mysqli_connection;

	get_secpay_conf();
	$out = "<br><br>";

	//echo "<pre>"; print_r($admin); echo "</pre>";
	$out .= "<form method=\"$conf_post_or_get\" action=\"/dtc/new_account.php\">
<input type=\"hidden\" name=\"action\" value=\"contract_renewal\">
<input type=\"hidden\" name=\"renew_type\" value=\"multiple-services\">
<input type=\"hidden\" name=\"adm_login\" value=\"$adm_login\">
<table cellspacing=\"0\" cellpading=\"1\" border=\"0\">
<tr><td class=\"dtcDatagrid_table_titles\"></td>
<td class=\"dtcDatagrid_table_titles\">"._("Renewal product")."</td>
<td class=\"dtcDatagrid_table_titles\">"._("Hostname")."</td>
</tr>";
	$nbr_shared = 0;
	if ( $admin["info"]["prod_id"] != 0 ){
		$nbr_shared = 1;
		$td = "td  class=\"dtcDatagrid_table_flds\"";
		$q = "SELECT * FROM $pro_mysql_product_table WHERE id='".$admin["info"]["prod_id"]."';";
		$r = mysqli_query($mysqli_connection,$q)or die("Cannot query \"$q\" line ".__LINE__." file ".__FILE__." sql said ".mysqli_error($mysqli_connection));
		$n = mysqli_num_rows($r);
		if($n != 1){
			$out .= "<!-- Cannot find your Shared Hosting product ID ".$admin["info"]["prod_id"].". -->";
		}
		$prod = mysqli_fetch_array($r);
		$q = "SELECT * FROM $pro_mysql_product_table WHERE renew_prod_id='".$admin["info"]["prod_id"]."';";
		$r = mysqli_query($mysqli_connection,$q)or die("Cannot query \"$q\" line ".__LINE__." file ".__FILE__." sql said ".mysqli_error($mysqli_connection));
		$n = mysqli_num_rows($r);
		if($n < 1 && $secpayconf_use_products_for_renewal == 'no'){
			$out .= "<!-- Cannot find renewal product ID for your Shared Hosting ".$admin["info"]["prod_id"].". -->";
		}
		$pop = "";
		if ($secpayconf_use_products_for_renewal == 'yes'){
			$pop .= "<option value=\"".$prod["id"]."\">".$prod["name"]." (".$prod["price_dollar"]." $secpayconf_currency_letters)</option>";
		}
		for($j=0;$j<$n;$j++){
			$a = mysqli_fetch_array($r);
			$pop .= "<option value=\"".$a["id"]."\">".$a["name"]." (".$a["price_dollar"]." $secpayconf_currency_letters)</option>";
		}
		$out .= "<$td><input type=\"checkbox\" name=\"service_host[]\" value=\"shared:\"></td>
<$td><select name=\"shared:\">$pop</option></select></td>
<$td>"._("Shared Hosting")."</td>
</tr>";
	}

	$nbr_vps = sizeof($admin["vps"]);
	for($i=0;$i<$nbr_vps;$i++){
		if($i+$nbr_shared % 2){
			$td = "td  class=\"dtcDatagrid_table_flds\"";
		}else{
			$td = "td  class=\"dtcDatagrid_table_flds_alt\"";
		}
		$vps = $admin["vps"][$i];
		$q = "SELECT * FROM $pro_mysql_product_table WHERE id='".$vps["product_id"]."';";
		$r = mysqli_query($mysqli_connection,$q)or die("Cannot query \"$q\" line ".__LINE__." file ".__FILE__." sql said ".mysqli_error($mysqli_connection));
		$n = mysqli_num_rows($r);
		if($n != 1){
			$out .= "<!-- Cannot find your VPS product ID ".$vps["product_id"].". -->";
		}
		$prod = mysqli_fetch_array($r);
		$q = "SELECT * FROM $pro_mysql_product_table WHERE renew_prod_id='".$vps["product_id"]."';";
		$r = mysqli_query($mysqli_connection,$q)or die("Cannot query \"$q\" line ".__LINE__." file ".__FILE__." sql said ".mysqli_error($mysqli_connection));
		$n = mysqli_num_rows($r);
		if($n < 1 && $secpayconf_use_products_for_renewal == 'no'){
			$out .= "<!-- Cannot find renewal product ID for your VPS ".$vps["product_id"].". -->";
		}
		$pop = "";
		if ($secpayconf_use_products_for_renewal == 'yes'){
			$pop .= "<option value=\"".$prod["id"]."\">".$prod["name"]." (".$prod["price_dollar"]." $secpayconf_currency_letters)</option>";
		}
		for($j=0;$j<$n;$j++){
			$a = mysqli_fetch_array($r);
			$pop .= "<option value=\"".$a["id"]."\">".$a["name"]." (".$a["price_dollar"]." $secpayconf_currency_letters)</option>";
		}
		$out .= "<$td><input type=\"checkbox\" name=\"service_host[]\" value=\"vps:".$vps["vps_server_hostname"].":".$vps["vps_xen_name"]."\"></td>
<$td><select name=\"vps:".$vps["vps_server_hostname"].":".$vps["vps_xen_name"]."\">$pop</option></select></td>
<$td>".$vps["vps_server_hostname"].":".$vps["vps_xen_name"]."</td>
</tr>";
	}

	// echo "<pre>" ; print_r($admin["dedicated"]); echo "</pre>";
	$nbr_dedi = sizeof($admin["dedicated"]);
	for($i=0;$i<$nbr_dedi;$i++){
		if(($i+$nbr_vps+$nbr_shared) % 2){
			$td = "td  class=\"dtcDatagrid_table_flds\"";
		}else{
			$td = "td  class=\"dtcDatagrid_table_flds_alt\"";
		}
		$dedi = $admin["dedicated"][$i];
		$q = "SELECT * FROM $pro_mysql_product_table WHERE id='".$dedi["product_id"]."';";
		$r = mysqli_query($mysqli_connection,$q)or die("Cannot query \"$q\" line ".__LINE__." file ".__FILE__." sql said ".mysqli_error($mysqli_connection));
		$n = mysqli_num_rows($r);
		if($n != 1){
			$out .= "<!-- Cannot find your dedicated server product ID ".$dedi["product_id"].". -->";
		}
		$prod = mysqli_fetch_array($r);
		$q = "SELECT * FROM $pro_mysql_product_table WHERE renew_prod_id='".$dedi["product_id"]."';";
		$r = mysqli_query($mysqli_connection,$q)or die("Cannot query \"$q\" line ".__LINE__." file ".__FILE__." sql said ".mysqli_error($mysqli_connection));
		$n = mysqli_num_rows($r);
		if($n < 1 && $secpayconf_use_products_for_renewal == 'no'){
			$out .= "<!-- Cannot find renewal product ID for your dedicated server ".$dedi["product_id"].". -->";
		}
		$pop = "";
		if ($secpayconf_use_products_for_renewal == 'yes'){
			$pop .= "<option value=\"".$prod["id"]."\">".$prod["name"]." (".$prod["price_dollar"]." $secpayconf_currency_letters)</option>";
		}
		for($j=0;$j<$n;$j++){
			$a = mysqli_fetch_array($r);
			$pop .= "<option value=\"".$a["id"]."\">".$a["name"]." (".$a["price_dollar"]." $secpayconf_currency_letters)</option>";
		}
		$out .= "<$td><input type=\"checkbox\" name=\"service_host[]\" value=\"server:".$dedi["server_hostname"]."\"></td>
<$td><select name=\"server:".$dedi["server_hostname"]."\">$pop</option></select></td>
<$td>".$dedi["server_hostname"]."</td>
</tr>";
	}

	// echo "<pre>" ; print_r($admin["custom"]); echo "</pre>";
	$nbr_custom = sizeof($admin["custom"]);
	for($i=0;$i<$nbr_custom;$i++){
		if(($i+$nbr_vps+$nbr_dedi+$nbr_shared) % 2){
			$td = "td  class=\"dtcDatagrid_table_flds\"";
		}else{
			$td = "td  class=\"dtcDatagrid_table_flds_alt\"";
		}
		$custom = $admin["custom"][$i];
		$q = "SELECT * FROM $pro_mysql_product_table WHERE id='".$custom["product_id"]."';";
		$r = mysqli_query($mysqli_connection,$q)or die("Cannot query \"$q\" line ".__LINE__." file ".__FILE__." sql said ".mysqli_error($mysqli_connection));
		$n = mysqli_num_rows($r);
		if($n != 1){
			$out .= "<!-- Cannot find your custom product ID ".$custom["product_id"].". -->";
		}
		$prod = mysqli_fetch_array($r);
		$q = "SELECT * FROM $pro_mysql_product_table WHERE renew_prod_id='".$custom["product_id"]."';";
		$r = mysqli_query($mysqli_connection,$q)or die("Cannot query \"$q\" line ".__LINE__." file ".__FILE__." sql said ".mysqli_error($mysqli_connection));
		$n = mysqli_num_rows($r);
		if($n < 1 && $secpayconf_use_products_for_renewal == 'no'){
			$out .= "<!-- Cannot find renewal product ID for your custom product ".$custom["product_id"].". -->";
		}
		$pop = "";
		if ($secpayconf_use_products_for_renewal == 'yes'){
			$pop .= "<option value=\"".$prod["id"]."\">".$prod["name"]." (".$prod["price_dollar"]." $secpayconf_currency_letters)</option>";
		}
		for($j=0;$j<$n;$j++){
			$a = mysqli_fetch_array($r);
			$pop .= "<option value=\"".$a["id"]."\">".$a["name"]." (".$a["price_dollar"]." $secpayconf_currency_letters)</option>";
		}
		$out .= "<$td><input type=\"checkbox\" name=\"service_host[]\" value=\"custom:".$custom["domain"]."\"></td>
<$td><select name=\"custom:".$custom["domain"]."\">$pop</option></select></td>
<$td>".$custom["domain"]."</td>
</tr>";
	}

	if(($nbr_dedi+$nbr_vps+$nbr_custom+$nbr_shared) % 2){
		$td = "td  class=\"dtcDatagrid_table_flds\"";
	}else{
		$td = "td  class=\"dtcDatagrid_table_flds_alt\"";
	}
	$out .= "<tr><$td colspan=\"3\">".submitButtonStart()._("Renew").submitButtonEnd()."</td></tr>";

	$out .= "</table></form>";
	return $out;
}

?>
