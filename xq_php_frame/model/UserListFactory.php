<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat\model;

use bigcatorm\ListFactory;

class UserListFactory extends ListFactory {
	public $key = 'user_php70_user_list_';

	//select_option : [ "where uid=? and id=? order by ? limit ?", [123, 321, 'uid', '100']]
	public function __construct($dbobj, $id_multi_str = null, $select_option = null) {
		//$id_multi_str 是用,分隔的字符串
		if ($id_multi_str == null && $select_option = null) {
			$this->key = $this->key;
			$this->sql = "select `uid` from `user` ";
			$this->bind = [];
			parent::__construct($dbobj, $this->key);
			return true;
		} else if ($id_multi_str && $select_option = null) {
			$this->key = $this->key . md5($id_multi_str);
			parent::__construct($dbobj, $this->key, null, $id_multi_str);
			return true;
		} else if (is_array($select_option) && $id_multi_str == null) {
			$this->key = $this->key . $select_option[0] . implode('_', $select_option[1]);
			$this->sql = "select `uid` from `user` " . $select_option[0];
			$this->bind = $select_option[1];
			parent::__construct($dbobj, $this->key);
			return true;
		}
		return false;
	}
}