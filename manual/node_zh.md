# simPolk的模拟服务器说明
simPolk模拟链的网络，通过单一文件进行模拟，通过猜数字来模拟哪台服务器获得了记账权，实现虚拟币的产生，这是区块链运行的经济基础。
simPolk的模拟服务器有两个角色：

* 应用服务器

* 响应服务器

## 模拟服务器功能


## 配置一个模拟的私链网络

* 拷贝client/server.php到network文件夹（或者自定义的目录）。

* 配置api/config.php，找到nodes键值，增加一条数据

```php
array(
	'url'		=>	'http://localhost/simPolk/network/s_06.php',
	'account'	=>	'8ZBtjgKDgMP4MikruFcUqejToht97PTnnVQCDBUZ6GnLBtmxnduyCZaX2JG2yhK7',
	'sign'		=>	'',
),
```