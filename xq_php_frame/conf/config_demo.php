<?php

// $DEBUG = true;
// $LANGUAGE = 'cn';
// $PLATFORM = 'wuyan';

// $MY_PATH = 'http://127.0.0.1/mahjong/game_agent/big_agent/index.php';

// //memcached servers
// $MC_SERVERS = array(array('127.0.0.1', 11211));

// //缓存前缀  区分用的key前缀
// $KYE_NAME = 'wuyan_ivery_ad_';

// //测试版数据地址
// $DB_HOST = '10.66.182.68';
// $DB_USERNAME = 'root';
// $DB_PASSWD = 'gfplay@541013';
// $DB_DBNAME = 'fair_agent';
// $DB_PORT = '3306';

// $API_KEY = 'NCBDpay';
// $RPC_KEY = 'gfplay is best gfplay is best';

$options = [
	'mysql' => [
		'host' => '172.26.25.207',
		'port' => 3306,
		'username' => 'wuyandev',
		'password' => 'wuyandev',
		'dbname' => 'iveryone_test',
		'charset' => 'utf8mb4',
	],
	'redis' => [
		'parameters' => [
			'scheme' => 'tcp',
			'host' => '172.26.25.208',
			'port' => 6379,
			'password' => '9f3d9739b11c2a4b08ea48512ac467f6',
		],
		'db' => 5,
		'cachedb' => 6,
	],

	'h5_url' => 'https://beta.iveryone.test.wuyan.cn/',

	'iveryone_api_url' => 'http://api.iveryone.test.wuyan.cn/',
	'iveryone_openapi_url' => 'http://openapi.iveryone.test.wuyan.cn/',
	'linkface' => [
		'api_id' => 'cda7ca4424ed43f8ae4239ebcfacd065',
		'api_secret' => '60a29bd891964871a1282ca728a45f78',
	],
	'aliyun' => [
		'oss' => [
			'accessKeyId' => 'LTAIgqlgNpdOi1H6',
			'accessKeySecret' => 'SbbBjxAroCcHnvxQgA5gZ8GlQ7DqcG',
		],
	],
];