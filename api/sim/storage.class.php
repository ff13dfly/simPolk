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
		//$acc_from=$param['from'];
		$acc_to='';
		$amount=$this->env['cost']['storage'];
		$account=$param['u'];
		$utxo=$this->db->checkUTXO($account,$amount,'storage');

		if(!$utxo['avalid']){
			return array(
				'success'	=>	false,
				'message'	=>	'not enough input',
			);
		}
		//echo json_encode($utxo).'<hr>';
		//exit();
		
		//1.处理utxo数据
		$key=$this->env['keys']['transaction_collected'];
		switch ($utxo['way']) {
			case 'collected':
				$final=$this->db->embedUTXO($utxo['row'],$utxo['index'],$account,$acc_to,$amount,'storage');
				$this->db->setList($key,$utxo['row'],json_encode($final));

				break;
			case 'more':
				$final=$this->db->calcUTXO($utxo['out'],$account,$acc_to,$amount,'storage');
				$final['stamp']=time();
				$this->db->pushList($key,json_encode($final));

				//2.1.add to collected transaction;
				
				break;
			default:
				# code...
				break;
		}

		//2.处理storage数据
		$row=array(
			'key'		=>	$param['k'],
			'value'		=>	$param['v'],
			'owner'		=>	$account,
			'signature'	=>	'',
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