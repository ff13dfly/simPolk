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
		'creator'			=>	'',				//account who mined this block
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
		'list_transaction'	=>	array(),		//all transaction list 
		'list_storage'		=>	array(),		//all storage list 	
		'list_contact'		=>	array(),		//all contact list 
	);

	//transaction data struct
	private $uxto=array(
		'from'		=>	array(),
		'to'		=>	array(),
		'purpose'	=>	'transaction',		//[transaction,storage,contact]
		'version'	=>	2021,
		'stamp'		=>	0,
	);

	private $from=array(
		'hash'			=>	'sha256_hash',			//from hash	['', merkle hash]
		'amount'		=>	0,						//from amount 
		'type'			=>	'coinbase',				//from type [ coinbase, normal ]
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


	public function getConfig($cfg){
		$key=$cfg['keys']['setting'];
		if(!$this->existsKey($key)) return $cfg;
		$ncfg=json_decode($this->getKey($key),true);
		return $ncfg;
	}

	public function setConfig($cfg){
		$key=$cfg['keys']['setting'];
		$this->setKey($key,json_encode($cfg));
		return true;
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

	/*******************************************************/
	/***************uncategoried****************************/
	/*******************************************************/
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

	/*******************************************************/
	/***************control logic***************************/
	/*******************************************************/

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

	//自动写块，处理已经收集信息的方法
	//返回后继方法需要操作的基础配置信息
	//请求写到块和写入操作，是两次不同的请求上完成的
	
	private function autoConfig(){
		$cfg=$this->setting;
		$result=array();
		
		$status=$this->autoFillData();
		$result['current_block']=$status['current'];
		$result['block_height']=$status['height'];
		$result['chain_start']=$status['start'];
		
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
		$height=$this->existsKey($key_height)?$this->getKey($key_height):0;

		$result=array(  
			'current'	=>	$curBlock,
			'height'	=>	$height,
			'start'		=>	$start,
		);

		//skip data writing ,when simchain pending.
		if($cfg['pending']) return $result;

		//3.create the blank block
		if($curBlock>$height+1 || $curBlock==1){
			$pre=$this->setting['prefix']['chain'];
			for($i=$height;$i<$curBlock;$i++){
				$bkey=$pre.$i;
				$delta=($curBlock-$i-1)*$cfg['speed'];		//自动补块的stamp处理
				if(!$this->existsKey($bkey))$this->createBlock($i,$delta,$i!=($curBlock-1));
			}
			$this->setKey($key_height,$curBlock-1);
		}
		return $result;
	}

	/*******************************************************/
	/***************account functions***********************/
	/*******************************************************/

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
	

	private function saveAccountStatus(&$as){
		$key=$this->setting['keys']['accounts'];
		foreach($as as $acc=>$v){
			$this->setHash($key,$acc,json_encode($v));
		}
	}

	/*******************************************************/
	/***************block data struct***********************/
	/*******************************************************/

	private function createTree($list){
		$mtree=array();
		foreach($list as $k=>$v){
			$mtree[]=$this->encry(json_encode($v));
		}
		$this->merkle($mtree);
		return $mtree;
	}
	
	private function getParentHash($n){
		if($n==0) return '';
		$key=$this->setting['prefix']['chain'].($n-1);
		if(!$this->existsKey($key)) return false;

		$res=json_decode($this->getKey($key),true);
		return $res['merkle_root'];
	}

	private function saveToChain($n,$data){
		$key=$this->setting['prefix']['chain'].$n;
		$this->setKey($key,json_encode($data));
	}

	/*	create the block data struct and cache to user
	@param	$n		integer 	//block number
	@param	$skip	boolean		//skip the collected rows
	*/
	private function createBlock($n,$delta,$skip=true){
		$nodes=$this->setting['nodes'];
		$svc=$nodes[rand(0, count($nodes)-1)];
		$data=$this->getCoinbaseBlock($n,$delta,$svc);		//获取带coinbase UXTO的区块数据

		//merge the collected data to the basic data struct
		//if(!$skip){
			$this->mergeCollected($data);
		//}

		//struct all the neccessary cache;
		$this->structRow($data);
		$this->saveToChain($n,$data);
		return TRUE;
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

	/*******************************************************/
	/***************collected data functions****************/
	/*******************************************************/

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
			'contact'		=>	$this->getCollected($cfg['keys']['contact_collected']),	
		);
	}

	private function getCollected($key){
		$list=$this->getList($key);
		$cs=array();
		//$mtree=array();
		if(!empty($list)){
			foreach($list as $v){
				$cs[]=json_decode($v,TRUE);
				//$mtree[]=$this->encry($v);
			}
		}
		
		// if(!empty($mtree)){
		// 	$this->merkle($mtree);
		// }
		return array(
			'data'		=>	$cs,
			//'merkle'	=>	$mtree,
		);
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
		$cfg=$this->setting;
		$keys=$cfg['keys'];

		//1.create uxto through storage;
		$sts=$this->createUxtoFromStorage($raw['list_storage'],$raw['creator']);
		if(!empty($sts)){
			foreach($sts as $v) $raw['list_transaction'][]=$v;
		}
		
		//1.merkle calculation
		//1.1.transfer merkle
		$mtree=$this->createTree($raw['list_transaction']);
		$raw['merkle_transaction']=$mtree;
		$raw['root_transaction']=$mtree[count($mtree)-1];

		//1.2.storage merkle
		$slist=empty($raw['list_storage'])?array($this->encry($raw['height'].'_storage')):$raw['list_storage'];
		$stree=$this->createTree($slist);
		$raw['merkle_storage']=$stree;
		$raw['root_storage']=$stree[count($stree)-1];

		//1.3.contact merkle
		$clist=empty($raw['list_contact'])?array($this->encry($raw['height'].'_contact')):$raw['list_contact'];
		$ctree=$this->createTree($clist);
		$raw['root_contact']=$ctree[count($ctree)-1];

		//1.4.merkle root
		$atree=array(
			$raw['root_transaction'],
			$raw['root_storage'],
			$raw['root_contact'],
		);
		$this->merkle($atree);
		$raw['merkle_root']=$atree[count($atree)-1];

		//2.account cache
		$as=array();
		$this->structStorage($raw['list_storage'],$stree,$as);		//计算storage，进行uxto处理
		$this->structContact($raw['list_contact'],$ctree,$as);
		$this->structAccount($raw['list_transaction'],$mtree,$as);

		$raw['parent_hash']=$this->getParentHash($raw['height']);  //5.get the parent hash
		$this->saveAccountStatus($as);  //6.save accout data

		return true;
	}

	private function structAccount($list,&$mtree,&$as){
		$ekey=$this->setting['keys']['transaction_entry'];
		foreach($list as $k=>$v){
			$hash=$mtree[$k];

			//1.remove account uxto
			if($k!=0){
				foreach($v['from'] as $kk=>$vv){
					$input_hash=$vv['hash'];
					$from_account=$vv['account'];

					//get the account data and remove the input from uxto;
					if(!isset($as[$from_account])){
						$as[$from_account]=$this->checkAccount($from_account);
					}
					array_shift($as[$from_account]['uxto']);

					//remove the input hash data;
					$this->delHash($ekey,$input_hash);
				}
			}
			
			//2.add account uxto
			foreach($v['to'] as $vv){
				$account=$vv['account'];
				if(!isset($as[$account])){
					$as[$account]=$this->checkAccount($account);
				}
				$as[$account]['uxto'][]=$hash;
			}

			//3.set uxto hash 
			$this->setHash($ekey,$hash,json_encode($v));
		}
	}

	private function structStorage($list,&$mtree,&$as){
		$ekey=$this->setting['keys']['storage_entry'];
		foreach($list as $k=>$v){
			$hash=$mtree[$k];
			$account=$v['owner'];
			if(!isset($as[$account])){
				$as[$account]=$this->checkAccount($account);
			}
			$as[$account]['storage'][]=$hash;

			//echo json_encode($v).'<hr>';
			//exit();

			$this->setHash($ekey,$hash,json_encode($v));
		}
	}

	private function structContact(&$accounts){

	}

	

	/*******************************************************/
	/***************uxto cale functions*********************/
	/*******************************************************/
	private function createUxtoFromStorage($list,$miner){
		//echo json_encode($list).'<hr>';
		//echo $miner;
		$arr=array();

		$amount=$this->setting['cost']['storage'];
		foreach($list as $k=>$v){
			$owner=$v['owner'];
			$uxto=$this->checkUXTO($owner,$amount);

			$final=$this->calcUXTO($uxto['out'],$owner,$miner,$amount);
			$final['purpose']='storage';
			$arr[]=$final;
			//$final['type']='storage';
			//echo json_encode($final).'<hr>';
		}
		return $arr;
	}

	/*simulator mining 
	*/
	private function getCoinbaseBlock($n,$delta,$svc){
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

		$uxto['stamp']=time()-$delta;

		$data['list_transaction'][]=$uxto;
		$data['height']=$n;
		$data['signature']=$svc['sign'];
		$data['creator']=$svc['account'];
		$data['stamp']=time()-$delta;

		return $data;
	}

	public function checkUXTO($account,$amount){
		$atmp=$this->getHash($this->setting['keys']['accounts'],array($account));
		if(empty($atmp)) return false;
		$user_from=json_decode($atmp[$account],true);
		$uxto=$user_from['uxto'];

		$out=array();
		$left=array();
		$count=0;
		$arr=$this->getHash($this->setting['keys']['transaction_entry'],$uxto);
		
		foreach($arr as $hash=>$v){
			$row=json_decode($v,true);
			if($count>=$amount){
				$left[]=array('hash'=>$hash,'data'=>$row);
			}else{
				foreach($row['to'] as $kk=>$vv){
					if($vv['account']!=$account) continue;
					$count+=$vv['amount'];
				} 
				$out[]=array('hash'=>$hash,'data'=>$row);
			}
		}
		return array(
			'avalid'	=> $count>=$amount?true:false,
			'out'		=>	$out,
			'left'		=>	$left,
			'user'		=>	$user_from,
		);
	}

	public function calcUXTO($out,$from,$to,$amount){
		$format=$this->getTransactionFormat();
		$row=$format['row'];
		$fmt_from=$format['from'];
		$fmt_to=$format['to'];

		//1.calc the amount of input
		$sum=0;
		foreach($out as $k=>$v){
			//echo 'uxto['.$k.'] :'.json_encode($v).'<hr>';

			$fmt_from['hash']=$v['hash'];
			$fmt_from['type']='normal';
			$fmt_from['account']=$from;

			foreach($v['data']['to'] as $vv){
				if($vv['account']!=$from) continue;

				$fmt_from['amount']=$vv['amount'];
				$sum+=(int)$vv['amount'];
			}
			$row['from'][]=$fmt_from;	
		}

		//2.calc the amount of output
		$fmt_to['amount']=$amount;
		$fmt_to['account']=$to;
		$row['to'][]=$fmt_to;

		if($sum!==$amount){
			$fmt_to['amount']=$sum-$amount;
			$fmt_to['account']=$from;
			$row['to'][]=$fmt_to;
		}
		
		return $row;
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
	/*******************************************************/
	/***************other functions*************************/
	/*******************************************************/
	private function getEncryHash($data){
		if(is_array($data)){
			return hash('sha256', hash('sha256', json_encode($data), true), true);
		}
		return hash('sha256', hash('sha256',$data,true),true);
	}
}