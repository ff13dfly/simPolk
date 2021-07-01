<?php
	return array(
		//sim-chain db setting
		'redis'			=>	array(			//redis setting
			'host'		=>	'localhost',
			'port'		=>	6379,
			'auth'		=>	'authKEY',
			'timeout'	=>	3600,
		),

		//sim-chain basic setting
		'name'			=>	'VBC',			//coin name
		'speed'			=>	30,				//block created speed (block/second)
		'basecoin'		=>	100,			//reward for creating block
		'password'		=>	'123456',		//default password for simulate account
		'cost'			=>	array(
			'transfer'	=>	array(1,20),	//transfer cost range
			'storage'	=>	10,				//storage set cost
			'contact'	=>	10,				//contact run cost
		),
		
		//sim-chain db keys setting
		'keys'			=>	array(
			'start'					=>	'sim_start',		//simulator start timestamp, used to calc block number
			'transfer_collected'	=>	'sim_collected',	//what is collected now.
			'storage_collected'		=>	'sim_storage',		//storage map entry key
			'contact_collected'		=>	'sim_contact',		//contact map entry key

			'height'				=>	'block_height',		//now height
			'accounts'				=>	'sim_accounts',		//account hashmap entry
			'account_list'			=>	'all_accounts',		//account list

			'chain_entry'			=>	'en_chain',			//block map entry name (for redis hash)
			'storage_entry'			=>	'en_storage',		//storage map entry name (for redis hash)
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
		'key_cur_block'	=>	'currentblock',		//the current block number
		
		'nodes'		=>	array(
			array(
				'url'		=>	'http://localhost/simPolk/network/s_01.php',
				'account'	=>	'5r9E41L7tb8PstabiPKsJm56q8XeMqoA43Zmxbn9NwTmvKh75468McNpv2ZYTH8i',
				'sign'		=>	'',
			),
			array(
				'url'		=>	'http://localhost/simPolk/network/s_02.php',
				'account'	=>	'GiPLyuNqog6FYypM1saRXfsJh2PDVRSH6G7gg4oYMF894SE3kAC7dhutUVFHG42T',
				'sign'		=>	'',
			),
			array(
				'url'		=>	'http://localhost/simPolk/network/s_03.php',
				'account'	=>	'hmAaXc3K4mLTf8LdhnrmrWE92smnj4nzpf9NNaxZSkhV6gPi2SzC5Ye3VFYQBPME',
				'sign'		=>	'',
			),
			array(
				'url'		=>	'http://localhost/simPolk/network/s_04.php',
				'account'	=>	'tYRDbF6M2HUtmJ4bcPc1Sr7LyykN6WtBwAus5bWmhmdEhEYH5hXjsJ4HteQNKFLN',
				'sign'		=>	'',
			),
			array(
				'url'		=>	'http://localhost/simPolk/network/s_05.php',
				'account'	=>	'VBtXmdeYKZD96KfwrAg8PCPBN8XhLw77Nznn3mWhp1QKEer5ezYcuEJ3MyA3Pm7Y',
				'sign'		=>	'',
			),
			array(
				'url'		=>	'http://localhost/simPolk/network/s_06.php',
				'account'	=>	'8ZBtjgKDgMP4MikruFcUqejToht97PTnnVQCDBUZ6GnLBtmxnduyCZaX2JG2yhK7',
				'sign'		=>	'',
			),
			array(
				'url'		=>	'http://localhost/simPolk/network/s_07.php',
				'account'	=>	'CNJViGivLzcZiXDC6YSDKYYYvjSuZCqpEd8CCB9AK7ue6pcpY9YAeRgpouLPAWtJ',
				'sign'		=>	'',
			),
			array(
				'url'		=>	'http://localhost/simPolk/network/s_08.php',
				'account'	=>	'tC3dfEdQMT3pGXvuzchn6fpLBzaf8TBD2N5HN7yyo1fqzRzCkJopso7Uf3DWGQ5m',
				'sign'		=>	'',
			),
			array(
				'url'		=>	'http://localhost/simPolk/network/s_09.php',
				'account'	=>	'RnHHuSfeJE3HvduemawHWAJijZUAzx5RpUzwrQX7BYBGrjkNVHcppyaoWKn9TLz9',
				'sign'		=>	'',
			),
			array(
				'url'		=>	'http://localhost/simPolk/network/s_10.php',
				'account'	=>	'4ijWvnXuCmzZy1ACUVJsjc7RQ5qXmqNbtJPcfrZtWE8ukfRMW5HyStHP88Tga3Yv',
				'sign'		=>	'',
			),
		),
		
	);
