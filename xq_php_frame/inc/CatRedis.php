<?php
/**
 * @author xuqiang76@163.com
 * @final 20180517
 */

namespace bigcat\inc;

use bigcat\inc\iCache;

class CatRedis implements iCache {
	private $conn;
	private $ok;
	static $instance;

	public static function get_instance() {
		global $options;

		if (empty(self::$instance)) {
			self::$instance = new self($options['redis']);
		}

		if (self::$instance->ok()) {
			return self::$instance;
		} else {
			return null;
		}
	}

	function __construct($server) {
		$this->conn = new \Redis();
		$this->ok = false;

		$this->conn->connect($server['parameters']['host'], $config['parameters']['port']);
		if (isset($config['parameters']['password']) && $this->conn->auth($config['parameters']['password'])) {

			$this->conn->select($config['db']);
			$this->ok = true;
		}
	}

	public function ok() {
		return $this->ok;
	}

	public function get($server_key = '', $strkey = '') {
		if ($this->conn) {
			$strobjs = $this->conn->get($strkey);
			if ($strobjs !== false) {
				return $strobjs;
			} else {
				return null;
			}
		}
		return null;
	}

	public function get_multi($server_key = '', $keys_arr = array()) {
		if ($this->conn) {
			$objs_arr = $this->conn->mGet($keys_arr);
			if ($objs_arr != null) {
				return $objs_arr;
			} else {
				return null;
			}
		}
		return null;
	}

	public function set_multi($server_key = '', $values = array(), $timeout = 0) {
		//$values : An array of key/value pairs to store on the server.
		if (is_array($values)) {
			return $this->conn->mSet($values);
		}
		return false;
	}

	public function set($server_key = '', $strkey = '', $strobj = null, $timeout = 0) {
		return $this->conn->set($strkey, $strobj);
	}

	public function setKeep($strkey, $strobj, $timeout = 0) {
		return $this->conn->setNx($strkey, $strobj);
	}

	public function append($strkey, $strobj, $timeout = 0) {
		$this->conn->append($strkey, $strobj);
	}

	public function del_multi($server_key = '', $keys = array()) {
		if (is_array($keys)) {
			return $this->conn->delete($keys);
		}
		return false;
	}

	public function del($server_key = '', $strkey = '') {
		return $this->conn->delete($strkey);
	}

	public function get_result() {
		return 0;
	}
}