<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat\inc;

class BaseFunction {
	static $db_instance = null;

	//通过前端授权码code获得用户的微信openid
	public static function code_get_openid($code, $appid, $appsecret) {
		//获取openid
		$openid = '';
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$appsecret&code=$code&grant_type=authorization_code";
		$result = self::https_request($url);

		$jsoninfo = json_decode($result, true);
		if (isset($jsoninfo["openid"])) {
			$openid = $jsoninfo["openid"]; //从返回json结果中读出openid
		}
		return $openid;
	}

	//发短信函数 阿里大鱼
	public static function sms_cz_alidayu($templateCode, $sms_param, $phone, $signname = "灵飞") {
		$gearmanjson = array
			(
			'template_code' => $templateCode
			, 'sms_param' => $sms_param
			, 'phone' => $phone
			, 'signname' => $signname,
		);

		try
		{
			$client = new \GearmanClient();
			$client->addServer('127.0.0.1', 4730);
			$client->doBackground('sms_cz', json_encode($gearmanjson));
		} catch (Exception $e) {
			self::logger('./log/sms.log', "【Exception】:\n" . var_export($e, true) . "\n" . __LINE__ . "\n");
			return false;
		}
		return true;
	}

	public static function time2str($itime) {
		if ($itime) {
			return date('Y-m-d H:i:s', $itime);
		}
		return false;
	}

	public static function microtime_float() {
		list($usec, $sec) = explode(" ", microtime());
		return ((float) $usec + (float) $sec);
	}

	public static function output($response) {

		header('Cache-Control: no-cache, must-revalidate');
		header("Content-Type: text/plain; charset=utf-8");

		if (isset($_REQUEST['callback']) && $_REQUEST['callback']) {
			echo $_REQUEST['callback'] . '(' . json_encode($response) . ')';
		} else {
			echo json_encode($response);
		}
	}

	public static function output_html($html) {

		header('Cache-Control: no-cache, must-revalidate');
		header("Content-Type: text/html; charset=utf-8");

		echo ($html);
	}

	public static function encryptMD5($data) {
		$content = '';
		if (!$data || !is_array($data)) {
			return $content;
		}
		ksort($data);
		foreach ($data as $key => $value) {
			$content = $content . $key . $value;
		}
		if (!$content) {
			return $content;
		}

		return self::sub_encryptMD5($content);
	}

	public static function sub_encryptMD5($content) {
		global $RPC_KEY;
		$content = $content . $RPC_KEY;
		$content = md5($content);
		if (strlen($content) > 10) {
			$content = substr($content, 0, 10);
		}
		return $content;
	}

	public static function https_request($url, $data = null) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		if (!empty($data)) {
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($curl);
		curl_close($curl);
		return $output;
	}

	public static function logger($file, $word) {
		$fp = fopen($file, "a");
		flock($fp, LOCK_EX);
		fwrite($fp, "执行日期：" . strftime("%Y-%m-%d %H:%M:%S", time()) . "\n" . $word . "\n\n");
		flock($fp, LOCK_UN);
		fclose($fp);
	}

	public static function get_client_ip() {
		$s_client_ip = '';

		if (isset($_SERVER['HTTP_X_REAL_IP'])) {
			$s_client_ip = $_SERVER['HTTP_X_REAL_IP'];
		} elseif ($_SERVER['REMOTE_ADDR']) {
			$s_client_ip = $_SERVER['REMOTE_ADDR'];
		} elseif (getenv('REMOTE_ADDR')) {
			$s_client_ip = getenv('REMOTE_ADDR');
		} elseif (getenv('HTTP_CLIENT_IP')) {
			$s_client_ip = getenv('HTTP_CLIENT_IP');
		} else {
			$s_client_ip = 'unknown';
		}
		return $s_client_ip;
	}

	public static function getDB() {
		//单例
		global $options;

		if (empty(self::$db_instance)) {
			$dsn = 'mysql:' . implode(';', array(
				'host=' . $options['mysql']['host'],
				'port=' . $options['mysql']['port'],
				'dbname=' . $options['mysql']['dbname'],
				'charset=' . $options['mysql']['charset'],
			));

			$options_db = array(
				\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$options['mysql']['charset']}",
				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_TIMEOUT => 2,
			);

			self::$db_instance = new \PDO($dsn, $options['mysql']['username'], $options['mysql']['password'], $options_db);

		}

		try {
			self::$db_instance->query('SELECT 1');
			return self::$db_instance;
		} catch (\PDOException $e) {
			self::$db_instance = null;
			return false;
		}

	}

	//$rawsqls array(['insert into mytable (name, age) values (?, ?)', ['xq', 42]],  ...)
	public static function execute_sql_backend($rawsqls) {
		$result_arr = null;
		$is_rollback = false;

		if (!$rawsqls || !is_array($rawsqls)) {
			return $result_arr;
		}

		$db_connect = self::getDB();
		$db_connect->beginTransaction();
		foreach ($rawsqls as $item_sql) {
			$result = null;

			$pstmt = $db_connect->prepare($item_sql[0]);
			$result = $pstmt->execute($item_sql[1]);

			if (!$result) {
				if ($db_connect->rollBack()) {
					$is_rollback = true;
				} else {
					$db_connect->rollBack();
					$is_rollback = true;
				}
				$result_arr = null;
				break;
			}

			$tmp_result['result'] = $result;
			$tmp_result['last_insert_id'] = $db_connect->lastInsertId();
			$tmp_result['row_count'] = $pstmt->rowCount();
			$result_arr[] = $tmp_result;

		}

		if (!$is_rollback) {
			$db_connect->commit();
			return $result_arr;
		}

		return null;
	}

	public static function query_sql_backend($rawsql, $bind_params = array()) {
		$db_connect = self::getDB();

		if ($pstmt = $db_connect->prepare($rawsql) && $pstmt->execute($bind_params)) {
			return $pstmt;
		}

		return null;
	}

	/*
		* @inout $weights : array(1=>20, 2=>50, 3=>100);
		* @putput array
	*/
	public static function w_rand($weights) {

		$r = mt_rand(1, array_sum($weights));

		$offset = 0;
		foreach ($weights as $k => $w) {
			$offset += $w;
			if ($r <= $offset) {
				return $k;
			}
		}

		return null;
	}

	public static function my_addslashes($str) {
		$str = str_replace(array("\r\n", "\r", "\n"), '', $str);
		return addslashes(stripcslashes($str));
	}

}