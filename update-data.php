<?php
/**
* @author		Oluwasegun Matthew (07060514642)
* @email		oadetimehin@terragonltd.com
*/

error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once '_config/config.php';
require_once 'model/model.php';
$before = microtime(true);


$model_call = new Model();
$request_data = $model_call->update_doc(array('msisdn'=>'230','cpid'=>'cp-1001201412','cds_campaign_id'=>'cc-c-10012014201001201441','expiry_time'=>'2014-03-29 22:15:10'));
echo '<pre>';
print_r($request_data);
echo '</pre>';



$after = microtime(true);
echo ($after-$before). " sec\n";
?>
