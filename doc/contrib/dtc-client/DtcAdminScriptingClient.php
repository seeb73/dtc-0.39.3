<?php
require_once('./DtcScriptingRequest.php');

class DtcAdminScriptingClient extends DtcScriptingRequest {
  private $dtcadmin_host = '';

  public function __construct( $dtcadmin_host, $dtcadmin_login, $dtcadmin_pass, $verify_cert = false ) {	
	$this->dtcadmin_host = $dtcadmin_host;
        parent::__construct( 'https://'.$dtcadmin_host.'/dtcadmin/index.php?', $dtcadmin_login, $dtcadmin_pass, true, $verify_cert, "DTC Admin Scripting Client 0.1");
  }

  public function addNewAccountRequest($product_id, $login, $password, $name, $firstname, $is_company, $company_name, $vat_num, $email, $phone, $fax, $address1, $address2, $address3, $zipcode, $city, $state, $country, $notes, $domain_name = '', $domain_tld = '', $vps_server_hostname = '-1', $vps_os = 'debian' ){
	$params = array();
	$params['action'] = 'new_user_request';
	$params['product_id'] = $product_id;
	$params['domain_name'] = $domain_name;
	$params['domain_tld'] = $domain_tld;
	$params['vps_server_hostname'] = $vps_server_hostname;
	$params['vps_os'] = $vps_os;
	$params['reqadm_login'] = $login;
	$params['reqadm_pass'] = $password;
	$params['reqadm_pass2'] = $password;
	$params['familyname'] = $name;
	$params['firstname'] = $firstname;
	$params['iscomp'] = $is_company;
	$params['compname'] = $company_name;
	$params['vat_num'] = $vat_num;
	$params['email'] = $email;
	$params['phone'] = $phone;
	$params['fax'] = $fax;
	$params['address1'] = $address1;
	$params['address2'] = $address2;
	$params['address3'] = $address3;
	$params['zipcode'] = $zipcode;
	$params['city'] = $city;
	$params['state'] = $state;
	$params['country'] = $country;
	$params['custom_notes'] = $notes;
	$params['Login'] = 'Register';

	return $this->doRequest($params, 'https://'.$this->dtcadmin_host.'/dtc/new_account.php?');
  }

  public function confirmWaitingAccount( $account ) {
	return $this->doRequest(array('action' => 'valid_waiting_user', 'reqadm_id' => $account ));
  }
  
  // values from checkboxes like $ftp_login_flag, $restricted_ftp_path, $allow_mailing_list_edit, $allow_subdomain_edit, $pkg_install_flag
  // should be set to 'yes' or '' (blank value for no).
  public function AddHostingProduct($name, $period, $price_dollar, $setup_fee, $affiliate_kickback, $quota_disk, $nbr_email, 
  	$nbr_database, $bandwidth, $allow_add_domain, $max_domain, $allow_dns_and_mx_change, $ftp_login_flag, $restricted_ftp_path, 
	$allow_mailing_list_edit, $allow_subdomain_edit, $pkg_install_flag, $shared_hosting_security = 'sbox_aufs', $private = '')
  	{
	$params = array();
	$params['rub'] = 'product';	
	$params['action'] = 'hosting_product_list_shared_new';
	$params['name'] = $name;
	$params['period'] = $period;
	$params['price_dollar'] = $price_dollar;
	$params['setup_fee'] = $setup_fee;
	$params['affiliate_kickback'] = $affiliate_kickback;
	$params['quota_disk'] = $quota_disk;
	$params['nbr_email'] = $nbr_email;
	$params['nbr_database'] = $nbr_database;
	$params['bandwidth'] = $bandwidth;
	$params['max_domain'] = $max_domain;
	$params['allow_dns_and_mx_change'] = $allow_dns_and_mx_change;
	$params['ftp_login_flag'] = $ftp_login_flag;
	$params['restricted_ftp_path'] = $restricted_ftp_path;
	$params['allow_mailing_list_edit'] = $allow_mailing_list_edit;
	$params['allow_subdomain_edit'] = $allow_subdomain_edit;
	$params['pkg_install_flag'] = $pkg_install_flag;
	$params['shared_hosting_security'] = $shared_hosting_security;
	$params['private'] = $private;
	foreach($params as $k => $v)
		{
		if ($v == '')
			{
			unset($params[$k]);
			}
		}
	$params['id'] = '';
	$params['allow_add_domain'] = $allow_add_domain;
	return $this->doRequest($params);
	}
	
	// $admin and $password are taken from sql: "select adm_login, adm_pass from admin"
	public function addNewDomainToUser($admin, $adminpassword, $newdomain)
		{
		$params = array();
		$params['rub'] = 'adminedit';	
		$params['adm_login'] = $admin;
		$params['adm_pass'] = $adminpassword;
		$params['newdomain_name'] = $newdomain;
		$params['newdomain'] = 'Ok';
		return $this->doRequest($params);
		}
/*
 * <input type="hidden" name="adm_login" value="dtc"> // usuario al cual le agrego dominio
<input type="hidden" name="rub" value="adminedit">
<input type="hidden" name="adm_pass" value="326308890"> // admin password
<input type="text" name="newdomain_name" value=""> // nombre del dominio a agregar
* */

