<?php
class Contact{
	private $struct=array(
		'owner'	=>	'account hash',

	);
	
	public function task($act,$param,&$core,$cur,$cfg){
		switch ($act) {
			case 'exec':	//contact autorun 

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