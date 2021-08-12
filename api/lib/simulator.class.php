<?php
define('LENGTH_MAX', 256);		//simple string max length
class Simulator extends CORE{
	public function __construct(){}
	public function __destruct(){}
	public static function getInstance(){
		return CORE::init(get_class());
	}

	private $callback=0;
	private $setting=array();

	/*******************************************************/
	/******************data struct**************************/
	/*******************************************************/

	//block data struct, this part need to be modified as same as Polkadot's
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
	private $utxo=array(
		'from'		=>	array(),
		'to'		=>	array(),
		'version'	=>	2021,
		'stamp'		=>	0,
	);

	//utxo input data struct
	private $from=array(
		'hash'			=>	'sha256_hash',			//from hash	['', merkle hash]
		'amount'		=>	0,						//from amount 
		'type'			=>	'coinbase',				//from type [ coinbase, normal ]
		'account'		=>	'account_hash_64byte',	//account public key
		'signature'		=>	'account_signature',	//account encry
	);

	//utxo output data struct
	private $to=array(
		'amount'		=>	0,
		'account'		=>	'account_hash_64byte',	//account public key
		'purpose'		=>	'',						//[transaction,storage,contact]
	);


	/*******************************************************/
	/******************config function**********************/
	/*******************************************************/

	/* get simchain setting
	*	@param	$cfg	array	//the setting from local file ../config.php
	*
	*  return
	*  array		//setting of current simchain, and save to cache.
	*/
	public function getConfig($cfg){
		$key=$cfg['keys']['setting'];
		if(!$this->existsKey($key)) return $cfg;			//check cache to get current setting
		$ncfg=json_decode($this->getKey($key),true);
		return $ncfg;
	}

	/* set simchain setting 
	* @param	$cfg	array	//the setting need to set
	*
	* return 
	* boolean		//result of saving
	*/
	public function setConfig($cfg){
		$key=$cfg['keys']['setting'];
		if($this->setKey($key,json_encode($cfg))) return true;
		return false;
	}


	/*******************************************************/
	/******************pallet function**********************/
	/*******************************************************/

	/*	load the extend class as Polkadot's pallet
	*	@param	$cls	string		//name of the pallet need to load
	*
	*	return
	*   instance of target class
	*/
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
	/***************transaction functions*******************/
	/*******************************************************/

	/*get the basic data struct of transaction
	* @param null
	*
	* return 
	* array			//data struct of transaction
	*/
	public function getTransactionFormat(){
		return array(
			'row'	=>	$this->utxo,
			'from'	=>	$this->from,
			'to'	=>	$this->to,
		);
	}

	/*******************************************************/
	/********************control logic**********************/
	/*******************************************************/

	/*	application entry method
	*	steps:
	*	1.get the collected transaction, include storage and contract
	*	2.create blank blocks up to the start time of simchain
	*	3.router to pallet, supply neccessary parameters and instance
	*
	*	router rules:	
	*		mod=classname		//load the class file in sim/{classname}.class.php
	*		act=action			//router name for pallet, call like this : $cls->task($act,$params,$core,$cur,$cfg)
	*
	*	@param	$cfg	array					//setting of simchain
	*	@param	&$core	pointer of instance		//db engine instance
	*
	*	return
	*	array					//the result of the method of target pallet
	*/
	public function autoRun($cfg,&$core){
		$this->setting=$cfg;	//cache the setting
		
		if($_GET['callback'])$this->callback=$_GET['callback'];			//save jsonp's callback marking
		if(!isset($_GET['mod']) || !isset($_GET['act'])) return $this->error('error request');
		
		$cls=$_GET['mod'];
		$act=$_GET['act'];
		
		//1.auto config, will create blank blocks.
		$cur=$this->autoConfig();		
		
		//2.load target pallet
		$a=$this->loadClass($cls);
		if(empty($a)) return $this->error('Failed to load class');

		//3.return the result
		return $a->task($act,$this->getParam(),$core,$cur,$cfg);
	}

