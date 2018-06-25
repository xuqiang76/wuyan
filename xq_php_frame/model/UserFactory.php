<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat\model;

use bigcat\inc\BaseFunction;
use bigcat\inc\Factory;

class UserFactory extends Factory {
	const objkey = 'user_php70_user_multi_';
	private $sql;
	private $bind;
	public function __construct($dbobj, $uid) {
		$serverkey = self::objkey;
		$objkey = self::objkey . "_" . $uid;
		$this->sql = "select
            `uid`
            , `key`
            , `status`
            , `pwd`
            , `init_time`
            , `up_time`
            , `login_time`

            , `name`

            from `user`
            where `uid`=?";
		$this->bind = [intval($uid)];
		parent::__construct($dbobj, $serverkey, $objkey);
		return true;
	}

	public function retrive() {
		$res_query = BaseFunction::query_sql_backend($this->sql, $this->bind);
		if (!$res_query) {
			return null;
		}
		$obj = null;
		$result = $res_query['sth']->fetchAll(PDO::FETCH_CLASS, "User");

		if ($result) {
			$obj = $result[0];
			$obj->before_writeback();
		}
		return $obj;
	}
}
