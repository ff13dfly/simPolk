<?php
define('LENGTH_MAX', 256);		//simple string max length
class Simulator extends CORE{	
	private $callback=0;
	private $setting=array();
	private $db=null;

	private $storage=array(

	);
	//block head struct
	private $raw=array(
		'parent_hash'		=>	'',				//parent hash , used to vertify the blockchain data
		'merkle_root'		=>	'',
		'version'			=>	'simPolk 0.1',	//datastruct version
		'height'			=>	0,				//block height		
		'stamp'				=>	0,				//timestamp
		'diffcult'			=>	0,				//diffcult for server to calc hash
		'nonce'				=>	0,				//salt of the block
		'height'			=>	0,				//index of blockchain
		'root_transaction'	=>	'',				//transaction merkle root
		'root_storage'		=>	'',				//storage merkle root
		'root_contact'		=>	'',				//contact merkle root
		'merkle_transaction'=>	array(),		//merkle tree for transfer
		'merkle_storage'	=>	array(),		//merkle tree for storage
		'merkle_contact'	=>	array(),		//merkle tree for contact
		'list_transaction'	=>	array(),		//full transaction 
		'list_storage'		=>	array(),
		'list_contact'		=>	array(),
	);

	//transaction data struct
	private $uxto=array(
		'from'		=>	array(),
		'to'		=>	array(),
		'version'	=>	2021,
		'stamp'		=>	0,
	);

	private $from=array(
		'hash'			=>	'sha256_hash',			//from hash	['', merkle hash]
		'amount'		=>	0,						//from amount 
		'type'			=>	'coinbase',				//from type [coinbase, normal]
		'account'		=>	'account_hash_64byte',	//account public key
	);

	private $to=array(
		'amount'		=>	0,
		'account'		=>	'account_hash_64byte',	//account public key
	);

	/*	create the block data struct and cache to user
	@param	$n		integer 	//block number
	@param	$skip	boolean		//skip the collected rows
	*/
	private function createBlock($n,$skip=true){
		$nodes=$this->setting['nodes'];
		$svc=$nodes[rand(0, count($nodes)-1)];
		$data=$this->getCoinbaseBlock($n,$svc);		//获取带coinbase UXTO的区块数据

		
		if(!$skip){
			//$this->mergeData($data);
			//exit('<hr>have collected data');
		}

		//struct all the neccessary cache;
		$this->structRow($data);

		$this->saveToChain($n,$data);

		//clean the collected data
		if(!$skip){
			$this->cleanCollectedData();
		}
		
		return TRUE;
	}

	private function mergeData(&$row){
		$cfg=$this->setting;
		$ts=$this->getCollected($cfg['keys']['transfer_collected']);
		echo json_encode($ts);
	}

	private function cleanCollectedData(){
		$cfg=$this->setting;
		$this->delKey($cfg['keys']['transfer_collected']);
		$this->delKey($cfg['keys']['storage_collected']);
		$this->delKey($cfg['keys']['contact_collected']);
		return true;
	}

	private function structRow(&$raw){
		$cfg=$this->setting;
		$keys=$cfg['keys'];
		$pre=$cfg['prefix'];
		//1.merkle calculation
		//1.1.transfer merkle
		$mtree=array();
		foreach($raw['list_transaction'] as $k=>$v){
			$mtree[]=$this->encry(json_encode($v));
		}

		$raw['merkle_transaction']=$mtree;
		$this->merkle($mtree);
		$raw['root_transaction']=$mtree[count($mtree)-1];

		//1.2.storage merkle
		if(!empty($raw['list_storage'])){

		}
		//1.3.contact merkle
		if(!empty($raw['list_contact'])){

		}

		//2.account cache
		$as=array();
		foreach($raw['list_transaction'] as $k=>$v){
			$hash=$mtree[$k];
			//echo $hash.':<br>';

			//2.1.remove account uxto
			foreach($v['from'] as $kk=>$vv){
				if($kk==0) continue;

			}
			
			//2.2.add account uxto
			foreach($v['to'] as $vv){
				$account=$vv['account'];
				if(!isset($as[$account])){
					$list=$this->getHash($keys['accounts'],array($account));
					$as[$account]=json_decode($list[$account],true);
				}
				$as[$account]['uxto'][]=$hash;
			}

			//2.3.set uxto hash 
			$this->setHash($keys['transaction_entry'],$hash,json_encode($v));
		}

		//3.save accout data
		foreach($as as $acc=>$v){
			$this->setHash($keys['accounts'],$acc,json_encode($v));
		}
		
		//echo '<hr>';

		return $raw;
	}

