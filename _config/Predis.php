<?php

require __DIR__.'/Predis/Autoloader.php';

class Predis {
	public $client;

	private $_key 				= false;
	private $doc 				=  false;
	private $doc_log 			= false;
	private $doc_map 			= false;
	private $doc_scan_pointer 	= false;

	function __construct($parameter){
		$this->redis_connection();
		$this->init($parameter);
	}

	protected function redis_connection(){
		Predis\Autoloader::register();

			$this->client = new Predis\Client(
				array(
					"scheme" 	=> Options::$redis_scheme,
					"host" 		=> Options::$redis_host,
					"port" 		=> Options::$redis_port,
					"database" 	=> Options::$redis_database
					)
			);
		

	try {
    $this->client->connect();
} catch (Predis\Connection\ConnectionException $exception) {
    // ...
	throw new Exception($exception->getMessage());
    exit("cannot connect to redis : " . $exception->getMessage());
}
	}

	public function init($param){
		$doc = $param['doc'];
		$this->set_key($param['key']);
		$this->doc = 'predis_'.$doc;
		$this->doc_map = 'predis_'.$doc.'_filter';
		$this->doc_log = 'predis_'.$doc.'_log';
		$this->doc_scan_pointer = 'predis_'.$doc.'_pointer';
	}

	private function set_key($key){
		$this->_key = $key;
	}

	public function info(){
		$data = $this->client->info();
		return $data;
	}

	private function get_key(){
		return $this->$_key;
	}

	public function add($data,$filter_count=3){
		$_key_value = $this->client->incr($this->_key);
		$data[$this->_key] = $_key_value;
		$status = $this->client->hmset($this->doc.':'.$this->_key.':'.$_key_value,$data);
		$count = 0;
		$filter_string = $this->doc_map;
		$this->client->rpush($this->doc_log,$_key_value);
		$this->client->rpush($filter_string.':'.$this->_key.'_'.$_key_value,$_key_value);
		foreach($data as $doc_key=>$doc_value){
			if($count < $filter_count && $doc_key != $this->_key){
				$filter_string .= ':'.$doc_key.'_'.$doc_value;
				$this->client->rpush($filter_string,$_key_value);
			}
			$count++;
		}
		return $status;
	}
public function delete_all($data){
	
	$doc_info = $this->get_doc_by_key($data);
	$db_info  = $this->info();
	$total_key = $db_info['Keyspace']['db'.Options::$redis_database]['keys'];
	
	$possible_keys = $this->client->scan(0,array('match'=>$this->generate_key_string($data).'*','count'=>$total_key));
	foreach($possible_keys[1] as $key_to_update){
		$this->delete_doc($key_to_update);
	}
	
}

	public function update($data,$condition,$filter_count = 3){
		$doc_info = $this->get_doc_by_key($condition);
		$db_info  = $this->info();
		$total_key = $db_info['Keyspace']['db'.Options::$redis_database]['keys'];

		$update_status = false;

		if(!empty($doc_info)){
			$key_string = $this->doc.':'.$this->_key.':'.$doc_info[$this->_key];
			$update_status = $this->client->hmset($key_string, $data);

			$possible_keys = $this->client->scan(0,array('match'=>$this->generate_key_string($condition).'*','count'=>$total_key));
			foreach($possible_keys[1] as $key_to_update){
				$this->delete_doc($key_to_update);
			}

			$filter_string = $this->doc_map;
			foreach($data as $key => $value)
			$condition[$key] = $value;

			$count = 0;
			foreach($condition as $doc_key => $doc_value){
				if($count < $filter_count && $doc_key != $this->_key){ //$count < $filter_count &&
					$filter_string .= ':'.$doc_key.'_'.$doc_value;
					$this->client->rpush($filter_string,$doc_info[$this->_key]);
				}
				$count++;
			}
		}

		return $update_status;
	}

	/*public function update($data,$condition,$filter_count = 3){
		$doc_info = $this->get_doc_by_key($condition);
		$update_status = false;

		if(!empty($doc_info)){
		$key_string = $this->doc.':'.$this->_key.':'.$doc_info[$this->_key];
		$update_status = $this->client->hmset($key_string, $data);
		$possible_keys = $this->scan_mulitple_key($condition);

		foreach($possible_keys as $key_to_update){
		$this->delete_doc($key_to_update);
	}

	$filter_string = $this->doc_map;
	foreach($data as $key => $value)
	$condition[$key] = $value;

	$count = 0;
	foreach($condition as $doc_key => $doc_value){
	if($count < $filter_count && $doc_key != $this->_key){
	$filter_string .= ':'.$doc_key.'_'.$doc_value;
	$this->client->rpush($filter_string,$doc_info[$this->_key]);
	}
	$count++;
	}
	}
	return $update_status;
	}
	*/


