<?php
class Pallet{	
	public function task($act,$param,&$core,$cur,$cfg){
		switch ($act) {
			case 'exec':	//contact autorun 

				break;
			case 'list':
				
				return array(
					'success'	=>	TRUE,
					'list'		=>	array(),
				);
				
				break;
			default:
				
				break;
		}
	}
	
}