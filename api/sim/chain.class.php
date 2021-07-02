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
				foreach($cfg['keys'] as $key){
					$core->delKey($key);
				}
				
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