<?php
	return array(
		'pending'		=>	false,			//pendding the chain status

		
		//'config_cache'	=>	'sim_confg',	//config_cache key
		//sim-chain basic setting
		'name'			=>	'STG',			//coin name
		'speed'			=>	30,				//block created speed (block/second)
		'basecoin'		=>	3000,			//reward for creating block
		'password'		=>	'123456',		//default password for simulate account
		
		'cost'			=>	array(
			'transfer'	=>	array(1,20),	//transfer cost range
			'storage'	=>	100,			//storage set cost
			'global'	=>	1000,			//global storage entry
			'contact'	=>	10,				//contact run cost
		),

		//sim-chain db setting
		'redis'			=>	array(			//redis setting
			'host'		=>	'localhost',
			'port'		=>	6379,
			'auth'		=>	'authKEY',
			'timeout'	=>	36000,
		),

		'switcher'		=>	array(
			'enable_password'	=>	false,
			'enable_'			=>	false,
		),
		
		//sim-chain db keys setting
		'keys'			=>	array(
			'start'					=>	'sim_start',		//simulator start timestamp, used to calc block number
			'height'				=>	'block_height',		//now height
			'setting'				=>	'sim_setting',		//setting 

			'accounts'				=>	'sim_accounts',		//account hashmap entry
			'account_list'			=>	'all_accounts',		//account list

			'transaction_collected'	=>	'sim_collected',	//what is collected now.
			'storage_collected'		=>	'sim_storage',		//storage map entry key
			'contact_collected'		=>	'sim_contact',		//contact map entry key
			'transaction_entry'		=>	'en_chain',			//transaction map entry name (for redis hash)
			'storage_entry'			=>	'en_storage',		//storage map entry name (for redis hash)
			'global_entry'			=>	'en_global',		//global storage can access by key
			'contact_entry'			=>	'en_contact',		//contact map entry name (for redis hash)
		),
		'prefix'		=>	array(
			'coins'		=>	'cc_',			//choins record

			'chain'		=>	'ch_',			//block number prefix
			'storage'	=>	'sg_',			//storage key prefix
			'contact'	=>	'ct_',			//storage key prefix
		),
		
		//sim-chain network setting
		'white_list'	=>	array(				//request server control
		
		),
		
		'nodes'		=>	array(
			array(
				'url'		=>	'http://localhost/simPolk/network/s_01.php',
				'account'	=>	'5r9E41L7tb8PstabiPKsJm56q8XeMqoA43Zmxbn9NwTmvKh75468McNpv2ZYTH8i',
				'sign'		=>	'FAKE4E8PbNZQIH7Vk8DnFzMyOxNIxdIV',
			),
			array(
				'url'		=>	'http://localhost/simPolk/network/s_02.php',
				'account'	=>	'GiPLyuNqog6FYypM1saRXfsJh2PDVRSH6G7gg4oYMF894SE3kAC7dhutUVFHG42T',
				'sign'		=>	'FAKEwOEVG702r55HxCP4tFz9IUCHNDsb',
			),
			array(
				'url'		=>	'http://localhost/simPolk/network/s_03.php',
				'account'	=>	'hmAaXc3K4mLTf8LdhnrmrWE92smnj4nzpf9NNaxZSkhV6gPi2SzC5Ye3VFYQBPME',
				'sign'		=>	'FAKEuBIvFupQ3nKPik2ItO8vpI8DcuWM',
			),
			array(
				'url'		=>	'http://localhost/simPolk/network/s_04.php',
				'account'	=>	'tYRDbF6M2HUtmJ4bcPc1Sr7LyykN6WtBwAus5bWmhmdEhEYH5hXjsJ4HteQNKFLN',
				'sign'		=>	'FAKEefM25JMOTnQgUirdisQSAOqiu4Qx',
			),
			array(
				'url'		=>	'http://localhost/simPolk/network/s_05.php',
				'account'	=>	'VBtXmdeYKZD96KfwrAg8PCPBN8XhLw77Nznn3mWhp1QKEer5ezYcuEJ3MyA3Pm7Y',
				'sign'		=>	'FAKE6Xq467RXB4EM4eJHAZ9ixRm7J8GA',
			),
			array(
				'url'		=>	'http://localhost/simPolk/network/s_06.php',
				'account'	=>	'8ZBtjgKDgMP4MikruFcUqejToht97PTnnVQCDBUZ6GnLBtmxnduyCZaX2JG2yhK7',
				'sign'		=>	'FAKENs32HsHJNQRzAK5JDI1UspyfLbmv',
			),
			array(
				'url'		=>	'http://localhost/simPolk/network/s_07.php',
				'account'	=>	'CNJViGivLzcZiXDC6YSDKYYYvjSuZCqpEd8CCB9AK7ue6pcpY9YAeRgpouLPAWtJ',
				'sign'		=>	'FAKEVTMMP3UI6WmQCfzAwrD0dGgEWcyI',
			),
			array(
				'url'		=>	'http://localhost/simPolk/network/s_08.php',
				'account'	=>	'tC3dfEdQMT3pGXvuzchn6fpLBzaf8TBD2N5HN7yyo1fqzRzCkJopso7Uf3DWGQ5m',
				'sign'		=>	'FAKEAuq7HBaABPXhaKznTm1itGxzwNXR',
			),
			array(
				'url'		=>	'http://localhost/simPolk/network/s_09.php',
				'account'	=>	'RnHHuSfeJE3HvduemawHWAJijZUAzx5RpUzwrQX7BYBGrjkNVHcppyaoWKn9TLz9',
				'sign'		=>	'FAKEZS4acXO8AjV4dfiPcQpWBCNawt14',
			),
			array(
				'url'		=>	'http://localhost/simPolk/network/s_10.php',
				'account'	=>	'4ijWvnXuCmzZy1ACUVJsjc7RQ5qXmqNbtJPcfrZtWE8ukfRMW5HyStHP88Tga3Yv',
				'sign'		=>	'FAKE5N9FcyieEBpNYrT1V02M0v67sRR0',
			),
		),
		
	);
