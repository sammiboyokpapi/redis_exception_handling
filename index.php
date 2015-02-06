<?php
/**
 * @author		Oluwasegun Matthew (07060514642)
 * @email		oadetimehin@terragonltd.com
*/

error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once '_config/config.php';
require_once 'model/model.php';




//if(isset($_GET['add-data'])){
	parse_csv('cds_subscriber_details.csv');
//}

function parse_csv($file_name){
	$model_call = new Model();
	$row = 1;
	if (($handle = fopen($file_name, "r")) !== FALSE) {
		while (($data = fgetcsv($handle)) !== FALSE) { //, 1000, ","
			$num = count($data);
			$redis_data = array('msisdn'=>$data[1],'content_id'=>$data['3'],'subscription_status'=>$data[9],'time_created'=>$data[6],'cpid'=>$data[5],'cds_campaign_id'=>$data[4],
			'expiry_time'=>$data[8],'user_id'=>$data[2],
			'subscription_time'=>$data[7],'referrer'=>$data[10],
			'Trial'=>$data[11],'Subscribe channel'=>$data[12],'Billing cycle start time'=>$data[13],
			'Billing cycle end time'=>$data[14],'Next charge time'=>$data[15],'Rent Status'=>$data[16]);
	#			var_dump($redis_data);		
	#if($row > 182385){
				$status = $model_call->add_doc($redis_data,4);
				if($status){
					echo $redis_data['msisdn'].': Data added successfully<br>'. PHP_EOL;
				}else{
				echo '<br>Error adding data...' . PHP_EOL;
				}//else
				//echo 'Skipped<br>';

			$row++;
		}
		fclose($handle);
	}
}



if(isset($_GET['load-data'])){
	$request_data = $model_call->load_doc(array('msisdn'=>'*','cpid'=>'*','cds_campaign_id'=>'*','expiry_time'=>'*'),Options::$query_limit,'');
	echo '<pre>';
	print_r($request_data);
	echo '</pre>';
}







?>