	/*	auto config the current operation
	*	steps:
	*	1.auto fill the blank blocks 
	*	2.get the status of current simchain
	*	3.get the right server
	*
	* @param null
	*
	* return
	*	array	//current status of the simchain
		{
			'current_block'	:	0,		//current block number of writing, simchain can be pendded, so this value is different from block height
			'block_height'	:	0,		//actual block height if simchain is not pendded
			'chain_start'	:	0,		//actual time of simchain started
			'server'		:	{},		//the server detail which will write current block
		}
	*/
	private function autoConfig(){
		$cfg=$this->setting;
		$result=array();
		
		//1.fill the blank blocks and get the basic informaion of current simchain
		$status=$this->autoFillData();		//auto add blank blocks.
		$result['current_block']=$status['current'];		
		$result['block_height']=$status['height'];
		$result['chain_start']=$status['start'];
		
		//2.get the server which have the right to write block
		//this is simple select without mining, but guessing the number 
		$index=$this->getServer($cfg['nodes']);
		$result['server']=$cfg['nodes'][$index];		
		return $result;
	}

	/* create the blank blocks (only basecoin)
	* simPolk is coded by PHP, it is not easy to create the block in time as the actual parachain
	* when simchain is called, this method will calc the block height and create the blocks which only have basecoin
	*	
	*	steps:
	*	1.calc the block height by start time and simchian block-create speed
	*	2.if simchain is not pendding, create the blocks
	*
	* 	@param null
	*	
	*	return 
	*	array		//status of simchain
	*/
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

		//skip data writing ,when simchain is pending.
		if($cfg['pending']) return $result;

