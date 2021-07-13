<?php
class Storage{
	private $env;
	private $cur;
	private $db;

	public function task($act,$param,&$core,$cur,$cfg){
		$this->env=$cfg;
		$this->cur=$cur;
		$this->db=$core;
		
		switch ($act) {
			case 'key':
				$hash=$param['k'];
				$ekey=$cfg['keys']['storage_entry'];
				$list=$core->getHash($ekey,array($hash));
				return array(
					'success'	=>	true,
					'data'		=>	json_decode($list[$hash],true),
				);
				break;

			case 'get':
				return array(
					'success'	=>	true
				);
				break;

			case 'set':
				$account=$param['u'];
				$uxto=$core->checkUXTO($account,$cfg['cost']['storage']);

				//echo json_encode($uxto).'<hr>';
				//exit();

				if(!$uxto['avalid']){
					return array(
						'success'	=>	false,
						'message'	=>	'not enough input',
					);
				}

				$row=array(
					'key'		=>	$param['k'],
					'value'		=>	$param['v'],
					'owner'		=>	$account,
					'signature'	=>	$uxto['user']['sign'],
					'stamp'		=>	time(),
				);


				$key=$cfg['keys']['storage_collected'];
				$core->pushList($key,json_encode($row));
				return array(
					'success'	=>TRUE,
					'count'		=>$core->lenList($key),
				);
				break;
			default:
				
				break;
		}
	}
	
	
	private function setStorage(){
		
	}
	
	
	private function getKey($param){
		
	}
	
}