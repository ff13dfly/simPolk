<?php
class Node{
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
			case 'list':
				
				return array(
					'success'	=>	TRUE,
					'node'		=>	$cfg['nodes'],
					'cur'		=>	$cur,
					'param'		=>	$param,
				);
				
				break;
				
			case 'view':	
				return $this->viewSample($param);
				break;

			case 'mining':
				//echo 'hello world';
				$res=$this->miningSimulator($param);
				if($res!=false){
					$result['success']=true;
					$result['code']=$res;
				}
				break;

			default:
				
				break;
		}
		return $result;
	}

	private function miningSimulator($param){
		$pre=$param['pre'];
		$words=$param['s'];
		$check=$param['di'];
		//strlen($param['di']);
		
		$str=$pre.'_'.$words;
		$step=$param['step'];
		$n=$param['n'];

		$start=$n*($step-1);
		for($i=$start;$i<$n+$start;$i++){
			$hash='0x'.hash('sha256',$str.'_'.$i);
			if(substr($hash, 0,strlen($check))===$check){
				return array(
					'string'	=>	$str.'_'.$i,
					'hash'		=>	$hash,
					'type'		=>	'sha256'
				);
			}
		}
		return false;
	}

	private function viewSample($param){
		return array(
			'success'	=>	TRUE,
		);
	}
}