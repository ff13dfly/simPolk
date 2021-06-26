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
			default:
				
				break;
		}
	}
	
	
	public function setStorage(){
		
	}
	
	
	public function getKey($param){
		
	}
	
}