<?php
class Account{
	private $env;
	private $cur;
	private $db;
	
	private $struct=array(
		'type'		=>	'user',
		'nickname'	=>	'',
		'uxto'		=>	array(),
	);
	
	public function task($act,$param,&$core,$cur,$cfg){
		$this->env=$cfg;
		$this->cur=$cur;
		$this->db=$core;
			
		switch ($act) {
			case 'new':
				$account=$this->newAccount();
				$this->saveAccout($account['public_key'], $account['data'], $cfg['keys']);
				return array(
					'success'	=>	TRUE,
					'data'		=>	$account,
				);
				break;
			
			case 'list':
				$list=$core->getList($cfg['keys']['account_list']);
				$acs=$core->getHash($cfg['keys']['accounts'],$list);
				
				$arr=array();
				foreach($acs  as $hash => $data){
					//echo json_encode($account).'<br>';
					$arr[]=array_merge(array('account'=>$hash),json_decode($data,TRUE));
				}
				
				return array(
					'success'	=>	TRUE,
					'data'		=>	$arr,
				);
				break;
			
			default:
				
				break;
		}
	}
	
	private function saveAccout($hash,$data,&$keys){
		//echo json_encode($keys);
		//1.建立根hash
		$this->db->setHash($keys['accounts'],$hash,json_encode($data));
		
		//2.建立account的list
		$this->db->pushList($keys['account_list'],$hash);
		
		//$list=$this->db->getList($keys['account_list']);
		//echo json_encode($list).'<br>';
	}
	
	
	private function newAccount(){
		return array(
			'public_key'	=>	$this->getPublicKey(),
			'private_key'	=>	'',
			'data'			=>	$this->struct,
		);
	}
	
	private function getPublicKey(){
		return hash('sha256', uniqid());
	}
}