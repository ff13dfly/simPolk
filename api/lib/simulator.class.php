<?php
define('LENGTH_MAX', 256);		//simple string max length
class Simulator extends CORE{	
	private $callback=0;
	private $setting=array();

	//block head struct
	private $raw=array(
		'parent_hash'		=>	'',				//parent hash , used to vertify the blockchain data
		'merkle_root'		=>	'',				//block merkle root or block hash
		'height'			=>	0,				//index of blockchain
		'signature'			=>	'',				//creator signature	
		'version'			=>	'simPolk 0.1',	//datastruct version	
		'stamp'				=>	0,				//timestamp
		'diffcult'			=>	0,				//diffcult for server to calc hash
		'nonce'				=>	0,				//salt of the block
		
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
		'signature'		=>	'account_signature',	//account encry
	);

	private $to=array(
		'amount'		=>	0,
		'account'		=>	'account_hash_64byte',	//account public key
	);

	public function __construct(){}
	public function __destruct(){}
	public static function getInstance(){
		return CORE::init(get_class());
	}

	/*return the basic datastruct of transaction
	*/
	public function getTransactionFormat(){
		return array(
			'row'	=>	$this->uxto,
			'from'	=>	$this->from,
			'to'	=>	$this->to,
		);
	}

	public function freshCurrentBlock(){
		$cfg=$this->setting;

		//1.获取当前已写块的数据
		$h=$this->getKey($cfg['keys']['height']);
		$n=$h-1;
		$key=$cfg['prefix']['chain'].$n;
		if(!$this->existsKey($key)){
			return false;
		}
		$res=$this->getKey($key);
		$data=json_decode($res);

		return $data;
		//2.merge进新的数据

		//3.重新写块

		return $n;
	}

	/*	create the block data struct and cache to user
	@param	$n		integer 	//block number
	@param	$skip	boolean		//skip the collected rows
	*/
	private function createBlock($n,$skip=true){
		$nodes=$this->setting['nodes'];
		$svc=$nodes[rand(0, count($nodes)-1)];
		$data=$this->getCoinbaseBlock($n,$svc);		//获取带coinbase UXTO的区块数据

		//merge the collected data to the basic data struct
		if(!$skip){
			$this->mergeCollected($data);
		}

		//struct all the neccessary cache;
		$this->structRow($data);
		$this->saveToChain($n,$data);
		return TRUE;
	}

	/* merget the collected rows to the block
	
	*/
	private function mergeCollected(&$data){
		$cds=$this->getAllCollected();
		//1.merge all data

		//merge the transaction data
		$list=$cds['transaction']['data'];
		if(!empty($list)){
			foreach($list as $k=>$v){
				$data['list_transaction'][]=$v;
			}
		}

		//merge the storage data
		$list=$cds['storage']['data'];
		if(!empty($list)){
			foreach($list as $k=>$v){
				$data['list_storage'][]=$v;
			}
		}

		//merge the contact data
		$list=$cds['contact']['data'];
		if(!empty($list)){
			foreach($list as $k=>$v){
				$data['list_contact'][]=$v;
			}
		}

		//2.clean the collected data;
		$this->cleanCollectedData();
		return true;
	}

	/* calc the block params method

	*/
	private function structRow(&$raw){
		//echo '<br>158:structing data...';

		$cfg=$this->setting;
		$keys=$cfg['keys'];
		
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

		}else{
			$mtree=array(
				$this->encry($raw['height'].'_storage'),
			);
			$this->merkle($mtree);
			$raw['root_storage']=$mtree[count($mtree)-1];
		}
		//1.3.contact merkle
		if(!empty($raw['list_contact'])){

		}else{
			$mtree=array(
				$this->encry($raw['height'].'_contact'),
			);
			$this->merkle($mtree);
			$raw['root_contact']=$mtree[count($mtree)-1];
		}

		//1.4.merkle root
		$mtree=array(
			$raw['root_transaction'],
			$raw['root_storage'],
			$raw['root_contact'],
		);
		$this->merkle($mtree);
		$raw['merkle_root']=$mtree[count($mtree)-1];

		//2.account cache
		$as=array();
		//echo '206:'.json_encode($raw['list_transaction']).'<hr>';
		foreach($raw['list_transaction'] as $k=>$v){
			$hash=$mtree[$k];

			//2.1.remove account uxto
			foreach($v['from'] as $kk=>$vv){
				if($kk==0) continue;
				//这里需要删除掉已经使用了的币
			}
			
			//2.2.add account uxto
			foreach($v['to'] as $vv){
				$account=$vv['account'];
				if(!isset($as[$account])){
					$as[$account]=$this->checkAccount($account);
				}
				$as[$account]['uxto'][]=$hash;
			}

			//2.3.set uxto hash 
			$this->setHash($keys['transaction_entry'],$hash,json_encode($v));
		}

