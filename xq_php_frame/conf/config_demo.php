<?php

// $DEBUG = true;
// $LANGUAGE = '123';
// $PLATFORM = '123';

// $MY_PATH = 'http://127.0.0.1/123';

// //memcached servers
// $MC_SERVERS = array(array('127.0.0.1', 123));

// //缓存前缀  区分用的key前缀
// $KYE_NAME = '123';

// //测试版数据地址
// $DB_HOST = '123';
// $DB_USERNAME = '123';
// $DB_PASSWD = '123';
// $DB_DBNAME = '123';
// $DB_PORT = '123';

// $API_KEY = '123';
// $RPC_KEY = '123 123';

$options = [
	'mysql' => [
		'host' => '123',
		'port' => 123,
		'username' => '123',
		'password' => '123',
		'dbname' => 'iveryone_test',
		'charset' => 'utf8mb4',
	],
	'redis' => [
		'parameters' => [
			'scheme' => 'tcp',
			'host' => 'ttt',
			'port' => 123,
			'password' => 'ttt',
		],
		'db' => 5,
		'cachedb' => 6,
	],

	'h5_url' => 'https://123/',

	'iveryone_api_url' => 'http://ttt/',
	'iveryone_openapi_url' => 'http://ttt/',
	'linkface' => [
		'api_id' => '123',
		'api_secret' => '123',
	],
	'aliyun' => [
		'oss' => [
			'accessKeyId' => '123',
			'accessKeySecret' => '123',
		],
	],
];
