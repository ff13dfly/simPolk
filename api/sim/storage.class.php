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
				$key=$param['k'];
				
				return array(
					'success'	=>	true
				);
				break;

			case 'get':
				return array(
					'success'	=>	true
				);
				break;

			case 'set':
				$account=$param['u'];

				$atmp=$this->db->getHash($cfg['keys']['accounts'],array($account));
				$user_from=json_decode($atmp[$account],true);

				$nuxto=$this->getUXTO($user_from['uxto'],$account,$cfg['cost']['storage']);

				if(!$nuxto['avalid']){
					return array(
						'success'	=>	false,
						'message'	=>	'not enough input',
					);
				}

				$row=array(
					'key'	=>	$param['k'],
					'value'	=>	$param['v'],
					'owner'	=>	$account,
					'stamp'	=>	time(),
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
	
	private function getUXTO($uxto,$account,$amount){
		$out=array();
		$left=array();
		$count=0;
		$arr=$this->db->getHash($this->env['keys']['transaction_entry'],$uxto);
		
		foreach($arr as $hash=>$v){
			$row=json_decode($v,true);
			if($count>=$amount){
				$left[]=array('hash'=>$hash,'data'=>$row);
			}else{
				
				foreach($row['to'] as $kk=>$vv){
					if($vv['account']!=$account) continue;
					$count+=$vv['amount'];
				} 
				$out[]=array('hash'=>$hash,'data'=>$row);
			}
		}
		return array(
			'avalid'	=> $count>=$amount?true:false,
			'out'		=>	$out,
			'left'		=>	$left,
		);
	}
	
	private function setStorage(){
		
	}
	
	
	private function getKey($param){
		
	}
	
}