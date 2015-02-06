<?php
/**
 * @author		Oluwasegun Matthew (07060514642)
 * @email		oadetimehin@terragonltd.com
*/
class Model {
	private $redisModel = false;

	function __construct(){
		try{
		$this->redisModel = new Predis(array('doc'=>Options::$_subscription,'key'=>Options::$_subscription_key));
		}catch (Exception $e){
			
			throw new Exception($e->getMessage());
		}
	}

	public function add_doc($data,$filter=3){
		$status = $this->redisModel->add($data,$filter);
		return $status;
	}

	public function get_doc($param){
		return $this->redisModel->get_doc_by_key($param);
	}

	public function update_doc($data,$condition){
		$this->redisModel->update($data,$condition,4);
	}

	public function load_doc($param,$count=20,$pattern="*"){
		return $this->redisModel->get_multiple_doc_filter($param,$count,$pattern);
	}

	public function load_all_doc($param,$pattern="*"){
		return $this->redisModel->get_all_multiple_doc($param,$pattern);
	}
	
	public function delete($param){
		return $this->redisModel->delete_all($param);
	}
}
