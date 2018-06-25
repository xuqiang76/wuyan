<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat\inc;

interface iCache
{
	// whether connection to db is ok
	public function ok();

	// get values from db
	// if keys is array,return array(keys[0] => value...) else return value
	// value of some key not exist,if keys is array,no value in return array,else return null
	public function get( $server_key, $strkey);

	// set values to db
	// if values is array,otherwise it is the key of strobj
	public function set( $server_key, $strkey, $strobj=null, $timeout=0);

	// set value to db if it not exist
	// return false if it exist else true
	public function setKeep($strkey, $strobj, $timeout=0);

	// delete values from db
	public function del($server_key, $strkey);

	//
	public function get_multi( $server_key, $keys_arr );

	public function set_multi( $server_key, $values, $timeout=0 );

	public function del_multi( $server_key, $keys );
	
	public function get_result();

}