<?php

define('LENGTH_MAX', 256);		//simple string max length

class Simulator extends CORE{	
	private $callback=0;					//js回调的放置位置
	private $setting=array();
	private $db=null;
	
	//标准block的数据结构,供修改输出来用
	private $struct=array(
		'polkadot'	=>	array(
			'hash'		=>	'',
			'version'	=>	1,
			'pre'		=>	'',		
			'next'		=>	'',
			'index'		=>	0,
			'time'		=>	0,
			'root'		=>	'',
			'fee'		=>	1,
			'size'		=>	0,
			'reward'	=>	0,
			'creator'	=>	'',
			'in'		=>	array(),
			'out'		=>	array(),
			'contact'	=>	array(),
			'storage'	=>	array(),
			'mrkl_tree'	=>	array(),
		),
	
		'bitcoin'	=>	array(
			'hash'		=>	'',
			'version'	=>	1,
			'pre'		=>	'',		
			'next'		=>	'',
			'index'		=>	0,
			'time'		=>	0,
			'root'		=>	'',
			'fee'		=>	1,
			'size'		=>	0,
			'reward'	=>	0,
			'creator'	=>	'',
			'in'		=>	array(),
			'out'		=>	array(),
			'mrkl_tree'	=>	array(),
		),
	);
	
	
	
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
		$cur=$this->autoConfig();
		//echo json_encode($cur).'<hr>';
		
		//2.处理遗留的block生成，检查数据和区块高度
		$key_collected=$cfg['keys']['collected'];
		$height=$core->getKey($cfg['keys']['height']);
		if($core->existsKey($key_collected)){
			$data=json_decode($core->getKey($key_collected));
			//2.1.创建目标区块
			
		}
		
		//3.处理断档的block的激励
		
		
		//4.加载对应的模块进行处理
		spl_autoload_register(function($class_name) {
			$target='sim'.DS.$class_name.'.class.php';
			if(!file_exists($target)) $this->error('no such module');
		    require_once $target;
		});
		if(!class_exists($cls)) $this->error('no such module');
		
		$a=new $cls();
		return $a->task($act,$this->getParam(),$core,$cur,$cfg);
	}
	
	//自动写块，处理已经收集信息的方法
	//返回后继方法需要操作的基础配置信息
	//请求写到块和写入操作，是两次不同的请求上完成的
	
	public function autoConfig(){
		$cfg=$this->setting;
		$core=$this->db;
		$result=array();
		
		//1.检测是否已经存在开始时间;
		$key_start=$cfg['keys']['start'];
		$start=$core->getKey($key_start);
		if(!$start){
			$start=time();
			$core->setKey($key_start,$start);
		}
		$result['current_block']=ceil((time()-$start)/$cfg['speed']);
		
		//2.获取当前正在收集的数据
		$key_height=$cfg['keys']['height'];
		if($core->existsKey($key_height)){
			$height=$core->getKey($key_height);
		}else{
			$height=0;
		}
		
		if($result['current_block']>$height+1){
			for($i=$height;$i<$result['current_block'];$i++){
				$this->createBlankBlock($i);
			}
			$core->setKey($key_height,$result['current_block']);
		}
		
		//echo $height;
		//exit();
		
		//2.1.将数据写入到块里
			
		//2.2.处理响应的coin的增减
			
		//2.3.自动执行智能合约的内容		
		
		$index=$this->getServer($cfg['nodes']);
		$result['server']=$cfg['nodes'][$index];
		
		//$res=$this->createNewBlock($data,$svc,$cfg);
		
		//3.测试账号生成
		//echo $core->newAccount();
		
		return $result;
	}
	
	public function createBlankBlock($n){
		$nodes=$this->setting['nodes'];
		$svc=$nodes[rand(0, count($nodes)-1)];
		
		$data=$this->struct;
		$data['creator']=$svc['account'];
		$data['reward']=$this->setting['basecoin'];
		$data['index']=$n;
		
		$this->saveToChain($n,$data);
		return TRUE;
	}
	
	//写新块的方法，将随机选择服务器
	//$data为需要写入到块里的内容
	public function createNewBlock($data,$server,&$cfg){
		echo json_encode($data);
		echo json_encode($server);
		echo '<hr>';
	}

	private function saveToChain($n,$data){
		$key=$this->setting['prefix']['chain'].$n;
		
		//1.保存数据
		$this->db->setKey($key,json_encode($data));
		
		
		//2.更新挖到矿的user的coin量，以便后面调用
		$ukey=$this->setting['prefix']['coins'].$data['creator'];
		if(!$this->db->existsKey($ukey)) $this->db->setKey($ukey,0);
		$this->db->incKey($ukey,$data['reward']);
		
		//3.循环transfer部分的coin量，进行更新

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
	
	//按照数组进行数据重建，获取数据通过哈市get即可
	public function structData(&$list){
		foreach($list as $v){
			//1.按照transfer对交易进行hash的压栈
			
			//2.按照storage进行数据结构建立
			
			//3.按照智能合约，进行数据处理
			
		}
	}

	private function getEncryHash($data){
		if(is_array($data)){
			return hash('sha256', hash('sha256', json_encode($data), true), true);
		}
		return hash('sha256', hash('sha256',$data,true),true);
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