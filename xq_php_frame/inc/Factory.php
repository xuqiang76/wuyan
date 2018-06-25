<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat\inc;

use bigcat\inc\BaseObject;

class Factory extends BaseObject
{

	protected $server_key;
	protected $objkey;
	protected $timeout;
	protected $dbobj;
	protected $obj;

	function __construct($dbobj, $server_key, $objkey, $timeout=3600)
	{
		global $KYE_NAME;
		$this->dbobj = $dbobj;
		$this->server_key = $KYE_NAME.$server_key;
		$this->objkey = $KYE_NAME.$objkey;
		$this->timeout = $timeout;
	}

	public function get()
	{
		return $this->obj;
	}

	public function set($obj)
	{
		return $this->obj = $obj;
	}

	public function clear()
	{
		if($this->timeout === null)
		{
			return false;
		}
		if($this->dbobj && is_object($this->dbobj)) {
			$this->dbobj->del( $this->server_key, $this->objkey);
		}
		return true;
	}

	public function writeback()
	{
		if($this->timeout === null)
		{
			return false;
		}
		if(is_object($this->obj))
		{
			$this->obj->before_writeback();
		}
		$strobj = igbinary_serialize($this->obj);
		$this->dbobj->set($this->server_key, $this->objkey, $strobj, $this->timeout);
		return true;
	}

	public function initialize()
	{
		$strobj = null;
		if($this->objkey == null || $this->server_key == null){
			return false;
		}
		if($this->timeout !== null)
		{
			$strobj = $this->dbobj->get($this->server_key, $this->objkey);
		}
		if( $strobj === false ){
			return false;
		}
		if( $strobj !== null )
		{
			$obj = igbinary_unserialize($strobj);
			if($obj !== false && $obj !== null)
			{
				$this->obj = $obj;
			}
		}
		else
		{
			$this->obj = $this->retrive();
			if( $this->obj !== null  )
			{
				$this->writeback();
			}
		}
		return ($this->obj !== null );
	}

	// if you want to retrive data from some other place,if it not store in hash db
	// please override retrive function
	public function retrive()
	{
		return null;
	}
}