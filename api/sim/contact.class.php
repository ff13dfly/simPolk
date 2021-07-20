<?php
class Contact{
	private $env;
	private $cur;
	private $db;
	
	public function task($act,$param,&$core,$cur,$cfg){
		$this->env=$cfg;
		$this->cur=$cur;
		$this->db=$core;

		$result=array(
			'success'=>false,
		);
		switch ($act) {
			case 'exec':	//contact autorun 

				break;
			case 'add':
				$result=$this->addNewContact($param);

				break;
			case 'list':
				
				break;
			default:
				
				break;
		}
		return $result;
	}
	
	private function addNewContact($param){
		$account=$param['u'];
		$UTXO=$this->db->checkUTXO($account,$this->env['cost']['contact']);

		if(!$UTXO['avalid']){
			return array(
				'success'	=>	false,
				'message'	=>	'not enough input',
			);
		}

		$row=array(
			'content'	=>	$param['body'],
			'owner'		=>	$account,
			'signature'	=>	$UTXO['user']['sign'],
			'stamp'		=>	time(),
		);


		$key=$this->env['keys']['contact_collected'];
		$this->db->pushList($key,json_encode($row));
		return array(
			'success'	=>TRUE,
			'count'		=>$this->db->lenList($key),
		);
	}
}