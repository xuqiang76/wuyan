<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat\inc;

use bigcat\inc\Factory;

class MutiStoreFactory extends Factory
{
	public $key_objfactory = null;	// key list objfactory
	public $key_obj = null;	//key list
	protected $bInitMuti = true;

	public function __construct($dbobj, $server_key, $objkey, $key_objfactory, $key_id=null, $timeout=3600)
	{
		parent::__construct($dbobj, $server_key, $objkey, $timeout);

		if($this->bInitMuti)
		{
			$this->key_objfactory = $key_objfactory;
			$this->key_objfactory->initialize();
			$this->key_obj = $this->key_objfactory->get();
		}
		elseif($key_id)
		{
			$this->key_obj = array($key_id);
		}
		else
		{
			$this->key_obj = null;
		}

		$tmp_arr = null;
		if($this->key_obj && is_array($this->key_obj))
		{
			foreach ($this->key_obj as $item)
			{
				$tmp_arr[] = $this->objkey . '_' . $item;
			}
		}
		$this->key_obj = $tmp_arr;
	}

	public function clear()
	{
		if($this->dbobj && is_object($this->dbobj)) {
			$this->dbobj->del_multi($this->server_key, $this->key_obj);	//用数组做参数，删除多个
		}
		$this->clear_key_list();	//delete key list
	}

	public function clear_key_list()
	{
		if($this->key_objfactory)
		{
			$this->key_objfactory->clear();
		}
	}

	public function initialize()
	{
		if($this->objkey == null || $this->key_obj == null || $this->server_key == null)
		{
			return false;
		}

		if($this->key_obj && is_array($this->key_obj) && $this->server_key)
		{	//key list is array
			$strobj_arr = $this->dbobj->get_multi($this->server_key, $this->key_obj);
			if($strobj_arr && is_array($strobj_arr) && count($this->key_obj) == count($strobj_arr))
			{
				$tmp_arr = null;
				foreach ($strobj_arr as $key=>$item)
				{
					$tmp_arr[$key] = igbinary_unserialize($item);
				}
				$this->obj = $tmp_arr;
			}
			else
			{	//not in cache
				$this->obj = $this->retrive();
				if( $this->obj !== null  )
				{
					if($this->bInitMuti)
					{
						$this->clear();
					}
					$this->writeback();
				}
			}
		}
		else
		{
			return false;
		}

		return ($this->obj !== null );

	}

	public function writeback($id=null)
	{
		global $KYE_NAME;
		// 如果是初始化所有对象,则分别写回
		$tmp_arr = array();
		foreach( $this->obj as $key => $obj )
		{
			if(is_object($obj))
			{
				$obj->before_writeback();
			}
			if( $id !== null && $key !== $this->objkey . '_' .$id )
			continue;
			$tmp_arr[$KYE_NAME.$key] = igbinary_serialize($obj);
		}
		if($tmp_arr)
		{
			$this->dbobj->set_multi($this->server_key, $tmp_arr, $this->timeout );
		}
		unset($tmp_arr);
	}

	public function retrive()
	{
		return array();
	}
}