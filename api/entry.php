<?php
/*  definition for simulator  */
define('SHUTDOWN',	FALSE);					//shutdown switcher
define('DEBUG',		true);					//debug switcher
define('DS',		DIRECTORY_SEPARATOR);	//file separator define, to adapt different OS

/*  debug information  for simulator  */
date_default_timezone_set('Asia/Shanghai');	//timezone set
if(SHUTDOWN) exit('server is shutdown');	//shutdown the simPolk server
if(DEBUG){
	global $debug;
	$debug['ms']=microtime(true);
	$debug['redis']=0;
	ini_set("display_errors", "stderr");
	error_reporting(E_ALL);
}

/*  simulator logical  */
include 'lib'.DS.'core.class.php';
include 'lib'.DS.'simulator.class.php';
$a=Simulator::getInstance();				//instance the simulator
$cfg=include 'config.php';					//get the config from config file.
$a->setRedisConfig($cfg['redis']);			//set redis config

$ncfg=$a->getConfig($cfg);

$res=$a->autoRun($ncfg,$a);					//call the simulator entry method and get the result
$a->export($res);							//export the result