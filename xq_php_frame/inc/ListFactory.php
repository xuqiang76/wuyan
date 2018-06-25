<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat\inc;

use bigcat\inc\BaseFunction;
use bigcat\inc\Factory;

class ListFactory extends Factory {
	public $sql = '';
	public $bind = array(); // ['tttest', 42, ...]
	public $list_key;
	public $id_arr;

	public function __construct($dbobj, $key, $timeout = null, $id_multi_str = '') {
		$this->list_key = $key;
		if ($id_multi_str) {
			$this->id_arr = explode(',', $id_multi_str);
		}
		parent::__construct($dbobj, $this->list_key, $this->list_key, $timeout);
	}

	public function retrive() {
		$list_arr = array();
		$stg = null;
		if ($this->id_arr && is_array($this->id_arr)) {
			return $this->id_arr;
		} else {
			if ($this->sql) {
				$sth = BaseFunction::query_sql_backend($this->sql, $this->bind);
			}

			$result = $sth->fetchAll(PDO::FETCH_COLUMN, 0);

			if ($result) {
				foreach ($resul as $value) {
					$list_arr[] = $value[0];
				}
				$sth = null;
				return $list_arr;
			}
		}

		return $list_arr;
	}
}