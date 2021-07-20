<?php
class Sample{
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
				
				
				break;

			default:
				
				break;
		}

		return $result;
	}
}