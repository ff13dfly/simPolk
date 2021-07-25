<?php
class Storage{
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
			case 'key':
				$result=$this->getStorageByKey($param);
				break;

			case 'get':
				break;

			case 'set':
				$result=$this->setStorage($param);
				break;
			default:
				
				break;
		}

		return $result;
	}

	private function setStorage($param){
		$account=$param['u'];
		$utxo=$this->db->checkUTXO($account,$this->env['cost']['storage'],'storage');

		if(!$utxo['avalid']){
			return array(
				'success'	=>	false,
				'message'	=>	'not enough input',
			);
		}

		$row=array(
			'key'		=>	$param['k'],
			'value'		=>	$param['v'],
			'owner'		=>	$account,
			'signature'	=>	$utxo['user']['sign'],
			'stamp'		=>	time(),
		);


		$key=$this->env['keys']['storage_collected'];
		$this->db->pushList($key,json_encode($row));
		return array(
			'success'	=>TRUE,
			'count'		=>$this->db->lenList($key),
		);
	}
	
	private function getStorageByKey($param){
		$hash=$param['k'];
		$ekey=$this->env['keys']['storage_entry'];
		$list=$this->db->getHash($ekey,array($hash));
		return array(
			'success'	=>	true,
			'data'		=>	json_decode($list[$hash],true),
		);
	}
}