<?php
class Chain{
	private $env;
	private $cur;
	private $db;
	
	//基础transfer的数据结构
	private $transfer=array(
		'from'=>array(
			'account'	=>	'public hash',
			'hash'		=>	array('hash_a','hash_b'),
			'type'		=>	'basecoin',
			'value'		=>	0,
			'stamp'		=>	0,
		),
		'to'=>array(
			'account_a'	=>	0,
			'account_b'	=>	0,
		),
	);
	
	public function task($act,$param,&$core,$cur,$cfg){
		$this->env=$cfg;
		$this->cur=$cur;
		$this->db=$core;
			
		switch ($act) {
			case 'view':
				
				return $this->chainView($param['n']);
				
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

			case 'current':
				
				return array(
					'success'	=>	TRUE,
					'transfer'	=>	$this->getCollected($cfg['keys']['transfer_collected']),		//collected transfer
					'storage'	=>	$this->getCollected($cfg['keys']['storage_collected']),			//collected storage
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
	
	private function transferTo(){
		
	}
	
	private function chainView($n){
		echo $key=$this->env['prefix']['chain'].$n;
		if(!$this->db->existsKey($key)){
			return false;
		}
		$res=$this->db->getKey($key);
		return $res;
	}
}