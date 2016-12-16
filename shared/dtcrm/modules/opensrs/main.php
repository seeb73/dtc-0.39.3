<?php
/**
 * OpenSRS registry plugin for DTC.
 *
 * Go to http://opensrs.com and sign up for an account. Credit some funds
 * and then you can request an API key. 
 * Edit opensrs/opensrs/openSRS_config.php and put your API key and username.
 * 
 *
 * @author Martin Vasilev <martin@mreja.net>
 * @version 1.0
 * @package domain_registrar_plugin
 */

$tag = "OpenSRS";

Global $handle;
$handle = "save"; //If you want to comfirm registration, renew and transfer from openSRS web site use "save". If you domains to be processed directly without your confirmation use "process". 

function opensrs_cookies($domain){
require_once ("/usr/share/dtc/shared/dtcrm/modules/opensrs/opensrs/openSRS_loader.php");
	global $pro_mysql_domain_table;
	global $myslqi_connection;
	
	$q = "SELECT owner,registrar_password FROM $pro_mysql_domain_table WHERE name = '$domain';";
	$r = mysqli_query($mysql_connection,$q)or die("Cannot query \"$q\" line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
	$a = mysql_fetch_array($r);
	$login = $a["owner"];
	$pass = $a["registrar_password"];
	
	$post_params_hash["func"] = "cookieSet";
        $post_params_hash["data"]["domain"] = $domain;
	$post_params_hash["data"]["reg_username"] = $login;
        $post_params_hash["data"]["reg_password"] = $pass;
        $openSRS_results = processOpensrs("json",json_encode($post_params_hash));
	$opensrs_ret = json_decode($openSRS_results->resultFormatted, true);
        $cookie = $opensrs_ret['attributes']['cookie'];
        return $cookie;
}
//Define minimum registration period in years for TLD 
function min_reg_period($tld){
$min_reg_period=array('.com' => '1',
		      '.net' => '1',
		      '.org' => '1',
		      '.info' => '1',
		      '.biz' => '1',
		      '.ca' => '1',
		      '.us' => '1',
		      '.uk' => '1',
		      '.eu' => '1',
		      '.mobi' => '1',
		      '.asia' => '1',
		      '.ac' => '1',
		      '.at' => '1',
		      '.be' => '1',
		      '.bz' => '1',
		      '.cc' => '1',
		      '.ch' => '1',
		      '.cn' => '1',
		      '.co' => '1',
		      '.co.in' => '1',
		      '.co.nz' => '1',
		      '.com.co' => '1',
		      '.de' => '1',
		      '.dk' => '1',
		      '.es' => '1',
		      '.firm.in' => '1',
		      '.fr' => '1',
		      '.gen.in' => '1',
		      '.in' => '1',
		      '.ind.in' => '1',
		      '.io' => '1',
		      '.it' => '1',
		      '.mx' => '1',
		      '.name' => '1',
		      '.net.co' => '1',
		      '.net.in' => '1',
		      '.net.nz' => '1',
		      '.nl' => '1',
		      '.nom.co' => '1',
		      '.nu' => '1',
		      '.org.in' => '1',
		      '.org.nz' => '1',
		      '.pl' => '1',
		      '.se' => '1',
		      '.sh' => '1',
		      '.tv' => '1',
		      '.vc' => '1',
		      '.ws' => '1',
		      '.xxx' => '1',
		      );
$minregperiod = $min_reg_period[$tld];
return $minregperiod;

}

//Define maximum registration period in years for TLD 
function max_reg_period($tld){
$max_reg_period=array('.com' => '10',
		      '.net' => '10',
		      '.org' => '10',
		      '.info' => '10',
		      '.biz' => '10',
		      '.ca' => '10',
		      '.us' => '10',
		      '.uk' => '10',
		      '.eu' => '10',
		      '.mobi' => '10',
		      '.asia' => '10',
		      '.ac' => '10',
		      '.at' => '10',
		      '.be' => '10',
		      '.bz' => '10',
		      '.cc' => '10',
		      '.ch' => '10',
		      '.cn' => '10',
		      '.co' => '10',
		      '.co.in' => '10',
		      '.co.nz' => '10',
		      '.com.co' => '10',
		      '.de' => '10',
		      '.dk' => '10',
		      '.es' => '10',
		      '.firm.in' => '10',
		      '.fr' => '10',
		      '.gen.in' => '10',
		      '.in' => '10',
		      '.ind.in' => '10',
		      '.io' => '10',
		      '.it' => '10',
		      '.mx' => '10',
		      '.name' => '10',
		      '.net.co' => '10',
		      '.net.in' => '10',
		      '.net.nz' => '10',
		      '.nl' => '10',
		      '.nom.co' => '10',
		      '.nu' => '10',
		      '.org.in' => '10',
		      '.org.nz' => '10',
		      '.pl' => '10',
		      '.se' => '10',
		      '.sh' => '10',
		      '.tv' => '10',
		      '.vc' => '10',
		      '.ws' => '10',
		      '.xxx' => '10',
		      );
$maxregperiod = $max_reg_period[$tld];
return $maxregperiod;

}


/**
 * Checks the availability of the domain
 * 
 * @param string $domain_name the domain name to check
 * @return array
 */
function opensrs_registry_check_availability($domain_name){
	
	require_once ("/usr/share/dtc/shared/dtcrm/modules/opensrs/opensrs/openSRS_loader.php");
	
	$domain=explode(".", $domain_name);
	$name=$domain[0];
	$tld=".".$domain[1];
	
	$func = "lookupDomain";
	
	$post_params_hash["func"]="$func";
	$post_params_hash["data"]["domain"]="$name";
	$post_params_hash["data"]["alldomains"]=".com;.net;.org";
	$post_params_hash["data"]["selected"]="$tld";
	$openSRS_results=processOpensrs("json",json_encode($post_params_hash));
	$opensrs_ret = json_decode($openSRS_results->resultFormatted, true);
	$ret["is_success"] = 1;
	if ( $opensrs_ret[0]['status'] == "available" ) {
            $ret["attributes"]["status"] = "available";
	    $ret["attributes"]["minperiod"] = min_reg_period($tld);
	    $ret["attributes"]["maxperiod"] = max_reg_period($tld);
        }
	
	$ret["response_text"] = $opensrs_ret[0]['status'];
	
	return $ret;
}

/**
 * Prepare a list of whois contacts for passing to the registrar
 *
 * @param array $contacts array of contacts
 * @return array
 */
function opensrs_prepar_whois_params($contacts){
	if($contacts["owner"]["company"] == ""){
		$owner = $contacts["owner"]["firstname"]." ".$contacts["owner"]["lastname"];
	}else{
		$owner = $contacts["owner"]["company"];
	}
	$post_params_hash["personal"]["org_name"] = $owner;
	$post_params_hash["personal"]["first_name"] = $contacts["owner"]["firstname"];
	$post_params_hash["personal"]["last_name"] = $contacts["owner"]["lastname"];
	$post_params_hash["personal"]["email"] = $contacts["owner"]["email"];
	$post_params_hash["personal"]["url"]="";
        $post_params_hash["personal"]["phone"] = $contacts["owner"]["phone_num"];
	$post_params_hash["personal"]["fax"] = $contacts["owner"]["fax_num"];
	$post_params_hash["personal"]["address1"] = $contacts["owner"]["addr1"];
	$post_params_hash["personal"]["address2"] = $contacts["owner"]["addr2"];
	$post_params_hash["personal"]["address3"] = $contacts["owner"]["addr3"];
	$post_params_hash["personal"]["postal_code"] = $contacts["owner"]["zipcode"];
	$post_params_hash["personal"]["city"] = $contacts["owner"]["city"];
	$post_params_hash["personal"]["state"] = $contacts["owner"]["state"];
	$post_params_hash["personal"]["country"] = $contacts["owner"]["country"];
	$post_params_hash["personal"]["lang_pref"] = $contacts["owner"]["language"];
	
	
	return $post_params_hash;


}

/**
 * Register a domain name
 *
 * @param string $adm_login Admin login (not used)
 * @param string $adm_pass Admin password (no used)
 * @param string $domain_name Domain name to register
 * @param integer $period Length of registration period requested
 * @param array $contacts array of contacts to associate with this domain
 * @param array $dns_servers array of DNS servers to use
 * @param string $new_user Is this a new user?
 * @return array
 */
function opensrs_registry_register_domain($adm_login,$adm_pass,$domain_name,$period,$contacts,$dns_servers,$new_user){
	
	require_once ("/usr/share/dtc/shared/dtcrm/modules/opensrs/opensrs/openSRS_loader.php");
	global $handle;
        global $conf_addr_primary_dns;
        global $conf_addr_secondary_dns;
	
	//get the TLD for special 
	$dom = explode(".", $domain_name);
	$tld = strtolower($dom[1]);
	if($tld == "br"){
	$tld = "com.br";
	}
        
        if ($dns_servers[0] == "default") {
             $dns1 = $conf_addr_primary_dns;
        }else{
        $dns1 = $dns_servers[0];
        }

        if ($dns_servers[1] == "default") {
             $dns2 = $conf_addr_secondary_dns;
        }else{
        $dns2 = $dns_servers[1];
        }

	$post_params_hash3["func"] = "provSWregister";
	//data
	$post_params_hash3["data"]["domain"] = $domain_name;
	$post_params_hash3["data"]["reg_type"] = "new";
	$post_params_hash3["data"]["custom_tech_contact"] = "1";
	$post_params_hash3["data"]["custom_nameservers"] = "0";
	$post_params_hash3["data"]["reg_username"] = $adm_login;
	$post_params_hash3["data"]["reg_password"] = $adm_pass;
	$post_params_hash3["data"]["handle"] = $handle;
	$post_params_hash3["data"]["period"] = $period;
	//On the TEST system you can't change the nameservers.
	//$post_params_hash["data"]["name1"] = $dns1;
	//$post_params_hash["data"]["sortorder1"]="1";
	//$post_params_hash["data"]["name2"] = $dns2;
	//$post_params_hash["data"]["sortorder2"]="2";
	
	//special requirements for .EU, .DE, .BE
	if($tld == "eu" || $tld == "be" || $tld == "de"){
	
	$post_params_hash3["data"]["eu_country"] = $contacts["owner"]["email"];// for .EU
	$post_params_hash3["data"]["owner_confirm_address"] = $contacts["owner"]["addr1"]; //for .DE | .BE | .EU
	$post_params_hash3["data"]["lang"] = "EN"; //for .DE | .BE | .EU

	}
	//special requirements for .IT
	if($tld == "it"){
	$post_params_hash3["it_registrant_info"]["reg_code"] = "SGLMRA80A01H501E";
	$post_params_hash3["it_registrant_info"]["entity_type"] = "1"; // 1 - Italian and foreign natural persons 
	}
	
	//Personal
	$post_params_hash2 = opensrs_prepar_whois_params($contacts);
	
	$post_params_hash = array_merge($post_params_hash2, $post_params_hash3);
	
	$openSRS_results = processOpensrs("json",json_encode($post_params_hash));
	$opensrs_ret = json_decode($openSRS_results->resultFormatted, true);
	print_r($opensrs_ret);
	if($handle == "save"){
	
	if(isset($opensrs_ret['id'])){
		$ret["is_success"] = 1;
		$ret["response_text"] = "Order accepted with id:" .$opensrs_ret['id'];
		$ret["attributes"]["expiration"] = date('Y-m-d',strtotime('+1 year'));
	}else{
		$ret["is_success"] = 0;

                $ret["response_text"] = "Domain was unable to register. Something went wrong. plese contact your server administrator and send him this: ".json_encode($opensrs_ret);
	}
	
	}elseif($handle == "process"){
	if($opensrs_ret['registration_code'] == "200"){
                 $ret["is_success"] = 1;
                 $post_params_hash1["func"] = "lookupBelongsToRsp";
                 $post_params_hash1["data"]["domain"] = $domain_name;
                 $openSRS_results1 = processOpensrs("json",json_encode($post_params_hash1));
                 $opensrs_ret1 = json_decode($openSRS_results1->resultFormatted, true);

                 $ret["attributes"]["expiration"] = $opensrs_ret1["domain_expdate"];
         }else{
                 $ret["is_success"] = 0;
    
                 $ret["response_text"] = "Domain was unable to register. Something went wrong. plese contact your server administrator and send him this: ".json_encode($opensrs_ret);
         }
	
	}
	return $ret;
}

/**
 * Register a new domain name server with the top level domains
 * 
 * @param string $adm_login Admin login (not used)
 * @param string $adm_pass Admin password (not used)
 * @param string $subdomain Subdomain to register
 * @param string $domain_name Domain name on which to register the subdomain
 * @param integer $ip IP address of subdomain
 * @return array
 */
function opensrs_registry_add_nameserver($adm_login,$adm_pass,$subdomain,$domain_name,$ip){
	
	require_once ("/usr/share/dtc/shared/dtcrm/modules/opensrs/opensrs/openSRS_loader.php");
	$cookie = opensrs_cookies($domain_name);
	
	$post_params_hash["func"] = "nsCreate";
	//data
	$post_params_hash["data"]["cookie"] = $cookie;
	$post_params_hash["data"]["name"] = $subdomain.".".$domain_name;
	$post_params_hash["data"]["ipaddress"] = $ip;
	$post_params_hash["data"]["add_to_all_registry"] = "1";
	
	$openSRS_results = processOpensrs("json",json_encode($post_params_hash));
	$opensrs_ret = json_decode($openSRS_results->resultFormatted, true);
        if($opensrs_ret['response_code'] == "200"){

                $ret["is_success"] = 1;
        }else{
                $ret["is_success"] = 0;
                $ret["response_text"] = $opensrs_ret['response_text'];
        }
        return $ret;
	
}

/**
 * Update an existing domain name server with the top level domains
 *
 * @param string $adm_login Admin login (not used)
 * @param string $adm_pass Admin password (not used)
 * @param string $subdomain Subdomain to register
 * @param string $domain_name Domain name on which to register the subdomain
 * @param integer $ip IP address of subdomain
 * @return array
 */
function opensrs_registry_modify_nameserver($adm_login,$adm_pass,$subdomain,$domain_name,$ip){

	require_once ("/usr/share/dtc/shared/dtcrm/modules/opensrs/opensrs/openSRS_loader.php");
	$cookie = opensrs_cookies($domain_name);
	
	$post_params_hash["func"] = "nsModify";
	//data
	$post_params_hash["data"]["cookie"] = $cookie;
	$post_params_hash["data"]["name"] = $subdomain.".".$domain_name;
	$post_params_hash["data"]["new_name"] = $subdomain.".".$domain_name;
	$post_params_hash["data"]["ipaddress"] = $ip;
	$post_params_hash["data"]["add_to_all_registry"] = "1";
	
	$openSRS_results = processOpensrs("json",json_encode($post_params_hash));
	$opensrs_ret = json_decode($openSRS_results->resultFormatted, true);
        if($opensrs_ret['response_code'] == "200"){

                $ret["is_success"] = 1;
        }else{
                $ret["is_success"] = 0;
                $ret["response_text"] = $opensrs_ret['response_text'];
        }
        return $ret;
}

/**
 * Delete a domain name server from the top level domains
 *
 * @param string $adm_login Admin login (not used)
 * @param string $adm_pass Admin password (not used)
 * @param string $subdomain Subdomain to register
 * @param string $domain_name Domain name on which to register the subdomain
 * @param integer $ip IP address of subdomain
 * @return array
 */
function opensrs_registry_delete_nameserver($adm_login,$adm_pass,$subdomain,$domain_name){
    
	require_once ("/usr/share/dtc/shared/dtcrm/modules/opensrs/opensrs/openSRS_loader.php");
	$cookie = opensrs_cookies($domain_name);
	
	$post_params_hash["func"] = "nsDelete";
	//data
	$post_params_hash["data"]["cookie"] = $cookie;
	$post_params_hash["data"]["name"] = $subdomain.".".$domain_name;
	
	$openSRS_results = processOpensrs("json",json_encode($post_params_hash));
	$opensrs_ret = json_decode($openSRS_results->resultFormatted, true);
        if($opensrs_ret['response_code'] == "200"){

                $ret["is_success"] = 1;
        }else{
                $ret["is_success"] = 0;
                $ret["response_text"] = $opensrs_ret['response_text'];
        }
        return $ret;
}

/**
 * Get the WHOIS information for a given domain name
 * 
 * @param string $domain_name
 * @return array
 */
function opensrs_registry_get_whois($domain_name){

	require_once ("/usr/share/dtc/shared/dtcrm/modules/opensrs/opensrs/openSRS_loader.php");
	$post_params_hash["func"] = "lookupGetDomainsContacts";
	$post_params_hash["data"]["domain_list"] = $domain_name;
	
	$openSRS_results = processOpensrs("json",json_encode($post_params_hash));

	$replace = array(",", "{", "}", "\"", "owner", "admin", "tech", "billing", "contact_set :", "_");
	$with = array("<br>","<br>"," ", " ", "<b>owner</b>", "<b>admin</b>", "<b>tech</b>", "<b>billing</b>", " ", " ");

	$opensrs_ret = str_replace($replace, $with, $openSRS_results->resultFormatted);

	$ret["is_success"] = 1;
	$ret["response_text"] = $opensrs_ret;
	
	return $ret;
}

/** 
 * Update the WHOIS information for the given domain name
 * 
 * @param string $adm_login Admin login (not used)
 * @param string $adm_pass Admin password (not used)
 * @param string $domain_name Domain name to edit
 * @param array $contacts Array of contacts to send
 * @return array 
 */
function opensrs_registry_update_whois_info($adm_login,$adm_pass,$domain_name,$contacts){
	
	require_once ("/usr/share/dtc/shared/dtcrm/modules/opensrs/opensrs/openSRS_loader.php");
	$post_params_hash3["func"] = "provUpdateContacts";
	//data
	$post_params_hash3["data"]["domain"] = $domain_name;
	$post_params_hash3["data"]["types"] = "owner,admin,billing,tech";
	//personal
	$post_params_hash2 = opensrs_prepar_whois_params($contacts);
	$post_params_hash = array_merge($post_params_hash2, $post_params_hash3);
	
	if (find_domain_extension($domain_name) == ".eu"){
		unset($post_params_hash['personal']['org_name']);
	}
	
	$openSRS_results = processOpensrs("json",json_encode($post_params_hash));
	$opensrs_ret = json_decode($openSRS_results->resultFormatted, true);
        if($opensrs_ret['response_code'] == "200"){
                $ret["is_success"] = 1;
        }else{
                $ret["is_success"] = 0;
                $ret["response_text"] = "Something went wrong. plese contact your server administrator and send him this message: ".json_encode($opensrs_ret);
        }
        return $ret;
}

/**
 * 
 */
function opensrs_registry_update_whois_dns($adm_login,$adm_pass,$domain_name,$dns){
	require_once ("/usr/share/dtc/shared/dtcrm/modules/opensrs/opensrs/openSRS_loader.php");
        global $conf_addr_primary_dns;
        global $conf_addr_secondary_dns;

        if ($dns[0] == "default") {
             $dns1 = $conf_addr_primary_dns;
        }

        if ($dns[1] == "default") {
             $dns2 = $conf_addr_secondary_dns;
        }

        $post_params_hash["func"] = "nsAdvancedUpdt";
	//data
	$post_params_hash["data"]["domain"] = $domain_name;
	$post_params_hash["data"]["op_type"] = "assign";
	$post_params_hash["data"]["assign_ns"][0] = $dns1;
	$post_params_hash["data"]["assign_ns"][1] = $dns2;
	
	$openSRS_results = processOpensrs("json",json_encode($post_params_hash));
	$opensrs_ret = json_decode($openSRS_results->resultFormatted, true);
        if($opensrs_ret['response_code'] == "200"){
                $ret["is_success"] = 1;
        }else{
                $ret["is_success"] = 0;
                $ret["response_text"] = "Something went wrong. plese contact your server administrator and send him this message: ".json_encode($opensrs_ret);
        }
        return $ret;
}

/**
 * Check the status of the Registrar Lock on a given domain name
 *
 * @param string $domain_name
 * @return array 
 */
function opensrs_registry_check_transfer($domain_name){
        
        require_once ("/usr/share/dtc/shared/dtcrm/modules/opensrs/opensrs/openSRS_loader.php");
	$post_params_hash["func"]="transCheck";
        $post_params_hash["data"]["domain"] = $domain_name;
        $openSRS_results = processOpensrs("json",json_encode($post_params_hash));
	$opensrs_ret = json_decode($openSRS_results->resultFormatted, true);
        $ret["is_success"] = 1;
        if ( $opensrs_ret['transferrable'] == 1 ) {
              $ret["attributes"]["transferrable"] = 1;
        }else{
              $ret["attributes"]["transferrable"] = 0;
        $ret["attributes"]["reason"] = "Registrar Lock: ".$opensrs_ret['reason'];
        }
        return $ret;

}

/**
 * Renew a domain name with the registrar
 * 
 * @param string $domain_name Domain name to be renewed
 * @param integer $period Length of time to renew for
 * @return array
 */
function opensrs_registry_renew_domain($domain_name,$period){
	
	global $handle;
	require_once ("/usr/share/dtc/shared/dtcrm/modules/opensrs/opensrs/openSRS_loader.php");
	$post_params_hash1["func"] = "lookupBelongsToRsp";
	$post_params_hash1["data"]["domain"] = $domain_name;
	$openSRS_results1 = processOpensrs("json",json_encode($post_params_hash1));
	$opensrs_ret1 = json_decode($openSRS_results1->resultFormatted, true);
	
	$expdate = explode("-",$opensrs_ret1["domain_expdate"]);
	$expirationyear = $expdate[0];
        echo $expirationyear;
        $post_params_hash2["func"] = "provRenew";
	//data
	$post_params_hash2["data"]["domain"] = $domain_name;
	$post_params_hash2["data"]["auto_renew"] = "0";
	$post_params_hash2["data"]["currentexpirationyear"] = $expirationyear;
	$post_params_hash2["data"]["handle"] = $handle;
	$post_params_hash2["data"]["period"] = $period;
	$post_params_hash2["data"]["affiliate_id"] = ""; //optional
	$post_params_hash2["data"]["f_parkp"] = "";  //optional
        $openSRS_results2 = processOpensrs("json",json_encode($post_params_hash2));
	$opensrs_ret2 = json_decode($openSRS_results2->resultFormatted, true);
        
        if($handle == "save"){
        
        if(isset($opensrs_ret2['order_id'])){
              $ret["is_success"] = 1;
              $ret["response_text"] = "Order accepted with id:" .$opensrs_ret['order_id'];
        }else{
              $ret["is_success"] = 0;

	      $ret["response_text"] = "Something went wrong. plese contact your server administrator and send him this message: ".json_encode($opensrs_ret2);
        }
        
        }elseif($handle == "process"){
        
        if(isset($opensrs_ret2['registration expiration date'])){
               $ret["is_success"] = 1;
        }else{
               $ret["is_success"] = 0;

               $ret["response_text"] = "Something went wrong. plese contact your server administrator and send him this message: ".json_encode($opensrs_ret2);
             }

        }
        return $ret;

}

/**
 *
 */
function opensrs_registry_change_password($adm_login,$adm_pass,$domain_name,$new_pass){
}


/**
 * Get the transfer auth code for the given domain from the registrar
 * 
 * @param string $domain_name Domain name for request
 * @return array
 */
function opensrs_registry_get_auth_code($domain_name){

	require_once ("/usr/share/dtc/shared/dtcrm/modules/opensrs/opensrs/openSRS_loader.php");
	$post_params_hash["func"]="authSendAuthcode";
        $post_params_hash["data"]["domain_name"] = $domain_name;
        $openSRS_results = processOpensrs("json",json_encode($post_params_hash));
	$opensrs_ret = json_decode($openSRS_results->resultFormatted, true);
	
        $ret["is_success"] = 1;
        $ret["response_text"] = $opensrs_ret['response_text'];

        return $ret;
}

/**
 * Set the domain name transfer protection status
 * 
 * @param string $domain_name Domain name to set protection level on
 * @param string $sel Protection status to set
 * @return array
 */
function opensrs_registry_set_domain_protection($domain_name,$sel) {
        
        require_once ("/usr/share/dtc/shared/dtcrm/modules/opensrs/opensrs/openSRS_loader.php");
        
        $cookie = opensrs_cookies($domain_name);
        $post_params_hash["func"]="provModify";
        $post_params_hash["data"]["domain_name"] = $domain_name;
	$post_params_hash["data"]["data"] = "status";
        $post_params_hash["data"]["affect_domains"] = "1";
        $post_params_hash["data"]["cookie"] = $cookie;
        
        switch($sel){
             case "unlocked":
                $post_params_hash["data"]["lock_state"] = "0";
                break;
             case "transferprot":
                $post_params_hash["data"]["lock_state"] = "1";
                break;
             default:
             case "locked":
                $post_params_hash["data"]["lock_state"] = "1";
                break;
        }
	$openSRS_results = processOpensrs("json",json_encode($post_params_hash));
	$opensrs_ret = json_decode($openSRS_results->resultFormatted, true);
	$ret["is_success"] = 1;
        $ret["response_text"] = $opensrs_ret['response_text'];
        return $ret;

}

/**
 * Transfer a domain name from an existing registrar to this one
 *
 * @param string $adm_login
 * @param string $adm_pass
 * @param string $domain_name
 * @param array $contacts
 * @param array $dns_servers
 * @param string $new_user
 * @return array
 */
function opensrs_registry_transfer_domain($adm_login,$adm_pass,$domain_name,$contacts,$dns_servers,$new_user,$authcode) {
	
	require_once ("/usr/share/dtc/shared/dtcrm/modules/opensrs/opensrs/openSRS_loader.php");
        global $handle;
        global $conf_addr_primary_dns;
        global $conf_addr_secondary_dns;
	
	//get the TLD for special 
	$dom = explode(".", $domain_name);
	$tld = strtolower($dom[1]);
	if($tld == "br"){
	$tld = "com.br";
	}
        
        if ($dns_servers[0] == "default") {
             $dns1 = $conf_addr_primary_dns;
        }else{
        $dns1 = $dns_servers[0];
        }

        if ($dns_servers[1] == "default") {
             $dns2 = $conf_addr_secondary_dns;
        }else{
        $dns2 = $dns_servers[1];
        }

	$post_params_hash3["func"] = "provSWregister";
	//data
	$post_params_hash3["data"]["domain"] = $domain_name;
	$post_params_hash3["data"]["reg_type"] = "transfer";
	$post_params_hash3["data"]["custom_tech_contact"] = "1";
	$post_params_hash3["data"]["custom_nameservers"] = "0";
	$post_params_hash3["data"]["reg_username"] = $adm_login;
	$post_params_hash3["data"]["reg_password"] = $adm_pass;
	$post_params_hash3["data"]["handle"] = $handle;
	$post_params_hash3["data"]["period"] = "1";
	//Can't modify nameservers on TEST system
	//$post_params_hash["data"]["name1"] = $dns1;
	//$post_params_hash["data"]["sortorder1"]="1";
	//$post_params_hash["data"]["name2"] = $dns2;
	//$post_params_hash["data"]["sortorder2"]="2";
	
	//special requirements for .EU, .DE, .BE
	if($tld == "eu" || $tld == "be" || $tld == "de"){
	
	$post_params_hash3["data"]["eu_country"] = $contacts["owner"]["country"];// for .EU
	$post_params_hash3["data"]["owner_confirm_address"] = $contacts["owner"]["email"]; //for .DE | .BE | .EU
	$post_params_hash3["data"]["lang"] = "EN"; //for .DE | .BE | .EU

	}
	//special requirements for .IT
	if($tld == "it"){
	$post_params_hash3["it_registrant_info"]["reg_code"] = "SGLMRA80A01H501E";
	$post_params_hash3["it_registrant_info"]["entity_type"] = "1"; // 1 - Italian and foreign natural persons 
	}
	
	//Personal
	$post_params_hash2 = opensrs_prepar_whois_params($contacts);
	
	$post_params_hash = array_merge($post_params_hash2, $post_params_hash3);

	
	$openSRS_results = processOpensrs("json",json_encode($post_params_hash));
	$opensrs_ret = json_decode($openSRS_results->resultFormatted, true);
	
	if($handle == "save"){
	if(isset($opensrs_ret['id'])){
		$ret["is_success"] = 1;
		$ret["response_text"] = "Order accepted with id:" .$opensrs_ret['id'];
		$ret["attributes"]["expiration"] = date('Y-m-d',strtotime('+1 year'));
		$ret["attributes"]["expiration"] = $opensrs_ret1["domain_expdate"];
	}else{
		$ret["is_success"] = 0;

                $ret["response_text"] = "Domain was unable to register. Something went wrong. plese contact your server administrator and send hit this: ".json_encode($opensrs_ret);
	}
	}elseif($handle == "process"){
	
         if($opensrs_ret['registration_code'] == "200"){
                 $ret["is_success"] = 1;
                 $post_params_hash1["func"] = "lookupBelongsToRsp";
                 $post_params_hash1["data"]["domain"] = $domain_name;
                 $openSRS_results1 = processOpensrs("json",json_encode($post_params_hash1));
                 $opensrs_ret1 = json_decode($openSRS_results1->resultFormatted, true);

                 $ret["attributes"]["expiration"] = $opensrs_ret1["domain_expdate"];
         }else{
                 $ret["is_success"] = 0;

                 $ret["response_text"] = "Domain was unable to register. Something went wrong. plese contact your server administrator and send hit this: ".json_encode($opensrs_ret);
         }
	
	
	}
	return $ret;

}


$configurator = array(
	"title" => _("OpenSRS configuration"),
	"action" => "configure_opensrs_editor",
	"forward" => array("rub","sousrub"),
	"desc" => _("Use rr-n1-tor.opensrs.net for the live server, horizon.opensrs.net for the test one."),
	"cols" => array(
		"opensrs_server_url" => array(
			"legend" => _("Server address: "),
			"type" => "text",
			"size" => "20"),
		"opensrs_username" => array(
			"legend" => _("Username: "),
			"type" => "text",
			"size" => "20"),
		"opensrs_key" => array(
			"legend" => _("Private Key: "),
			"type" => "text",
			"size" => "100"),
		)
	);

$registry_api_modules[] = array(
"name" => "opensrs",
"configure_descriptor" => $configurator,
"registry_check_availability" => "opensrs_registry_check_availability",
"registry_add_nameserver" => "opensrs_registry_add_nameserver",
"registry_modify_nameserver" => "opensrs_registry_modify_nameserver",
"registry_delete_nameserver" => "opensrs_registry_delete_nameserver",
"registry_register_domain" => "opensrs_registry_register_domain",
"registry_update_whois_info" => "opensrs_registry_update_whois_info",
"registry_update_whois_dns" => "opensrs_registry_update_whois_dns",
"registry_check_transfer" => "opensrs_registry_check_transfer",
"registry_renew_domain" => "opensrs_registry_renew_domain",
"registry_change_password" => "opensrs_registry_change_password",
"registry_get_whois" => "opensrs_registry_get_whois",
"registry_get_auth_code" => "opensrs_registry_get_auth_code",
"registry_set_domain_protection" => "opensrs_registry_set_domain_protection",
"registry_transfert_domain" => "opensrs_registry_transfer_domain"
);

?>
