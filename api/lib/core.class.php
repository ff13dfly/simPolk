<?php
class CORE {
	private static $instanceMap = array();
	private $cRedis;
	private $redis_config;
	public function __clone(){trigger_error('[core]Clone is not allow!', E_USER_ERROR);}
	
	protected static function init($className) {
		
		if (!isset(self::$instanceMap[$className])){
			$object = new $className;
			if ($object instanceof CORE) self::$instanceMap[$className] = $object;
			else exit('[core]error,please check your code...');
		}
		return self::$instanceMap[$className];
	}

	public function setRedisConfig($redis){
		$this->redis_config=$redis;
	}
	
	/*  Merkle tree  */
	public function merkle(&$list,$start=0){
		$len=count($list);
		if($len==0) return FALSE;
		
		$tmp=array();
		for($i=0;$i<$len-$start;$i+=2){
			$base=$start+$i;
			$ha=$list[$base];
			$hb=isset($list[$base+1])?$list[$base+1]:$list[$base];
			$tmp[]=$this->encry($ha.$hb);
		}
		foreach($tmp as $v) $list[]=$v;
		
		if(count($tmp)==1) return TRUE;
		return $this->merkle($list,$len);
	}
	
	/* basic entry method
	@param	$str	string		//the string need to encry
	*/
	public function encry($str){
		return hash('sha256', hash('sha256', $str));
	}
	

	
	/*redis operations*/
	public function existsKey($key){
		if(!$this->cRedis)$this->redisLink();
		if(DEBUG)$this->redisCount();
		return $this->cRedis->exists($key);
	}
	
	public function expireKey($key,$time){
		if(!$this->cRedis)$this->redisLink();
		if(DEBUG)$this->redisCount();
		return $this->cRedis->expire($key,$time);
	}
	
	public function ttlKey($key){
		if(!$this->cRedis)$this->redisLink();
		if(DEBUG)$this->redisCount();
		return $this->cRedis->ttl($key);
	}
	
	/*string的redis部分*/
	public function getKey($key){
		if(!$this->cRedis)$this->redisLink();
		if(DEBUG)$this->redisCount();
		return $this->cRedis->get($key);
	}
		
	public function setKey($key,$val) {
		if(!$this->cRedis)$this->redisLink();
		if(DEBUG)$this->redisCount();
		return $this->cRedis->set($key,$val);
	}
	
	public function incKey($key,$n=1){
		if(!$this->cRedis)$this->redisLink();
		
		if($n==1){
			$this->cRedis->incr($key);
		}else{
			$this->cRedis->incrBy($key,$n);
		}
		if(DEBUG)$this->redisCount();
		return $this->cRedis->get($key);
	}
	
	public function delKey($key){
		if(!$this->cRedis)$this->redisLink();
		if(DEBUG)$this->redisCount();
		return $this->cRedis->del($key);
	}

	/*hash的redis部分*/
	public function getHash($main,$keys=array()){
		if(!$this->cRedis)$this->redisLink();
		if(DEBUG)$this->redisCount();
		if(empty($keys)) return $this->cRedis->hgetall($main);
		return $this->cRedis->hmget($main,$keys);
	}
		
	public function setHash($main,$key,$val){
		if(!$this->cRedis)$this->redisLink();
		if(DEBUG)$this->redisCount();
		return $this->cRedis->hset($main,$key,$val);
	}
	
	public function incHash($main,$key){
		if(!$this->cRedis) $this->redisLink();
		if(DEBUG)$this->redisCount();
		$this->cRedis->hincrby($main,$key,1);
		return $this->cRedis->hget($main,$key);
	}

	public function delHash($main,$key){
		if(!$this->cRedis)$this->redisLink();
		if(DEBUG)$this->redisCount();
		return $this->cRedis->hdel($main,$key);
	}
	
	/*list部分的操作*/
	public function existsList($key){
		if(!$this->cRedis)$this->redisLink();
		if(DEBUG)$this->redisCount();
		return $this->cRedis->exists($key);
	}
	
	public function delList($key){
		if(!$this->cRedis)$this->redisLink();
		if(DEBUG)$this->redisCount();
		return $this->cRedis->del($key);
	}
	
	public function pushList($key,$val){
		if(!$this->cRedis)$this->redisLink();
		if(DEBUG)$this->redisCount();
		return $this->cRedis->rpush($key,$val);
	}
	
	public function popList($key){
		if(!$this->cRedis)$this->redisLink();
		if(DEBUG)$this->redisCount();
		return $this->cRedis->rpop($key);
	}
	
	public function getList($key){
		//echo $key;
		if(!$this->cRedis)$this->redisLink();
		if(DEBUG)$this->redisCount();
		return $this->cRedis->lrange($key,0,-1);
	}

	public function rangeList($key,$start,$end){
		if(!$this->cRedis)$this->redisLink();
		if(DEBUG)$this->redisCount();
		return $this->cRedis->lrange($key,$start,$end);
	}

	
	public function indexList($key,$index){
		if(!$this->cRedis)$this->redisLink();
		if(DEBUG)$this->redisCount();
		return $this->cRedis->rindex($key,$index);
	}
	
	public function lenList($key){
		if(!$this->cRedis)$this->redisLink();
		if(DEBUG)$this->redisCount();
		return $this->cRedis->llen($key);
	}
	
	public function setList($key,$index,$val){
		if(!$this->cRedis)$this->redisLink();
		if(DEBUG)$this->redisCount();
		return $this->cRedis->lset($key,$index,$val);
	}
	
	public function removeList($key,$val){
		if(!$this->cRedis)$this->redisLink();
		if(DEBUG)$this->redisCount();
		$count=0;			//0为删除所有的
		return $this->cRedis->lrem($key,$val,$count);	
	}
	
	public function trimList($key,$start,$stop){
		if(!$this->cRedis)$this->redisLink();
		if(DEBUG)$this->redisCount();
		return $this->cRedis->lTrim($key,$start,$stop);	
	}
	
	/*redis服务器的连接*/
	private function redisLink(){
		if(!class_exists('Redis')){exit('no redis support');}
		if(!$this->cRedis){
			$redis=new Redis();
			$rcfg=$this->redis_config;
			if($redis->connect($rcfg['host'],$rcfg['port'])){
				$redis->auth($rcfg['auth']);
				$this->cRedis=$redis;
				return true;
			}else{
				return false;
			}
		}
		return true;
	}
	
	
	private function redisCount(){
		global $debug;
		$debug['redis']+=1;
	}
	
	public function char($len=30,$pre=''){
		$str='abcdefghijkmnpqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		for($i=0;$i<$len;$i++) $pre.=substr($str,rand(0, strlen($str)-1),1);
		return $pre;
	}
}