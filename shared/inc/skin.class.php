<?php
/**
 * @package DTC
 * @name GetSkin
 * @author Sebastian 'SeeB' Pachla <seeb@seeb.net.pl>
 * @return $skin (name)
 * @copyright GPL
 * @version $Id: $
 * $Log: $
 */

class getSkin{
    var $config_skin;
    var $conf_mysql_host;
    var $conf_mysql_login;	
    var $conf_mysql_pass;
    var $conf_mysql_db;
    var $mysql_connection;
    
    function getSkin($conf_mysql_host,$conf_mysql_login,$conf_mysql_pass,$conf_mysql_db){
		    $this->conf_mysql_host=$conf_mysql_host;
		    $this->conf_mysql_login=$conf_mysql_login;	
		    $this->conf_mysql_pass=$conf_mysql_pass;
		    $this->conf_mysql_db=$conf_mysql_db;
		    $mysql_connection = $this->connect2base();
		    $this->skin();
	    	
		if($this->connect2base() == false){
			die("Cannot connect to database !!!");
		}// end if
    }// end getskin - constructor

    function connect2base(){
		$ressource_id = mysqli_connect("$this->conf_mysql_host", "$this->conf_mysql_login", "$this->conf_mysql_pass","$this->conf_mysql_db");
		if($ressource_id == false)	return false;
		return $ressource_id;
    }// end connect2base

    function skin(){
		$query = "SELECT * FROM config WHERE 1 LIMIT 1;";
		$result = mysqli_query($mysql_connection, $query)or die("Cannot query $query !!!".mysql_error());	
		$row = mysqli_fetch_array($result);
		$this->config_skin=$row['skin'];
		return $this->config_skin; 
	}// end skin
}// end class
?>
