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
				$core->delKey($cfg['keys']['transaction_collected']);
				$core->delKey($cfg['keys']['storage_collected']);
				$core->delKey($cfg['keys']['contact_collected']);

				return array(
					'success'	=>	TRUE,
					'time'		=>	time(),
				);
				break;

			case 'current':
				
				return array(
					'success'		=>	TRUE,
					'transaction'	=>	$this->getCollected($cfg['keys']['transaction_collected']),		//collected transfer
					'storage'		=>	$this->getCollected($cfg['keys']['storage_collected']),			//collected storage
					'contact'		=>	$this->getCollected($cfg['keys']['contact_collected']),			//collected storage
					'current'		=>	$cur,
				);
				break;
				
			case 'transfer':	
				$acc_from=$param['from'];
				$acc_to=$param['to'];
				$amount=(int)$param['value'];

				//1.calc the from account uxto
				$atmp=$this->db->getHash($cfg['keys']['accounts'],array($acc_from));
				$user_from=json_decode($atmp[$acc_from],true);

				$nuxto=$this->getUXTO($user_from['uxto'],$acc_from,$amount);
				if(!$nuxto['avalid']){
					return array(
						'success'	=>	false,
						'message'	=>	'not enough input',
					);
				}
				
				//2.setup the uxto data struct
				$final=$this->calcUXTO($nuxto['out'],$acc_from,$acc_to,$amount);

				$key=$cfg['keys']['transaction_collected'];
				$core->pushList($key,json_encode($final));

				return array(
					'success'	=>TRUE,
					'count'		=>$core->lenList($key),
				);
				
				break;	
				
			default:
				
				break;
		}
	}

	private function calcUXTO($out,$from,$to,$amount){
		//echo json_encode($out).'<hr>';

		$format=$this->db->getTransactionFormat();
		$row=$format['row'];
		$fmt_from=$format['from'];
		$fmt_to=$format['to'];

		//1.calc the amount of input
		$sum=0;
		foreach($out as $k=>$v){
			//echo 'uxto['.$k.'] :'.json_encode($v).'<hr>';

			$fmt_from['hash']=$v['hash'];
			$fmt_from['type']='normal';
			$fmt_from['account']=$from;

			foreach($v['data']['to'] as $vv){
				if($vv['account']!=$from) continue;

				$fmt_from['amount']=$vv['amount'];
				$sum+=(int)$vv['amount'];
			}
			$row['from'][]=$fmt_from;	
		}

		//2.calc the amount of output
		$fmt_to['amount']=$amount;
		$fmt_to['account']=$to;
		$row['to'][]=$fmt_to;

		if($sum!==$amount){
			$fmt_to['amount']=$sum-$amount;
			$fmt_to['account']=$from;
			$row['to'][]=$fmt_to;
		}
		
		return $row;
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
		echo $key.':'.json_encode($list).'<hr>';

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