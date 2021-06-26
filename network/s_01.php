<?php

//1.模拟一个链节点的单页php代码

$result=array(
	'raw'=>$_POST,
	'code'=>rand(1, $_POST['max']),
);
echo json_encode($result);