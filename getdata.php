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


$msisdn = $_GET['msisdn'];
$content = $_GET['content_id'];


$model_call = new Model();
//if(isset($_GET['load-data'])){
    $request_data = $model_call->load_doc(array('msisdn'=>$msisdn,'content_id'=>$content

'subscription_status'=>'*','cpid'=>'*'),Options::$query_limit,'');
    echo '<pre>';
        print_r($request_data);
    echo '</pre>';
//}



$after = microtime(true);
echo ($after-$before). " sec\n";
?>
