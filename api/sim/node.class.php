<?php
class Node{
	private $env;
	private $cur;
	private $db;
	
	public function task($act,$param,&$core,$cur,$cfg){		
		$this->env=$cfg;
		$this->cur=$cur;
		$this->db=$core;
		
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

			default:
				
				break;
		}
	}

	private function viewSample($param){
		return array(
			'success'	=>	TRUE,
		);
	}
}