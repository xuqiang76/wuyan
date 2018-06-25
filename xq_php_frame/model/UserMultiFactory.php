<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat\model;

use bigcat\inc\BaseFunction;
use bigcat\inc\MutiStoreFactory;

class UserMultiFactory extends MutiStoreFactory {
	public $key = 'user_php70_user_multi_';
	private $sql;
	private $bind = [];

	public function __construct($dbobj, $key_objfactory = null, $uid = null, $key_add = '') {
		if (!$key_objfactory && !$uid) {
			return false;
		}
		$this->key = $this->key . $key_add;
		$ids = '';
		if ($key_objfactory) {
			if ($key_objfactory->initialize()) {
				$key_obj = $key_objfactory->get();
				$ids = implode(',', array_fill(0, count($key_obj), '?'));
				//$ids = implode(',', $key_obj);
			}
		}
		$fields = "
            `uid`
            , `key`
            , `status`
            , `pwd`
            , `init_time`
            , `up_time`
            , `login_time`

            , `name`
            ";

		if ($uid != null) {
			$this->bInitMuti = false;
			$this->sql = "select $fields from user where `uid`=?";
			$this->bind = [intval($uid)];
		} else {
			$this->sql = "select $fields from user ";
			if ($ids) {
				$this->sql = $this->sql . " where `uid` in ($ids) ";
			}
			$this->bind = $key_obj;
		}
		parent::__construct($dbobj, $this->key, $this->key, $key_objfactory, $uid);
		return true;
	}

	public function retrive() {
		$res_query = BaseFunction::query_sql_backend($this->sql, $this->bind);
		if (!$res_query) {
			return null;
		}

		$objs = array();
		$objs = $res_query['sth']->fetchAll(PDO::FETCH_CLASS, "User");

		foreach ($objs as $key => $val) {
			$objs[$key]->before_writeback();
		}

		return $objs;
	}
}
