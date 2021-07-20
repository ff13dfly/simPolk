<?php
class Contact{
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
			case 'exec':	//contact autorun 

				break;
			case 'add':
				$row=array(
					'body'	=>	$param['body'],
					'owner'	=>	$param['u'],
					'stamp'	=>	time(),
				);
				$key=$cfg['keys']['contact_collected'];
				$core->pushList($key,json_encode($row));
				return array(
					'success'	=>TRUE,
					'count'		=>$core->lenList($key),
				);

				break;
			case 'list':
				$list=$this->contactList($param['p'],$param['count']);
				return array(
					'success'	=>	TRUE,
					'list'		=>	$list,
				);
				
				break;
			default:
				
				break;
		}
		return $result;
	}
	
	private function contactCreate($str){

	}
	
	private function contactList($page,$count){
		$list=array();
		for($i=0;$i<$count;$i++){
			$list[]=array(
				'hash'	=>	$this->hashContact(),
				'body'	=>	$this->contactBody()
			);
		}
		return $list;
	}

	private function hashContact(){
		return hash('sha256',uniqid());
	}

	private function contactBody($res=''){
		$max=rand(100,1000);
		for($i=0;$i<$max;$i++)$res.=chr($i%2?rand(65, 90):rand(97,122));
		return $res;
	}
}