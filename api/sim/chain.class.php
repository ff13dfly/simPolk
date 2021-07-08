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
			case 'config':
				return array(
					'success'	=>	TRUE,
					'data'		=>	$core->getConfig($cfg),
				);
				break;

			case 'setup':
				$ncfg=json_decode($param['s'],true);
				$core->setConfig($ncfg);
				return array(
					'success'	=>	TRUE,
				);
					break;	

			case 'current':
				return array(
					'success'		=>	TRUE,
					'pending'		=>	$cfg['pending'],
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
				$uxto=$core->checkUXTO($acc_from,$amount);
				if($uxto==false || !$uxto['avalid']){
					return array(
						'success'	=>	false,
						'message'	=>	'not enough input',
					);
				}
				
				//2.setup the uxto data struct
				$final=$core->calcUXTO($uxto['out'],$acc_from,$acc_to,$amount);
				$final['stamp']=time();

				//2.1.add to collected transaction;
				$key=$cfg['keys']['transaction_collected'];
				$core->pushList($key,json_encode($final));

				//2.2.remove input hash list

				return array(
					'success'	=>TRUE,
					'count'		=>$core->lenList($key),
				);
				
				break;

			case 'view':
				$block= $this->chainView($param['n']);
				return array(
					'success'	=>	TRUE,
					'data'		=>	json_decode($block,true),
				);
				break;

			case 'write':
				//
				$height=$this->db->freshCurrentBlock();
				return array(
					'block'	=>	$height,
					'success'	=>	TRUE,
				);
				break;	

			case 'reset':		//重置模拟的blockchain网络
				$n=$core->getKey($cfg['keys']['height']);
				foreach($cfg['keys'] as $key){
					$core->delKey($key);
				}

				//处理掉所有的block数据
				$pre=$cfg['prefix']['chain'];
				$this->clean_block((int)$n+1,$pre);
				
				return array(
					'success'	=>	TRUE,
				);
				break;

			case 'restruct':	//restruct all data

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

			case 'set':		//设置运行参数
				return array(
					'success'	=>	TRUE,
					'time'		=>	time(),
				);
				break;

			default:
				return false;
				break;
		}
		return true;
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