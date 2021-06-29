<?php
class Storage{
	
	public function task($act,$param,&$core,$cur,$cfg){
		switch ($act) {
			case 'key':
				$key=$param['k'];
				
				return $this->getStorage($k);
				break;

			case 'get':
				return $this->blockList(0,30);
				break;

			case 'set':
				$row=array(
					
				);
				$key=$cfg['keys']['collected'];
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
	
	
	private function setStorage(){
		
	}
	
	
	private function getKey($param){
		
	}
	
}