#!/usr/bin/env php
<?php

$START_DATE="2017-02-01";
$END_DATE="2018-02-01";
$COUNTRY="AU";

if (sizeof($argv) > 1)
{
        if ($argv[1])
        $START_DATE=$argv[1];
        if ($argv[2])
        $END_DATE=$argv[2];
        if ($argv[3])
        $COUNTRY=$argv[3];
}

if(function_exists("date_default_timezone_set") and function_exists("date_default_timezone_get"))
@date_default_timezone_set(@date_default_timezone_get());

$script_start_time = time();
$start_stamps = gmmktime(date("H"), date("i"), date("s"), date("m"), date("d"), date("Y"));
$panel_type="cronjob";

chdir(dirname(__FILE__));

require("../shared/autoSQLconfig.php"); // Our main configuration file
require_once("$dtcshared_path/dtc_lib.php");
require_once("genfiles/genfiles.php");

global $mysqli_connection;

$q = "select * from paiement,completedorders where completedorders.country_code like '%".$COUNTRY."%' and paiement.id=completedorders.payment_id and paiement.date < '".$END_DATE."' and paiement.date >= '".$START_DATE."';";
$r = mysqli_query($mysqli_connection,$q)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
$n = mysqli_num_rows($r);

if($n == 0){
        echo "Found no payments to save for this period: exiting\n";
        exit(0);
}

#echo "Found $n payments\n";
$total_payments=0;
for($i=0;$i<$n;$i++){
        $comp = mysqli_fetch_array($r);
        $q_client = "select * from admin where id_client = " . $comp["id_client"];
        $r_client = mysqli_query($mysqli_connection,$q_client)or die("Cannot query $q line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
        $n_client = mysqli_num_rows($r);
        $comp_login = "unknown";
        if ( $n_client > 0 )
        {
                $comp_client = mysqli_fetch_array($r_client);
                $comp_login = $comp_client["adm_login"];
        }

        if ($comp["product_id"]=="0")
        {
                $services = explode("|", $comp["services"]);
                $countries = explode("|", $comp["country_code"]);
                foreach ($countries as $index => $country_code)
                {
                        if ($country_code == $COUNTRY)
                        {
                                $product_paid = explode(":", $services[$index]);
                                if ($product_paid["0"] == "vps")
                                {
                                        $q2 = "select product_id from vps where vps_server_hostname='".$product_paid["1"]."' and vps_xen_name='".$product_paid["2"]."';";
                                        $r2 =  mysqli_query($mysqli_connection,$q2)or die("Cannot query $q2 line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
                                        $n2 = mysqli_num_rows($r2);
                                        for ($i2=0;$i2<$n2;$i2++)
                                        {
                                                $prod_array = mysqli_fetch_array($r2);
                                                $q3 = "select price_dollar from product where id='".$prod_array["product_id"]."';";
                                                $r3 = mysqli_query($mysqli_connection,$q3)or die("Cannot query $q3 line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
                                                $n3 = mysqli_num_rows($r3);
                                                for ($i3=0;$i3<$n3;$i3++)
                                                {
                                                        $paid_array = mysqli_fetch_array($r3);
                                                        echo $comp["date"] . "," . $comp_login . "," . ",multipay," . $product_paid["1"] . "." . $product_paid["2"] ."," . $paid_array["price_dollar"] . "\n";
                                                        $total_payments += $paid_array["price_dollar"];
                                                }
                                        }
                                }
                        }
                }
        }
        else
        {
                $services = explode("|", $comp["services"]);
                $countries = explode("|", $comp["country_code"]);
                foreach ($countries as $index => $country_code)
                {
                        if ($country_code == $COUNTRY)
                        {
                                $q3 = "select name from product where id='".$comp["product_id"]."';";
                                $r3 = mysqli_query($mysqli_connection, $q3)or die("Cannot query $q3 line ".__LINE__." file ".__FILE__." sql said: ".mysql_error());
                                $n3 = mysqli_num_rows($r3);
                                for ($i3=0;$i3<$n3;$i3++)
                                {
                                        $paid_array = mysqli_fetch_array($r3);
                                        echo $comp["date"] . "," . $comp_login . "," . $comp["domain_name"] . ",single," . $paid_array["name"] ."," . $comp["refund_amount"] . "\n";
                                }
                                $total_payments += $comp["refund_amount"];
                        }
                }
        }
}
#echo "Total payments so far: $total_payments for $COUNTRY between $START_DATE and $END_DATE\n";
?>