	private function getCoinbaseBlock($n,$svc){
		$this->checkAccount($svc['account']);		//检查账户，并建立

		$data=$this->raw;
		$uxto=$this->uxto;

		//basecoin UXTO data struct
		$from=$this->from;
		$from['amount']=$this->setting['basecoin'];
		unset($from['hash']);
		unset($from['account']);
		$uxto['from'][]=$from;

		$to=$this->to;
		$to['amount']=$this->setting['basecoin'];
		$to['account']=$svc['account'];
		$uxto['to'][]=$to;

		$uxto['stamp']=time();

		$data['list_transaction'][]=$uxto;
		$data['height']=$n;
		return $data;
	}

	private function checkAccount($hash){
		$keys=$this->setting['keys'];
		$list=$this->db->getHash($keys['accounts'],array($hash));
		if($list[$hash]==false){
			$cls=$this->loadClass('account');
			$fmt=$cls->getAccountFormat();
			$fmt['last']=time();

			$this->setHash($keys['accounts'],$hash,json_encode($fmt));
			$this->pushList($keys['account_list'],$hash);
		}
		return true;
	}
	
	
 	public function __construct(){}
	public function __destruct(){}
	public static function getInstance(){
		return CORE::init(get_class());
	}
	
	//主入口，进行自动路由的地方
	
	//执行逻辑
	//1.获取缓存的数据（collected的交易等其他信息）;
	//2.更新height到当前(按照时间戳进行计算)，供下次访问的时候，建立区块;
	//3.把当前配置交给对应的方法进行处理;
	
	public function autoRun($cfg,&$core){
		$this->setting=$cfg;
		$this->db=$core;
		
		$this->callback=$_GET['callback'];
		if(!isset($_GET['mod']) || !isset($_GET['act'])) return $this->error('error request');
		
		$cls=$_GET['mod'];
		$act=$_GET['act'];
		
		//1.对配置进行检测，处理初始化
		$cur=$this->autoConfig();		//autoConfig里会进行跳块处理
		//2.跳块处理，检查数据和区块高度

		$key_collected=$cfg['keys']['transfer_collected'];
		$height=$core->getKey($cfg['keys']['height']);

		
		if($core->existsKey($key_collected)){
			$data=json_decode($core->getKey($key_collected));
			//2.1.创建目标区块
			$skip=false;
			$this->createBlock($height,$skip);
		}
		
		//4.加载对应的模块进行处理
		$a=$this->loadClass($cls);
		if(empty($a)) return $this->error('Failed to load class');

		return $a->task($act,$this->getParam(),$core,$cur,$cfg);
	}


	//自动加载class的方法
	private function loadClass($cls){
		spl_autoload_register(function($class_name) {
			$target='sim'.DS.$class_name.'.class.php';
			if(!file_exists($target)) return false;
		    require_once $target;
		});
		if(!class_exists($cls)) return false;
		return new $cls();
	}
	
	//自动写块，处理已经收集信息的方法
	//返回后继方法需要操作的基础配置信息
	//请求写到块和写入操作，是两次不同的请求上完成的
	
	public function autoConfig(){
		$cfg=$this->setting;
		$core=$this->db;
		$result=array();
		
		//1.check if it is the start of a simchain.
		$key_start=$cfg['keys']['start'];
		$start=$core->getKey($key_start);
		if(!$start){
			$start=time();
			$core->setKey($key_start,$start);
		}

		$curBlock=ceil((time()-$start)/$cfg['speed']);
		$result['current_block']=$curBlock;

		//2.create the blank block
		$key_height=$cfg['keys']['height'];
		if($core->existsKey($key_height)){
			$height=$core->getKey($key_height);
		}else{
			$height=0;
		}
		
		
		if($curBlock>$height+1){
			for($i=$height;$i<$curBlock;$i++){
				$this->createBlock($i);
			}
			$core->setKey($key_height,$curBlock);
		}
		
		$index=$this->getServer($cfg['nodes']);
		$result['server']=$cfg['nodes'][$index];		
		return $result;
	}