		//3.get the parent hash
		if($raw['height']!=0){
			$key=$this->setting['prefix']['chain'].($raw['height']-1);
			if(!$this->existsKey($key))return false;

			$res=$this->getKey($key);
			$row['parent_hash']=$res['merkle_root'];
		}
		//4.save accout data
		foreach($as as $acc=>$v){
			$this->setHash($keys['accounts'],$acc,json_encode($v));
		}
		
		return true;
	}

	/*simulator mining 
	*/
	private function getCoinbaseBlock($n,$svc){
		$this->checkAccount($svc['account'],$svc['sign']);		//检查账户，并建立

		$data=$this->raw;
		$uxto=$this->uxto;

		//basecoin UXTO data struct
		$from=$this->from;
		$from['amount']=$this->setting['basecoin'];
		$from['signature']=$svc['sign'];
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
		$data['signature']=$svc['sign'];
		$data['stamp']=time();

		return $data;
	}

	/* check the account is valid, if not exsist, created it.
	*/
	private function checkAccount($hash,$sign=''){
		$keys=$this->setting['keys'];
		$list=$this->getHash($keys['accounts'],array($hash));

		if($list[$hash]==false){
			$cls=$this->loadClass('account');
			$fmt=$cls->getAccountFormat();
			$fmt['last']=time();
			$fmt['sign']=empty($sign)?$this->char(31,'U'):$sign;

			$this->setHash($keys['accounts'],$hash,json_encode($fmt));
			$this->pushList($keys['account_list'],$hash);
		}

		$list=$this->getHash($keys['accounts'],array($hash));
		return json_decode($list[$hash],true);
	}
	
	

	
	//主入口，进行自动路由的地方
	
	//执行逻辑
	//1.获取缓存的数据（collected的交易等其他信息）;
	//2.更新height到当前(按照时间戳进行计算)，供下次访问的时候，建立区块;
	//3.把当前配置交给对应的方法进行处理;
	
	public function autoRun($cfg,&$core){
		$this->setting=$cfg;
		
		$this->callback=$_GET['callback'];
		if(!isset($_GET['mod']) || !isset($_GET['act'])) return $this->error('error request');
		
		$cls=$_GET['mod'];
		$act=$_GET['act'];
		
		//1.对配置进行检测，处理初始化,跳块处理，检查数据和区块高度
		$cur=$this->autoConfig();		//autoConfig里会进行跳块处理
		
		//2.加载对应的模块进行处理
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
		$result=array();
		
		$curBlock=$this->autoFillData();
		$result['current_block']=$curBlock;
		
		$index=$this->getServer($cfg['nodes']);
		$result['server']=$cfg['nodes'][$index];		
		return $result;
	}

	private function autoFillData(){
		$cfg=$this->setting;

		//1.check if it is the start of a simchain.
		$key_start=$cfg['keys']['start'];
		$start=$this->getKey($key_start);
		if(!$start){
			$start=time();
			$this->setKey($key_start,$start);
		}

		$curBlock=ceil((time()-$start)/$cfg['speed']);
		$curBlock=$curBlock==0?1:$curBlock;					//auto start the chain by create 0 block

		$key_height=$cfg['keys']['height'];
		if($this->existsKey($key_height)){
			$height=$this->getKey($key_height);
		}else{
			$height=0;
		}

		$key=$this->setting['prefix']['chain'].'0';
		if($curBlock==1 && !$this->existsKey($key)){
			$this->createBlock(0);
		}
	
		//3.create the blank block
		if($curBlock>$height+1){
			for($i=$height;$i<$curBlock;$i++){
				$this->createBlock($i,$i!=($curBlock-1));
			}
			$this->setKey($key_height,$curBlock-1);
		}
		return $curBlock;
	}



	private function saveToChain($n,$data){
		$key=$this->setting['prefix']['chain'].$n;
		$this->setKey($key,json_encode($data));
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
		$list=$this->getList($key);
		$cs=array();
		$mtree=array();
		if(!empty($list)){
			foreach($list as $v){
				$cs[]=json_decode($v,TRUE);
				$mtree[]=$this->encry($v);
			}
		}
		
		if(!empty($mtree)){
			$this->merkle($mtree);
		}
		return array(
			'data'		=>	$cs,
			'merkle'	=>	$mtree,
		);
	}

	private function cleanCollectedData(){
		$cfg=$this->setting;
		$this->delKey($cfg['keys']['transaction_collected']);
		$this->delKey($cfg['keys']['storage_collected']);
		$this->delKey($cfg['keys']['contact_collected']);
		return true;
	}

	private function getAllCollected(){
		$cfg=$this->setting;
		return array(
			'transaction'	=>	$this->getCollected($cfg['keys']['transaction_collected']),
			'storage'		=>	$this->getCollected($cfg['keys']['storage_collected']),
			'contact'		=>	$this->getCollected($cfg['keys']['storage_collected']),	
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