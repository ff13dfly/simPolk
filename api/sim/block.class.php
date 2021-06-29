<?php
define('MAX_BLOCK_LIST', 100);

class Block{
	//调用方式，
	private $definition=array(
		'block'	=>	['x','y','world'],
		
	);
	
	public function formatParam($def,$param){
		$res=array();
		$key='';
		$data='';
		$res[$key]=$data;
		return $res;
	}
	
	public function task($act,$param,&$core,$cur,$cfg){		
		
		switch ($act) {
			case 'list':
				return $this->blockList(0,30);
				break;
				
			case 'save':		//处理保存，调用definition对key进行定义
				//$data=$this->blockf(0,30);
				break;
				
			case 'current':
				
				
				break;
			
			case 'transfer':
				break;
			
			case 'update':
				break;
			
			default:
				
				break;
		}
	}
	
	private function blockList($start=0,$end=10){
		return array();
	}
	
	private function blockCurrent(){
		echo rand(3, 1236);
	}
}