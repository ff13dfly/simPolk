<?php

define('SHUTDOWN',	FALSE);
define('DEBUG',		TRUE);
define('DS',		DIRECTORY_SEPARATOR);

$cfg=include 'config.php';		//数据库配置和基础的定义

date_default_timezone_set('Asia/Shanghai');			//设置时区，不然date会按照标准日期进行计算

if(SHUTDOWN) exit('server is shutdown');
if(DEBUG){
	global $debug;
	$debug['ms']=microtime(true);
	$debug['redis']=0;
	ini_set("display_errors", "stderr");  //ini_set函数作用：为一个配置选项设置值，
	error_reporting(E_ALL);     //显示所有的错误信息
}

include 'lib'.DS.'core.class.php';
include 'lib'.DS.'simulator.class.php';

$a=Simulator::getInstance();		//初始化，后面才能调用
$a->setRedisConfig($cfg['redis']);

$res=$a->autoRun($cfg,$a);

$a->export($res);
