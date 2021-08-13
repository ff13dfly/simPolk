<?php
class Chain{
	private $env;
	private $cur;
	private $db;
	
	public function task($act,$param,&$core,$cur,$cfg){
		$this->env=$cfg;
		$this->cur=$cur;
		$this->db=$core;

		$result=array(
			'success'=>false,
		);
		switch ($act) {
			case 'config':
				$result=$this->getCurrentConfig($param);
				break;

			case 'setup':
				$result=$this->setNewConfig($param);
				break;	

			case 'current':
				$result=$this->getCurrentStatus($param);
				break;

			case 'transfer':	
				$result=$this->transferTo($param);
				break;

			case 'view':
				$res=$this->blockView($param);
				if(!empty($res)){
					$result['success']=true;
					$result['data']=$res;
				}
				break;

			case 'write':
				$result=$this->writeToChain($param);
				break;

			case 'reset':		//重置模拟的blockchain网络
				$result=$this->resetSimchain($param);
				break;

			case 'restruct':	//restruct all data

				break;

			case 'clean':		//处理保存，调用definition对key进行定义
				$result=$this->cleanCollectedData($param);
				break;

			default:
				return false;
				break;
		}
		return $result;
	}
	private function getCurrentConfig($param){
		$data=$this->db->getConfig($this->env);
		return array(
			'success'	=>	TRUE,
			'data'		=>	$data,
		);
	}

	private function setNewConfig($param){
		$ncfg=json_decode($param['s'],true);
		$this->db->setConfig($ncfg);
		return array(
			'success'	=>	TRUE,
		);
	}

	private function transferTo($param){
		$acc_from=$param['from'];
		$acc_to=$param['to'];
		$amount=(int)$param['value'];

		//1.calc the from account UTXO
		$utxo=$this->db->checkUTXO($acc_from,$amount);
		if($utxo==false || !$utxo['avalid']){
			return array(
				'success'	=>	false,
				'message'	=>	'not enough input , max : '.$utxo['amount'],
			);
		}

		$key=$this->env['keys']['transaction_collected'];
		switch ($utxo['way']) {
			case 'collected':
				$final=$this->db->embedUTXO($utxo['row'],$utxo['index'],$acc_from,$acc_to,$amount,'transaction');
				$this->db->setList($key,$utxo['row'],json_encode($final));
				return array(
					'success'	=>TRUE,
					'count'		=>$this->db->lenList($key),
				);
				break;

			case 'more':
				//2.setup the UTXO data struct
				$final=$this->db->newUTXO($utxo['out'],$acc_from,$acc_to,$amount);
				$final['stamp']=time();

				//2.1.add to collected transaction;
				$this->db->pushList($key,json_encode($final));

				//2.2.remove input hash list
				return array(
					'success'	=>TRUE,
					'count'		=>$this->db->lenList($key),
				);
				break;
				
			default:
				# code...
				break;
		}
	}

	private function getCurrentStatus($param){
		$keys=$this->env['keys'];
		return array(
			'success'		=>	TRUE,
			'pending'		=>	$this->env['pending'],
			'speed'			=>	$this->env['speed'],
			'transaction'	=>	$this->getCollected($keys['transaction_collected']),		//collected transfer
			'storage'		=>	$this->getCollected($keys['storage_collected']),			//collected storage
			'contact'		=>	$this->getCollected($keys['contract_collected']),			//collected storage
			'current'		=>	$this->cur,
		);
	}

	private function blockView($param){
		$n=$param['n'];
		$key=$this->env['prefix']['chain'].$n;
		if(!$this->db->existsKey($key)){
			return false;
		}
		$res=$this->db->getKey($key);
		return json_decode($res,true);
	}

	private function writeToChain($param){
		$height=$this->db->addBlockToChain();
		return array(
			'block'	=>	$height,
			'success'	=>	TRUE,
		);
	}

	private function cleanCollectedData($param){
		$keys=$this->env['keys'];
		$this->db->delKey($keys['transaction_collected']);
		$this->db->delKey($keys['storage_collected']);
		$this->db->delKey($keys['contact_collected']);

		return array(
			'success'	=>	TRUE,
			'time'		=>	time(),
		);
	}

	private function resetSimchain($param){
		$keys=$this->env['keys'];
		$n=$this->db->getKey($keys['height']);
		foreach($keys as $key){
			$this->db->delKey($key);
		}

		//处理掉所有的block数据
		$pre=$this->env['prefix']['chain'];
		$this->clean_block((int)$n+1,$pre);
		
		return array(
			'success'	=>	TRUE,
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
			'data'		=>	$cs,
			'merkle'	=>	$mtree,
		);
	}
	
	
}