<?php
class Account{
	private $env;
	private $cur;
	private $db;
	
	//account struct
	private $struct=array(
		'type'		=>	'user',
		'nickname'	=>	'',
		'uxto'		=>	array(),
	);
	
	/* router
	@param	$act	string		//$_GET['act],router key
	@param	$param	array		//params from URI
	@param	&$core	object		//link to redis db object
	@param	$cur	array		//current blockchain status, calced by simulator.class.php
	@param	$cfg	array		//the global config
	*/
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

	/*account create method backup*/
	public function newAccount2($n=64){
		$str='123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
		$len=strlen($str);
		$account='';
		for($i=0;$i<$n;$i++)$account.=substr($str,rand(0, $len-1),1);
		return $account;
	}
	
	private function getPublicKey(){
		return hash('sha256', uniqid());
	}
}