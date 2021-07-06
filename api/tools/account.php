<?php

for($i=0;$i<10;$i++){
	echo char().'<br>';
}


function char($len=28,$pre='FAKE'){
	$str='abcdefghijkmnpqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	for($i=0;$i<$len;$i++) $pre.=substr($str,rand(0, strlen($str)-1),1);
	return $pre;
}