	private function generate_key_string($key_array){
		$key_string = $this->doc_map;
		foreach($key_array as $doc_key => $doc_value){
			$key_string .= ':'.$doc_key.'_'.$doc_value;
		}
		return $key_string;
	}

	public function get_doc_by_key($key_array){
		//only mearnt to return one result
		$key_string = $this->generate_key_string($key_array);
		$doc_id = $this->client->lrange($key_string,0,1);
		$doc_info = array();
		if(!empty($doc_id))
			$doc_info = $this->get_doc_by_id($doc_id[0]);
		return $doc_info;
	}

	public function get_doc_by_id($doc_id){
		$doc = $this->client->hgetall($this->doc.':'.$this->_key.':'.$doc_id);
		return $doc;
	}

	private function delete_doc($key){
		$this->client->del($key);
	}


	public function check_key($key_array){
		if($this->client->exists($this->generate_key_string($key_array)))
			return true;
		else
			return false;
	}

	public function get_multiple_doc_by_key($key_array){
		$doc_ids = $this->client->lrange($this->generate_key_string($key_array),0,-1);
		$docs = array();
		foreach($doc_ids as $doc_id){
			$docs[] = $this->get_doc_by_id($doc_id);
		}
		return $docs;
	}

	public function get_doc_by_pure_key($key_string){
		$doc_id = $this->client->lrange($key_string,0,1);
		$doc = array();
		if(!empty($doc_id))
			$doc = $this->get_doc_by_id($doc_id[0]);

		return $doc;
	}

	public function get_all_doc($condition=array(),$start = 0,$count = 0){
		if(!empty($condition)){
				$key = $this->generate_key_string($condition);
		}else{
			$key = $this->doc_log;
		}

		if(is_numeric($start) && $count > 0)
			$doc_ids = $this->client->lrange($key,$start,$start+$count);
		else
			$doc_ids = $this->client->lrange($key,0,-1);

		$docs = array();
		foreach($doc_ids as $doc_id){
			$docs[] = $this->get_doc_by_id($doc_id);
		}
		return $docs;
	}

	public function get_doc_filter($key_array=array(),$pattern="*"){
		$filter_key = $this->scan_key($key_array,$pattern);
		$filter_doc_info = array();
		if($filter_key){
			$filter_doc_info = $this->get_doc_by_pure_key($filter_key[0]);
		}
		return $filter_doc_info;
	}


	public function scan_key($key_array=array(),$pattern="*"){
		$start_point = 0;
		$key_results = array();
		$key_string = $this->doc_map;
		if(!empty($key_array))
			$key_string = $this->generate_key_string($key_array);
		$key_string .= $pattern;
		do{
			$keys = $this->client->scan($start_point,array('match'=>$key_string));
			if(empty($keys[1]) && $keys[0] != 0){
				$start_point = $keys[0];
			}else{
				$start_point = 0;
				$key_results = $keys[1];
			}
		}while($start_point != 0);
		return $key_results;
	}

	public function scan_mulitple_key($key_array=array(),$pattern="*"){
		$start_point = 0;
		$key_results = array();
		$key_string = $this->doc_map;
		if(!empty($key_array))
			$key_string = $this->generate_key_string($key_array);
		$key_string .= $pattern;
		do{
			$keys = $this->client->scan($start_point,array('match'=>$key_string));
			if(!empty($keys[1])) {
				foreach($keys[1] as $each_key_value){
					$key_results[] = $each_key_value;
				}
			}
			$start_point = $keys[0];

		}while($start_point != 0);

		return array_unique($key_results);
	}

	private function set_pointer($pointer){
		$this->client->set($this->doc_scan_pointer,$pointer);
	}

	private function get_pointer(){
		$pointer = 0;
		$pointer_value = $this->client->get($this->doc_scan_pointer);
		if($pointer_value)
			$pointer = $pointer_value;

		return $pointer;
	}


	public function get_multiple_doc_filter($key_array=array(),$count = 10 ,$pattern="*"){
		$start_point = $this->get_pointer();
		$scan_result = array();

		$key_string = $this->doc_map;
		if(!empty($key_array))
				$key_string = $this->generate_key_string($key_array);
		$key_string .= $pattern;

		$scan_keys = $this->client->scan($start_point,array('match'=>$key_string,'count'=>$count));
		$this->set_pointer($scan_keys[0]);
		foreach($scan_keys[1] as $key)
			$scan_result[] = $this->get_doc_by_pure_key($key);
		return $scan_result;
	}

	public function get_all_multiple_doc($key_array=array(),$pattern="*"){
		$scan_result = array();
		$keys = $this->scan_mulitple_key($key_array,$pattern);
		foreach($keys as $key)
			$scan_result[] = $this->get_doc_by_pure_key($key);
		return $scan_result;
	}


}
