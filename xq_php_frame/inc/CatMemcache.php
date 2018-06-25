<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat\inc;

use bigcat\inc\iCache;

class CatMemcache implements iCache
{
	private $conn;
	private $ok;
	static $instance;

	public static function get_instance()
	{
		//单例	
		global $MC_SERVERS;
	
		if( empty(self::$instance))
		{
			self::$instance = new self($MC_SERVERS);
		}
	
		return  self::$instance;
	}
	
	function __construct($servers)
	{
		$this->conn = new \Memcached;
		$this->ok = false;
		
		$this->conn->setOption(\Memcached::OPT_COMPRESSION, true);
		$this->conn->setOption(\Memcached::OPT_DISTRIBUTION, \Memcached::DISTRIBUTION_CONSISTENT);	//一致性hash
		$this->conn->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
		
		foreach( $servers as $server )
		{
			if( $this->conn->addServer($server[0], $server[1]) !== false )
			{
				$this->ok = true;
			}
		}
	}

	public function ok()
	{
		return $this->ok;
	}

	public function get($server_key, $strkey)
	{
		if( $this->conn )
		{
			$strobjs = $this->conn->getByKey($server_key, $strkey);
			if( $strobjs !== false )
			{
				return $strobjs;
			}
			else
			{
				return null;
			}
		}
		return null;
	}

	public function  get_multi($server_key, $keys_arr )
	{
		if( $this->conn )
		{
			$objs_arr = $this->conn->getMultiByKey($server_key, $keys_arr);
			if( $objs_arr != null )
			{
				return $objs_arr;
			}
			else
			{
				return null;
			}
		}
		return null;
	}

	public function set_multi( $server_key, $values, $timeout=0 )
	{
		//$values : An array of key/value pairs to store on the server.
		if( is_array($values) )
		{
			return $this->conn->setMultiByKey( $server_key, $values, $timeout);
		}
		return false;
	}

	public function set($server_key, $strkey, $strobj=null, $timeout=0)
	{
		return $this->conn->setByKey($server_key, $strkey, $strobj, $timeout);
	}

	public function setKeep($strkey, $strobj, $timeout=0)
	{
		return $this->conn->add($strkey, $strobj, $timeout);
	}

	public function append($strkey, $strobj, $timeout=0)
	{
		//追加模式不能使用压缩
		$this->conn->setOption(Memcached::OPT_COMPRESSION, false);
		if(!$this->setKeep($strkey, $strobj, $timeout))
		{
			$this->conn->append($strkey, $strobj);
		}
		$this->conn->setOption(Memcached::OPT_COMPRESSION, true);
	}

	public function del_multi( $server_key, $keys )
	{
		if( is_array($keys) )
		{
			return $this->conn->deleteMultiByKey( $server_key, $keys);
		}
		return false;
	}

	public function del($server_key, $strkey )
	{
		return $this->conn->deleteByKey($server_key, $strkey);
	}
	
	public function get_result()
	{
		return $this->conn->getResultCode();
	}	
}