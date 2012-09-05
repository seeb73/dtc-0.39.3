<?php
require_once('DtcAdminScriptingClient.php');

$login = $_GET['login'];

$sclient = new DtcAdminScriptingClient( 'dtc.codebase.local', 'dtc', 'belealld' );

$ret = $sclient->addNewAccountRequest( 1, $login, 'testpass', 'Mustername', 'Vorname', 'yes', 'Musterfirma AG', '1111', 'muster@example.com', 2222, 3333, 'Adresse1', 'Adresse2', 'Adresse3', 9999, 'Musterstadt', 'NA', 'Notizen');
$ret .= $sclient->confirmWaitingAccount( $login );

// add another hosting product
$ret .= $sclient->AddHostingProduct('myHostingPlan', '0001-00-00', 38, 0, 0, 125,
	10, 5, 1000, 'no', 1, '', 'yes', '', '', 'yes', '', 'sbox_aufs', '');
// allow_add_domain should be 'yes' or 'no'
// leave blank the checkbox options that should have a no value: $ftp_login_flag, $restricted_ftp_path, $allow_mailing_list_edit, $allow_subdomain_edit, $pkg_install_flag

// add another domain to the current user
$ret .= $sclient->addNewDomainToUser($login, 'asdf1234', 'newdomain.com');
// the admin and password are from the user that you want to add the domain (the ones that the client has), not from the dtc administrator

// add a new email to the last domain
$ret .= $sclient->addNewEmailAccount($login, 'asdf1234', 'me', 'hello', 'asdf4321', '', '',
		'', 'SPAM', 10, 1024, '', '', '');
// leave blank the checkbox options that should have a no value: $spam_mailbox_enable $localdeliver $vacation_flag

// add a new database user
$ret .= $sclient->AddNewDatabaseUser($login, 'asdf1234', 'asd', 'asdf12345');

// add the database for the last user created
$ret .= $sclient->AddNewDatabase($login, 'asdf1234', 'mydb', $login.'-asd');


echo $ret;