	private function getCollectedData($n){
		$cfg=$this->setting;
		$block=$this->head;
	
		//1.获取transfer的值
		$fs=$this->getCollected($cfg['keys']['transfer_collected']);
		$block['merkle_root']=empty($fs['merkle'])?false:$fs['merkle'][count($fs['merkle'])-1];

		//2.获取storage的值
		$ss=$this->getCollected($cfg['keys']['storage_collected']);
		$block['merkle_storage']=empty($ss['merkle'])?false:$ss['merkle'][count($ss['merkle'])-1];

		//3.获取constact的值
		$ts=$this->getCollected($cfg['keys']['storage_collected']);
		$block['merkle_contact']=empty($ts['merkle'])?false:$ts['merkle'][count($ts['merkle'])-1];

		$block['stamp']=time();
		$block['height']=$n;
		$block['list']=array(
			'uxto'		=>	$fs['data'],
			'storage'	=>	$ss['data'],
			'contact'	=>	$ts['data'],	
		);
		return $block;
	}

	private function saveToChain($n,$data){
		$key=$this->setting['prefix']['chain'].$n;
		$this->db->setKey($key,json_encode($data));
	}
	
	//获取写块服务器数据
	private function getServer($servers){
		$count=count($servers);
		$ball=rand(1, $count);		//random ball
		
		$result=array();
		
		//1.遍历所有的的服务器
		foreach($servers as $svc){
			$result[]=$this->pingServer($svc['url'],$count);
		}
		
		//2.过滤数据处理正确的服务器
		$ok=array();
		foreach($result as $k => $rep){
			if($rep['code']==$ball) $ok[]=$k;
		}
		
		if(empty($ok)) return $this->getServer($servers);
		
		return $ok[rand(0, count($ok)-1)];
	}
	
	//测试服务器的响应
	private function pingServer($url,$max){
		$data=array(
			'stamp'	=>	time(),
			'max'	=>	$max,
		);
		
		$res=$this->curlPost($url,$data);
		return $res;
	}
	
	/*curl方式跨域post数据的方法
	 * @param	$url	string		//请求的url地址
	 * @param	$data	array		//post的值，kv形式的
	 * @param	$toJSON	boolean		//是否强制转换结果为JSON串
	 * 
	 * */
	private function curlPost($url,$data,$toJSON=true){
		//echo $url;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
			
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		//避免https 的ssl验证
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 	false);
		curl_setopt($ch, CURLOPT_SSLVERSION, 		false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 	false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 	false);
			
		$res = curl_exec($ch);
		if($res === false) $err=curl_error($ch);
		curl_close($ch);

		if(isset($err)) return $err;
		return $toJSON?json_decode($res,TRUE):$res;
	}
	
	/* get all params to be an array
	 * 
	 * */
	private function getParam(){
		$result=array();
		foreach($_GET as $k=>$v){
			if($k=='mod' || $k=='act' || $k=='callback') continue;
			$result[$k]=$v;
		}
		return $result;
	}

	private function getEncryHash($data){
		if(is_array($data)){
			return hash('sha256', hash('sha256', json_encode($data), true), true);
		}
		return hash('sha256', hash('sha256',$data,true),true);
	}

	private function getCollected($key){
		$list=$this->db->getList($key);
		$cs=array();
		$mtree=array();
		if(!empty($list)){
			foreach($list as $v){
				$cs[]=json_decode($v,TRUE);
				$mtree[]=$this->db->encry($v);
			}
		}
		
		if(!empty($mtree)){
			$this->db->merkle($mtree);
		}
		return array(
			'data'		=>	$cs,
			'merkle'	=>	$mtree,
		);
	}

	/*******************************************************/
	/***************ajax export functions*******************/
	/*******************************************************/
	
	/*通用输出方法*/
	public function export($data){
		if(DEBUG){
			global $debug;
			$ms=microtime(true);
			$data['debug']=array(
				'start'	=>	$debug['ms'],
				'end'	=>	$ms,
				'redis'	=>  $debug['redis'],
				'cost'	=>	round($ms-$debug['ms'],6),
			);
		}
		
		if($this->callback){
			exit($this->callback.'('.json_encode($data).')');
		}else{
			exit(json_encode($data));
		}
	}
	
	public function error($msg){
		$rst=array(
			'success'=>false,
			'message'=>$msg,
		);
		$this->export($rst);
	}
}