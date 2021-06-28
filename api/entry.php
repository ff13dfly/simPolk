<?php

define('SHUTDOWN',	FALSE);					//shutdown switcher
define('DEBUG',		TRUE);					//debug switcher
define('DS',		DIRECTORY_SEPARATOR);	

$cfg=include 'config.php';						//get the config from config file.

date_default_timezone_set('Asia/Shanghai');		//timezone set
if(SHUTDOWN) exit('server is shutdown');		//shutdown the simPolk server
if(DEBUG){
	global $debug;
	$debug['ms']=microtime(true);
	$debug['redis']=0;
	ini_set("display_errors", "stderr");
	error_reporting(E_ALL);
}

//include the simulator framework
include 'lib'.DS.'core.class.php';
include 'lib'.DS.'simulator.class.php';
$a=Simulator::getInstance();

//set redis config
$a->setRedisConfig($cfg['redis']);

//the simulator entry method
$res=$a->autoRun($cfg,$a);

//export the result
$a->export($res);