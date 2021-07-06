<?php
class Account{
	private $env;
	private $cur;
	private $db;
	
	//account struct
	private $struct=array(
		'type'			=>	'user',
		'nickname'		=>	'',
		'last'			=>	0,
		'sign'			=>	'',
		'uxto'			=>	array(),		//hash stack
		'storage'		=>	array(),		//hash stack
		'contact'		=>	array(),		//hash stack
	);

	public function getAccountFormat(){
		return $this->struct;
	}
	
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

			case 'view':
				$account=	$param['u'];
				$list=$this->db->getHash($cfg['keys']['accounts'],array($account));
				return array(
					'success'	=>	TRUE,
					'data'		=>	json_decode($list[$account],true) ,
				);
				break;

			case 'uxto':
				$hash=	$param['hash'];

				$arr=$core->getHash($cfg['keys']['transaction_entry'],array($hash));
				if(empty($arr)) return array(
					'success'	=>	false,
					'message'	=>	'no such hash',
				);

				$uxto=json_decode($arr[$hash],true);

				return array(
					'success'	=>	TRUE,
					'data'		=>	$uxto['to'] ,
				);
				break;

			case 'list':
				$akey=$cfg['keys']['account_list'];
				
				$len=$core->lenList($akey);

				//$list=$core->rangeList($akey,$len-9,$len);
				$count=6;
				$list=$core->rangeList($akey,$len-2-$count,$len-1);

				$acs=$core->getHash($cfg['keys']['accounts'],$list);
				
				$arr=array();
				foreach($acs  as $hash => $data){
					//echo json_encode($account).'<br>';
					$arr[]=array_merge(array('account'=>$hash),json_decode($data,TRUE));
				}
				
				return array(
					'success'	=>	TRUE,
					'data'		=>	array_reverse($arr),
					'len'		=>	$len,
				);
				break;
			
			default:
				
				break;
		}
	}
	
	public function saveAccout($hash,$data,&$keys){
		//1.建立根hash
		$this->db->setHash($keys['accounts'],$hash,json_encode($data));
		
		//2.建立account的list
		$this->db->pushList($keys['account_list'],$hash);
	}
	

	/*account create method backup*/
	private function newAccount($n=64){
		$data=$this->struct;
		$data['last']=time();
		$data['sign']=$this->db->char(28,'SFXX');

		return array(
			'public_key'	=>	$this->randAccount($n),
			'data'			=>	$data,
		);
	}

	private function randAccount($n){
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