	public function addNewEmailAccount($admin, $adminpassword, $mailname, $memo, $passwd, $spam_mailbox_enable, $localdeliver,
		$vacation_flag, $spam_mailbox, $quota_size, $quota_files, $redirect1, $redirect2, $vacation_text)
		{
		$params = array();
		$params['adm_login'] = $admin;
		$params['adm_pass'] = $adminpassword;
		$params['addrlink'] = $admin.'/mailboxs';
		$params['action'] = 'pop_access_editor_new_item';
		$params['id'] = $mailname; // first part of mail account
		$params['memo'] = $memo; // Name:
		$params['passwd'] = $passwd;
		$params['spam_mailbox_enable'] = $spam_mailbox_enable;
		$params['localdeliver'] = $localdeliver;
		$params['vacation_flag'] = $vacation_flag;
		foreach($params as $k => $v)
		{
		if ($v == '')
			{
			unset($params[$k]);
			}
		}
		$params['spam_mailbox'] = $spam_mailbox;
		$params['quota_size'] = $quota_size;
		$params['quota_files'] = $quota_files;
		$params['redirect1'] = $redirect1;
		$params['redirect2'] = $redirect2;
		$params['vacation_text'] = $vacation_text;
		$params['autoinc'] = '';
		return $this->doRequest($params);
		}
/*
<form method="GET" name="pop_access_editor_new_item_frm" action="?">
<input type="hidden" name="adm_login" value="dtc">
<input type="hidden" name="adm_pass" value="655668772">
<input type="hidden" name="addrlink" value="servilink.com.ar/mailboxs">
<input type="hidden" name="action" value="pop_access_editor_new_item">
<input type="hidden" name="autoinc" value="">
 
<input name="id" value="" type="text" />@servilink.com.ar
Name:<input name="memo" value="" type="text" />
Password:<input name="passwd" value="" type="password" />
Enable SPAM filtering:<input name="spam_mailbox_enable" value="yes" checked="checked" type="checkbox" />
SPAM mailbox destination:<input name="spam_mailbox" value="SPAM" type="text" />
Mailbox quota:<input name="quota_size" value="10" type="text" />MBytes
Mailbox max files quota:<input name="quota_files" value="1024" type="text" />files
Redirection 1:<input name="redirect1" value="" type="text" />
Redirection 2: <input name="redirect2" value="" type="text" />
Deliver messages locally in INBOX: <input name="localdeliver" value="yes" checked="checked" type="checkbox" />
Check to send a bounce (vacation) message: <input name="vacation_flag" value="yes" type="checkbox" />
Bounce message content:<textarea cols="40" rows="7" name="vacation_text" />
<input src="gfx/skin/bwoup/gfx/buttons/btn_p_ok.gif" type="image" />
*/

	public function AddNewDatabaseUser($admin, $adminpassword, $dbuser, $dbpass)
		{
		$params = array();
		$params['adm_login'] = $admin;
		$params['adm_pass'] = $adminpassword;
		$params['addrlink'] = 'database';
		$params['action'] = 'add_dbuser';
		$params['dbuser'] = $dbuser;
		$params['db_pass'] = $dbpass;
		return $this->doRequest($params);
		}
	/*
	 <form method="POST" action="?">
	 <input type="hidden" name="adm_login" value="server">
		<input type="hidden" name="addrlink" value="database">
		<input type="hidden" name="adm_pass" value="747973605">
	<input type="hidden" name="action" value="add_dbuser">
	<input type="text" name="dbuser" value=""></td>
	<td><input type="text" name="db_pass" value=""></td>
	<td><input type="submit" value="Crear">
	 */
	public function AddNewDatabase($admin, $adminpassword, $dbname, $dbuser)
		{
		$params = array();
		$params['adm_login'] = $admin;
		$params['adm_pass'] = $adminpassword;
		$params['addrlink'] = 'database';
		$params['action'] = 'add_dbuser_db';
		$params['newdb_name'] = $dbuser;
		$params['dbuser'] = $dbpass;
		return $this->doRequest($params);
		}
	/*
	<form method="POST" action="?">
	<input type="hidden" name="adm_login" value="deysa.com.ar">
	<input type="hidden" name="addrlink" value="database">
	<input type="hidden" name="adm_pass" value="786452831">
	<input type="hidden" name="action" value="add_dbuser_db">
	<input type="text" name="newdb_name">
	<select name="dbuser">
			<option value="deysa.com.ar-dey">deysa.com.ar-dey</option>
	<input type="submit" value="Crear"></form>
	 */
}  
