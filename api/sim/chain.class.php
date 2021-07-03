<?php
class Chain{
	private $env;
	private $cur;
	private $db;
	
	public function task($act,$param,&$core,$cur,$cfg){
		$this->env=$cfg;
		$this->cur=$cur;
		$this->db=$core;
			
		switch ($act) {
			case 'view':
				
				$block= $this->chainView($param['n']);
				return array(
					'success'	=>	TRUE,
					'data'		=>	json_decode($block,true),
				);
				break;
				
			case 'reset':		//重置模拟的blockchain网络

				$n=$core->getKey($cfg['keys']['height']);
				foreach($cfg['keys'] as $key){
					$core->delKey($key);
				}

				//处理掉所有的block数据
				$pre=$cfg['prefix']['chain'];
				$this->clean_block((int)$n,$pre);
				
				return array(
					'success'	=>	TRUE,
				);
				
				break;
			case 'restruct':	//restruct all data

				break;
				
			case 'save':		//处理保存，调用definition对key进行定义
				
				break;

			case 'clean':		//处理保存，调用definition对key进行定义
				$core->delKey($cfg['keys']['transfer_collected']);
				$core->delKey($cfg['keys']['storage_collected']);
				$core->delKey($cfg['keys']['contact_collected']);

				return array(
					'success'	=>	TRUE,
					'time'		=>	time(),
				);
				break;

			case 'current':
				
				return array(
					'success'	=>	TRUE,
					'transfer'	=>	$this->getCollected($cfg['keys']['transfer_collected']),		//collected transfer
					'storage'	=>	$this->getCollected($cfg['keys']['storage_collected']),			//collected storage
					'contact'	=>	$this->getCollected($cfg['keys']['contact_collected']),			//collected storage
					'current'	=>	$cur,
				);
				break;
				
			case 'transfer':	
				$format=$core->getTransactionFormat();
				$acc_from=$param['from'];
				$acc_to=$param['to'];
				$amount=(int)$param['value'];


				echo json_encode($format).'<hr>';

				//1.calc the from account uxto

				$atmp=$this->db->getHash($cfg['keys']['accounts'],array($acc_from));
				$user_from=json_decode($atmp[$acc_from],true);

				//echo json_encode($user_from).'<hr>';

				$nuxto=$this->getUXTO($user_from['uxto'],$acc_from,$amount);
				if(!$nuxto['avalid']){
					return array(
						'success'	=>	false,
						'message'	=>	'not enough input',
					);
				}
				echo json_encode($nuxto).'<hr>';



				exit();

				$row=$this->transfer;
				$row['from']['account']=$param['from'];
				$row['from']['stamp']=time();
				
				$key=$cfg['keys']['transfer_collected'];
				
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

	private function calcUXTO(){
		
	}
	
	//check the uxto list to get the right uxto
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

	private function clean_block($n,$pre){
		for($i=0;$i<$n;$i++) $this->db->delKey($pre.$i);
		return true;
	}

	private function getCollected($key){
		$list=$this->db->getList($key);
		$cs=array();
		$mtree=array();
		foreach($list as $v){
			$cs[]=json_decode($v,TRUE);
			$mtree[]=$this->db->encry($v);
		}
		
		if(!empty($mtree)){
			$this->db->merkle($mtree);
		}
		return array(
			'data'		=>	$list,
			'merkle'	=>	$mtree,
		);
	}
	
	private function chainView($n){
		$key=$this->env['prefix']['chain'].$n;
		if(!$this->db->existsKey($key)){
			return false;
		}
		$res=$this->db->getKey($key);
		return $res;
	}
}