		//3.create the blank block
		if($curBlock>$height+1 || $curBlock==1){
			$pre=$this->setting['prefix']['chain'];
			for($i=$height;$i<$curBlock;$i++){
				$bkey=$pre.$i;
				$delta=($curBlock-$i-1)*$cfg['speed'];		//calc the block stamp
				if(!$this->existsKey($bkey))$this->createBlock($i,$delta,$i!=($curBlock-1));
			}
			$this->setKey($key_height,$curBlock-1);
		}
		return $result;
	}

	/* get all params to be an array	
	* @param null
	*
	* return 
	* array			//key-value array of all parameters
	*/
	private function getParam(){
		$result=array();
		foreach($_GET as $k=>$v){
			if($k=='mod' || $k=='act' || $k=='callback') continue;
			$result[$k]=$v;
		}
		return $result;
	}

	/*******************************************************/
	/***************account functions***********************/
	/*******************************************************/

	/* check the account is valid, if not exsist, created it.
	*	@param	$hash		//account hash
	*	@param	$sign		//private key of account
	*
	*	return
	*	array				//details of account
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
	
	/*******************************************************/
	/***************block data struct***********************/
	/*******************************************************/
	
	/*	create the block data struct and cache to user
	*	@param	$n		integer 	//block number
	*	@param	$delta	integer		//delta from now,if simchain is pendding,use this to calc right timestamp
	*	@param	$skip	boolean		//skip the collected rows
	*
	*	return
	*	boolean		//result of block creation
	*/
	private function createBlock($n,$delta,$skip=true){
		//1.get the node
		$nodes=$this->setting['nodes'];
		$index=$this->getServer($nodes);
		$svc=$nodes[$index];
		$data=$this->getCoinbaseBlock($n,$delta,$svc);

		//2.merge the collected data to the basic data struct
		if(!$skip){
			$this->mergeCollected($data);
		}

		//3.struct the block's data
		$this->formatTransaction($data['list_transaction'],$svc);
		$this->structRow($data,$svc);
		$this->saveToChain($n,$data);
		return TRUE;
	}

	/* get the data of block only have coinbase transaction
	* 	@param	$n		integer		//block height
	*	@param	$delta	integer		//delta from now,if simchain is pendding,use this to calc right timestamp
	*	@param	$svc	array		//details of writing block node
	*
	*	return
	*	array			//data of block , only basecoin
	*/
	private function getCoinbaseBlock($n,$delta,$svc){
		//1.check the account of the node
		$this->checkAccount($svc['account'],$svc['sign']);		//检查账户，并建立
		
		//2.get the data format and fill the right value
		$data=$this->raw;
		$utxo=$this->utxo;

		//basecoin UTXO data struct
		$from=$this->from;
		$from['amount']=$this->setting['basecoin'];
		$from['signature']=$svc['sign'];
		
		unset($from['hash']);
		unset($from['account']);
		$utxo['from'][]=$from;

		$to=$this->to;
		$to['amount']=$this->setting['basecoin'];	
		$to['account']=$svc['account'];
		$to['purpose']='coinbase';
		$utxo['to'][]=$to;

		$utxo['stamp']=time()-$delta;

		$data['list_transaction'][]=$utxo;
		$data['height']=$n;
		$data['signature']=$svc['sign'];
		$data['creator']=$svc['account'];
		$data['stamp']=time()-$delta;			
		$data['nonce']=rand(1,20000);			//random number, no-scene, just simulate
		$data['diffcult']=rand(1,100);			//random number, no-scene, just simulate

		return $data;
	}

	/*	calc merkle tree of list
	*
	*	encry method is in the core.class.php, currently, like this :
	*	sha256(sha256(string))	
	* 
	*	@param	$list	array		//string array
	*	
	*	return
	*	array		//merkle tree of the list
	*/
	private function createTree($list){
		$mtree=array();
		foreach($list as $k=>$v){
			$mtree[]=$this->encry(json_encode($v));
		}
		$this->merkle($mtree);
		return $mtree;
	}
	
	/*
	
	*/
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
		return true;
	}



	private function formatTransaction(&$list,&$svc){
		foreach($list as $k=>$v){
			if(!isset($v['to']) || empty($v['to'])) continue;
			foreach($v['to'] as $kk=>$vv){
				if(!empty($vv['account'])) continue;
				$list[$k]['to'][$kk]['account']=$svc['account'];
			}
			
		}
	}

	/* add collected data write to current block
	*/
	public function freshCurrentBlock(){
		$cfg=$this->setting;

		//1.get current block data
		$n=$this->getKey($cfg['keys']['height']);
		$key=$cfg['prefix']['chain'].$n;
		if(!$this->existsKey($key)){
			return false;
		}
		$res=$this->getKey($key);
		$data=json_decode($res,true);
		//$UTXO['stamp']=time()-$delta;
		$delta=time()-$data['stamp'] +$cfg['speed'];
		$this->createBlock($n+1,$delta,false);

		$this->setKey($cfg['keys']['height'],$n+1);
		return true;
	}

	/*******************************************************/
	/******************* mining simulate *******************/
	/*******************************************************/

	/*	get a random server which have the right to write block
	*	steps:
	*	1.sent a number to the nodes, record the server guess the right number
	*	2.random select a node as the block writer
	*	
	*	@param	$servers	array		//list of node
	*	
	*	return
	*	array		//details of server
	*/
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
		
	/*	sent a number to server
	*	@param	$url	string	//target node url
	*	@param	$max	number	//the max number node can guess
	*	
	*	return
	*	array	//result from node
	* */
	private function pingServer($url,$max){
		$data=array(
			'stamp'	=>	time(),
			'max'	=>	$max,
		);
			
		$res=$this->curlPost($url,$data);
		return $res;
	}
	
	/* curl to get the node response
	 * @param	$url	string		//target node url
	 * @param	$data	array		//key-value data that will post to node
	 * @param	$toJSON	boolean		//force to json,default true
	 * 
	 * return
	 * array	//json format of node's response
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
	private function structRow(&$raw,$svc){
		//echo json_encode($raw).'<hr>';
		//2.merkle calculation	
		//2.1.transfer merkle
		$mtree=$this->createTree($raw['list_transaction']);
		$raw['merkle_transaction']=$mtree;
		$raw['root_transaction']=$mtree[count($mtree)-1];

		//2.2.storage merkle
		$slist=empty($raw['list_storage'])?array($this->encry($raw['height'].'_storage')):$raw['list_storage'];
		$stree=$this->createTree($slist);
		$raw['merkle_storage']=$stree;
		$raw['root_storage']=$stree[count($stree)-1];

		//2.3.contact merkle
		$clist=empty($raw['list_contact'])?array($this->encry($raw['height'].'_contact')):$raw['list_contact'];
		$ctree=$this->createTree($clist);
		$raw['root_contact']=$ctree[count($ctree)-1];

		//2.4.merkle root
		$atree=array(
			$raw['root_transaction'],
			$raw['root_storage'],
			$raw['root_contact'],
		);
		$this->merkle($atree);
		$raw['merkle_root']=$atree[count($atree)-1];

		//3.account cache
		$as=array();
		$this->structStorage($raw['list_storage'],$stree,$as);		//计算storage，进行UTXO处理
		$this->structContact($raw['list_contact'],$ctree,$as);
		$this->structAccount($raw['list_transaction'],$mtree,$as);

		$raw['parent_hash']=$this->getParentHash($raw['height']);  //5.get the parent hash
		$this->saveAccountStatus($as);  //6.save accout data

		return true;
	}

	private function saveAccountStatus(&$as){
		$key=$this->setting['keys']['accounts'];
		foreach($as as $acc=>$v){
			$this->setHash($key,$acc,json_encode($v));
		}
	}

	private function structAccount($list,&$mtree,&$as){
		$ekey=$this->setting['keys']['transaction_entry'];
		//echo json_encode($list).'<hr>';exit();
		//echo json_encode($mtree).'<hr>';exit();

		foreach($list as $k=>$v){
			$hash=$mtree[$k];
			//1.remove account UTXO
			if($k!=0){
				foreach($v['from'] as $kk=>$vv){
					$input_hash=$vv['hash'];
					$from_account=$vv['account'];

					//get the account data and remove the input from UTXO;
					if(!isset($as[$from_account])){
						$as[$from_account]=$this->checkAccount($from_account);
					}
					array_shift($as[$from_account]['utxo']);
				}
			}
			
			//2.add account UTXO
			foreach($v['to'] as $vv){
				$account=$vv['account'];
				if(!isset($as[$account])){
					$as[$account]=$this->checkAccount($account);
				}
				if(in_array($hash,$as[$account]['utxo'])) continue;		//skip the same hash
				$as[$account]['utxo'][]=$hash;
			}

			//3.set UTXO hash 
			$this->setHash($ekey,$hash,json_encode($v));
		}

		//exit();
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
			$this->setHash($ekey,$hash,json_encode($v));
		}
	}

	private function structContact($list,&$mtree,&$as){

	}

	

	/*******************************************************/
	/***************UTXO cale functions*********************/
	/*******************************************************/
	private function createUTXOFromStorage($list,$miner){
		$arr=array();
		$amount=$this->setting['cost']['storage'];
		foreach($list as $k=>$v){
			$owner=$v['owner'];
			$utxo=$this->checkUTXO($owner,$amount);

			$final=$this->calcUTXO($utxo['out'],$owner,$miner,$amount);
			$final['purpose']='storage';
			$arr[]=$final;
		}
		return $arr;
	}

	private function createUTXOFromContact($list,$miner){
		$arr=array();
		$amount=$this->setting['cost']['contact'];
		foreach($list as $k=>$v){
			$owner=$v['owner'];
			$utxo=$this->checkUTXO($owner,$amount);

			$final=$this->calcUTXO($utxo['out'],$owner,$miner,$amount);
			$final['purpose']='contact';
			$arr[]=$final;
		}
		return $arr;
	}



	//$list=array('transaction','storage','contact');
	private function skipUsedUTXO($utxo){
		$arr=array();
		$rows=array();		//input that will be used
		$cols=$this->getAllCollected();

		//1.先去除trasnaction里的hash
		$this->getUsedInput($cols['transaction']['data'],'transaction',$rows);
		//2.在剩余的UTXO里计算contact和storage需要的量，先计算storage
		// foreach($list as $type){
		// 	$this->getUsedInput($cols['transaction']['data'],$type,$rows);
		// }
		
		foreach($utxo as $v){
			if(in_array($v,$rows)) continue;
			$arr[]=$v;
		}
		
		return $arr;
	}

	private function getUsedInput(&$list,$type,&$rows){
		switch ($type) {
			case 'transaction':
				foreach($list as $k=>$v){
					foreach($v['from'] as $kk=>$vv){
						$rows[]=$vv['hash'];
					}
				}
				break;
			case 'contact':
				//echo json_encode($rows).'<hr>';
				foreach($list as $k=>$v){
					//add a input to the list
					echo json_encode($v);
				}
				break;
			default:
				foreach($list as $k=>$v){
					//add a input to the list
					echo json_encode($v);
				}
				break;
		}
		
		return true;
	}

	public function checkUTXO($account,$amount,$type='transaction'){
		$atmp=$this->getHash($this->setting['keys']['accounts'],array($account));
		if(empty($atmp)) return false;
		$user_from=json_decode($atmp[$account],true);
		
		//echo json_encode($user_from).'<hr>';

		//1.计算所有已经收集的交易中是否有可用的交易
		$rows=$this->calcAccountCollected($account,$amount,$type);
		if($rows!=false){
			//1.1.处理
			//echo json_encode($rows);
			$rows['avalid']=true;
			return $rows;
		}
		//2.去除不可用的utxo，判断输出

		//echo json_encode($user_from['utxo']).'<hr>';
		$utxo=$this->skipUsedUTXO($user_from['utxo']);

		//echo json_encode($UTXO).'<hr>';exit();
		if(empty($utxo)){
			return array(
				'avalid'	=>	false,
				'amount'	=>	'-1',
			);
		}

		$out=array();
		$left=array();
		$count=0;
		$arr=$this->getHash($this->setting['keys']['transaction_entry'],$utxo);
		
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
			'avalid'	=>	$count>=$amount?true:false,
			'way'		=>	'more',
			'out'		=>	$out,
			'left'		=>	$left,
			'user'		=>	$user_from,
			'amount'	=>	$count,
		);
	}

	/*	calc user whole collected
	*
	*/
	public function calcAccountCollected($account,$amount,$type='transaction'){
		$raw=$this->getAllCollected();
		if(!empty($raw['transaction']['data'])){
			foreach($raw['transaction']['data'] as $k=>$v){
				//echo json_encode($v).'<hr>';
				foreach($v['to'] as $index=>$vv){
					if($account!=$vv['account'] || $vv['amount']<$amount) continue;
					return array(
						'way'	=>	'collected',			//add action to collected
						'type'	=>	$type,			//
						'row'	=>	$k,
						'index'	=>	$index,
					);
					//echo json_encode($vv);
					//对于非交易类型，进行再次判断，看看是不是已经被用掉
					// switch ($type) {
					// 	case 'transaction':
							
					// 		break;

					// 	case 'storage':
					// 			# code...
					// 		break;

					// 	case 'contact':
					// 			# code...
					// 		break;

					// 	default:
					// 		# code...
					// 		break;
					// }
					
				}
			}
		}
		return false;
	}

	/*	calc user whole coins
	*
	*/
	public function calcAccountUTXO($utxo,$account){
		$arr=$this->getHash($this->setting['keys']['transaction_entry'],$utxo);
		$count=0;
		foreach($arr as $k =>$v){
			$row=json_decode($v,true);
			if(empty($row)) continue;
			foreach($row['to'] as $kk=>$vv){
				if($vv['account']!=$account) continue;
				$count+=$vv['amount'];
			}
		}
		return $count;
	}

	public function embedUTXO($row,$index,$account_from,$account_to,$amount,$type){
		$keys=$this->setting['keys'];
		$cs=$this->getCollected($keys['transaction_collected']);
		if(!isset($cs['data']) || !isset($cs['data'][$row]) || !isset($cs['data'][$row]['to'][$index])) return false;
		if($cs['data'][$row]['to'][$index]['account']!=$account_from) return false;

		//echo json_encode($cs['data'][$row]).'<hr>';

		//1.calc new output
		$from=$cs['data'][$row]['to'][$index];
		$from['amount']-=$amount;

		$to=$this->to;
		$to['account']=$account_to;
		$to['amount']=$amount;
		$to['purpose']=$type;

		//2.create new collected transaction
		$ts=array();
		foreach($cs['data'][$row]['to'] as $k=>$v){
			if($k==$index) continue;
			$ts[]=$v;
		}
		$ts[]=$from;
		$ts[]=$to;
		$cs['data'][$row]['to']=$ts;


		return $cs['data'][$row];
		//3.save new collected data
		//$key=$keys['transaction_collected'];
		//$this->db->pushList($key,json_encode($final));
	}

	public function calcUTXO($out,$from,$to,$amount,$purpose='transaction'){
		$format=$this->getTransactionFormat();
		$row=$format['row'];
		$fmt_from=$format['from'];
		$fmt_to=$format['to'];

		//1.calc the amount of input
		$sum=0;
		foreach($out as $k=>$v){
			//echo 'UTXO['.$k.'] :'.json_encode($v).'<hr>';

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
		$fmt_to['purpose']=$purpose;
		$row['to'][]=$fmt_to;

		if($sum!==$amount){
			$fmt_to['amount']=$sum-$amount;
			$fmt_to['account']=$from;
			$fmt_to['purpose']='transaction';
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