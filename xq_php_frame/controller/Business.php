<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat\controller;

use bigcat\inc\BaseFunction;
use bigcat\conf\CatConstant;

use bigcat\model\User;
use bigcat\model\UserFactory;
use bigcat\model\UserListFactory;
use bigcat\model\UserMultiFactory;

use bigcat\model\WxOpenid;
use bigcat\model\WxOpenidFactory;
use bigcat\model\WxOpenidListFactory;
use bigcat\model\WxOpenidMultiFactory;

class Business
{
	private $log = CatConstant::LOG_FILE;
	private $check_num_key = 'user_check_num_';
	private $check_add_agent_num_key = 'add_agent_check_num_';
	private $login_timeout = 31536000;	//3600 * 24 * 365
	public $cache_handler = null;
	private $check_login_pwd ='check_login_pwd_';


	public function send_num($params)
	{
		session_start();
		global $DEBUG;
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		//$rawsqls = array();
		//$itime = time();
		$data = array();
		$guoji = false;

		do {
			if( !isset($params['uid']) || !$params['uid'] || $params['uid'] == 86
			)
			{
				$response['code'] = CatConstant::ERROR; $response['sub_code'] = 4; $response['desc'] = __line__; break;
			}

			if(!$this->cache_handler->setKeep($params['uid'], 1, 3))
			{
				$response['sub_code'] = 1; $response['desc'] = __line__; break;
			}

			//国际短信
			if(substr($params['uid'],0,2) == 86 || $params['uid'] == 11112223333)
			{
				$guoji = false;

				/*if($params['uid'] != 11112223333)
				{
					$params['uid'] = substr($params['uid'],2,11);
				}*/
			}
			else
			{
				$guoji = true;
			}


			if(empty($params['type']))
			{
				$params['type'] = 1;
			}

            //if(1 == $params['type'] )//职业发送短信 就验证图形验证码
            {

                if( !isset($params['authcode']) || !$params['authcode'])
                {
                    $response['code'] = CatConstant::ERROR;$response['sub_code'] = 5; $response['desc'] = __line__; break;
                }

                if(empty($_SESSION['authcode']))
                {
                    //$_SESSION['authcode'] = 'ncbd1234567890';
                    $response['sub_code'] = 3;
                    $response['desc'] = __line__; break;
                }

                if(  strtoupper(str_replace(" ","",$params['authcode']))!=strtoupper($_SESSION['authcode']) )
                {
                    $response['sub_code'] = 2;
                    $response['desc'] = __line__; break;
                }

            }


			$check_num = 4321;
			if($DEBUG || $params['uid'] == 11112223333)
			{
				//测试帐号
				$check_num = 4321;	//test
			}
			else
			{
				$check_num = mt_rand(1000,9999);
			}

			//发送短信
			if($guoji == false )
			{
				//国内
				BaseFunction::sms_cz_alidayu("SMS_36375183", json_encode(array('code'=>strval($check_num), 'product'=>'灵飞棋牌')), $params['uid']);
			}
			else
			{
				//国际
				BaseFunction::sms_cz_tianruiyun($check_num, $params['uid'],'灵飞棋牌');
			}


			if(1 == $params['type'] )
			{
				$strkey = $this->check_num_key.$params['uid'];
			}
			else if (2 == $params['type'] )
			{
				$strkey = $this->check_add_agent_num_key.$params['uid'];
			}
			else
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			if( !($this->cache_handler->set( $strkey, $strkey, $check_num, 86400 )) )
			{
				BaseFunction::logger($this->log, "【memcached_set】:\n".var_export($this->cache_handler->get_result(), true)."\n".__LINE__."\n");
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			$response['data'] = $data;
		}while(false);

		return $response;
	}
	public function login($params)
	{
		global $WX_APPID, $WX_APPSECRET;
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();

		do {
			if( (!isset($params['code']) || !$params['code']) &&
			( !isset($params['uid']) || !$params['uid']	|| !isset($params['num']) || !$params['num'])
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}
			if(!isset($params['code']))
			{
				$params['code'] = '';
			}
			if(!isset($params['openid']))
			{
				$params['openid'] = '';
			}


			//$obj_user = null;
			//$is_code = false;
			//$is_openid_uid = false;
			$openid = '';
			$need_bind = false;
			$need_check_num = false;
			$data_uid = 0;

			//如果code存在，先用code查找uid
			if($params['code'] || $params['openid'])
			{
				//$is_code = true;
				//通过code取得openid
				if($params['openid'])
				{
					$openid = $params['openid'];
				}
				else if($params['code'])
				{
					$openid = BaseFunction::code_get_openid($params['code'], $WX_APPID, $WX_APPSECRET);
				}

				//如果查到数据库uid
				if(!$openid)
				{
					$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
				}
				$obj_wx_openid_list_factory = new WxOpenidListFactory($this->cache_handler, $openid);
				if($obj_wx_openid_list_factory->initialize() && $obj_wx_openid_list_factory->get())
				{
					$obj_wx_openid_factory = new WxOpenidMultiFactory($this->cache_handler, $obj_wx_openid_list_factory);
					if($obj_wx_openid_factory->initialize())
					$obj_wx_openid = $obj_wx_openid_factory->get();
					if($obj_wx_openid && is_array($obj_wx_openid))
					{
						foreach ($obj_wx_openid as $obj_wx_openid_item)
						{
							$data_uid = $obj_wx_openid_item->uid;
							break;
						}
					}
				}
				else
				{
					$obj_wx_openid_list_factory->clear();
				}

				if($data_uid)
				{
					//$is_openid_uid = true;
					if(!$params['uid'] || $data_uid == $params['uid'])
					{
						$need_check_num = false;
						$need_bind = false;
					}
					else if($params['uid'] && $data_uid != $params['uid'] && isset($obj_wx_openid_item) && $obj_wx_openid_item)
					{
						$rawsqls[] = $obj_wx_openid_item->getDelSql();
						$obj_wx_openid_factory->clear();
						$data_uid = $params['uid'];
						$need_check_num = true;
						$need_bind = true;
					}
					else
					{
						$data_uid = $params['uid'];
						$need_check_num = true;
						$need_bind = true;
					}
				}
				else
				{
					if(!$params['uid'] || !$params['num'])
					{
						//如果没有绑定 并且 没有$params['uid']等参数就跳出
						$response['data']['openid'] = $openid;
						$response['sub_code'] = 1; $response['desc'] = __line__; break;
					}
					else
					{
						//如果有客户端参数 $params['uid'] 和 $params['num'] 则登录
						//并绑定
						$data_uid = $params['uid'];
						$need_check_num = true;
						$need_bind = true;
					}
				}
			}
			else
			{
				//$is_code = false;
				$need_check_num = true;
				$need_bind = false;

				if(!$params['uid'] || !$params['num'])
				{
					//如果没有$params['uid']等参数就跳出
					$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
				}
				else
				{
					//如果有客户端参数 $params['uid'] 和 $params['num'] 则登录
					$data_uid = $params['uid'];
					$need_check_num = true;
				}
			}

			if($need_check_num && $data_uid)
			{
				$strkey = $this->check_num_key.$data_uid;
				$check_num = $this->cache_handler->get( $strkey, $strkey );
				if( $check_num != $params['num'])
				{
					$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
				}
			}

			if($need_bind && $openid && $data_uid)
			{
				//绑定
				$obj_wx_openid_multi_factory = new WxOpenidMultiFactory($this->cache_handler, null, $data_uid);
				// $obj_wx_openid_multi_factory->clear();
				// if($obj_wx_openid_multi_factory->initialize() && $obj_wx_openid_multi_factory->get())
				// {
				// 	$obj_wx_openid_multi = $obj_wx_openid_multi_factory->get();
				// 	if($obj_wx_openid_multi && is_array($obj_wx_openid_multi))
				// 	{
				// 		foreach ($obj_wx_openid_multi as $obj_wx_openid_multi_item)
				// 		{
				// 			$obj_wx_openid_multi_item->openid = $openid;
				// 			$rawsqls[] = $obj_wx_openid_multi_item->getUpdateSql();
				// 		}
				// 	}
				// }
				// else
				{
					$obj_wx_openid = new WxOpenid();
					$obj_wx_openid->uid = $data_uid;
					$obj_wx_openid->openid = $openid;
					$rawsqls[] = $obj_wx_openid->getInsertSql();
				}
			}

			$obj_user_factory = new UserFactory($this->cache_handler, $data_uid);
			if(!$obj_user_factory->initialize())
			{
				//无记录
				$tmp_user = new User();
				$tmp_user->uid = $data_uid;
				$tmp_user->init_time = $itime;
				$tmp_user->login_time = $itime;
				$tmp_user->up_time = $itime;
				$tmp_user->key = substr(md5($itime), 0, 6);

				$rawsqls[] = $tmp_user->getInsertSql();

				$obj_user = $tmp_user;
			}
			else
			{
				$obj_user = $obj_user_factory->get();

				$obj_user->login_time = $itime;
				$obj_user->key = substr(md5($itime), 0, 6);
				$rawsqls[] = $obj_user->getUpdateSql();
			}

			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				BaseFunction::logger($this->log, "【rawsqls】:\n".var_export($rawsqls, true)."\n".__LINE__."\n");
				$response['code'] = CatConstant::ERROR_UPDATE; $response['desc'] = __line__; break;
			}

			$obj_user_factory->clear();
			if(isset($obj_wx_openid_multi_factory))
			{
				$obj_wx_openid_multi_factory->clear();
			}

			$data['user'] = $obj_user;
			$data['openid'] = $openid;
			$response['data'] = $data;

		}while(false);

		return $response;
	}


	public function login_pwd($params)
	{
		global $WX_APPID, $WX_APPSECRET,$LOGIN_OUT_TIME ,$DEBUG;
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();
		$max_num = 5;//密码错误次数

		do {
			if( !isset($params['uid']) || !$params['uid']
			|| !isset($params['pwd']) || !$params['pwd']
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			$obj_user_factory = new UserFactory($this->cache_handler, $params['uid']);
			if(!$obj_user_factory->initialize() )
			{
				//无记录
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__;return $response; break;
			}
			else
			{
				$obj_user = $obj_user_factory->get();

				//判断是否查封或删除
				if($obj_user->status == 1)
				{
					$response['sub_code'] = 3; $response['desc'] = __line__; break;
				}

				if(!empty($LOGIN_OUT_TIME))
				{
					$out_time = $LOGIN_OUT_TIME * 3600;
					if(($itime - $obj_user->login_time) > $out_time )
					{
						$obj_user->login_time = $itime;
						//超时之后 再重新设置key
						if($DEBUG)
						{
							$obj_user->key = '123456';
						}
						else
						{
							$obj_user->key = substr(md5($itime), 0, 6);
						}
					}
					else
					{
						$obj_user->login_time = $itime;
					}
				}
				else
				{
					$obj_user->login_time = $itime;

					if (empty($obj_user->key))
                    {
                        $obj_user->key = substr(md5($itime), 0, 6);
                    }
				}

				/////////////////////密码错误 限制次数
				if( $obj_user->pwd != BaseFunction::sub_encryptMD5(BaseFunction::encryptMD5(array($params['pwd']))) )
				{
					$strkey = $this->check_login_pwd.$params['uid'];
					$check_num = $this->cache_handler->get( $strkey, $strkey );
					if( $check_num == $max_num)
					{
						$response['sub_code'] = 2; $response['desc'] = __line__; break;
					}
					else
					{
						$check_num += 1;
						if( !($this->cache_handler->set( $strkey, $strkey, $check_num, 1800 )) )
						{
							$response['sub_code'] = 5; $response['desc'] = __line__; break;
						}
					}

                    $min_num = $max_num - $check_num;
					$response['sub_code'] = 8; $response['desc'] = __line__; $response['sub_desc'] = "密码输入错误!您还有".$min_num."次机会" ;  break;
				}
				else
				{
					$strkey = $this->check_login_pwd.$params['uid'];
					$check_pwd = $this->cache_handler->get( $strkey, $strkey );
					if(isset($check_pwd) && $check_pwd == 5)
					{
						$response['sub_code'] = 4; $response['desc'] = __line__; break;
					}
					else
					{
						$this->cache_handler->del( $strkey, $strkey );
					}
				}
				////////////////////////////

				$rawsqls[] = $obj_user->getUpdateSql();
			}

			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				BaseFunction::logger($this->log, "【rawsqls】:\n".var_export($rawsqls, true)."\n".__LINE__."\n");
				$response['code'] = CatConstant::ERROR_UPDATE; $response['desc'] = __line__; break;
			}

			$obj_user_factory->clear();

			$data['user'] = $obj_user;
			$response['data'] = $data;

		}while(false);

		return $response;
	}

	public function chmod_login_pwd($params)
	{
		global $WX_APPID, $WX_APPSECRET,$DEBUG;
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$data = array();
		$itime = time();
		$is_chafeng = false;

		do {
			if( !isset($params['num']) || !$params['num']
			|| !isset($params['uid']) || !$params['uid']
			|| !isset($params['pwd']) || !$params['pwd']
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			//图形验证码
			if(empty($params['type'] ))
			{
				$params['type'] = 1;
			}

			$data_uid = $params['uid'];
			if($data_uid)
			{
				if(1 == $params['type'])
				{
					$strkey = $this->check_num_key.$data_uid;
				}
				else
				{
					$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
				}
				$check_num = $this->cache_handler->get( $strkey, $strkey );
				if( $check_num != str_replace(" ","",$params['num']))
				{
					BaseFunction::logger($this->log, "【login_check】:\n" . var_export($check_num, true) . "\n" . __LINE__ . "\n");
					$response['sub_code'] = 1; $response['desc'] = __line__; break;
				}
			}

			//修改密码
			$obj_user_factory = new UserFactory($this->cache_handler, $data_uid);
			if(!$obj_user_factory->initialize())
			{
				$tmp_user = new User();
				$tmp_user->uid = $data_uid;
				$tmp_user->init_time = $itime;
				$tmp_user->login_time = $itime;
				$tmp_user->up_time = $itime;

				if($DEBUG)
				{
					$tmp_user->key = '123456';
				}
				else
				{
					$tmp_user->key = substr(md5($itime), 0, 6);
				}
				$tmp_params['aid'] = $data_uid;
				if($this->_judge_is_chafeng($tmp_params))
				{
					//如果账号被查封 ,则status=1;
					$tmp_user->status = 1;
					$is_chafeng = true;
				}
				else
				{
					$tmp_user->status = 0;
				}

				$tmp_user->pwd = BaseFunction::sub_encryptMD5(BaseFunction::encryptMD5(array($params['pwd'])));

				$rawsqls[] = $tmp_user->getInsertSql();

				$obj_user = $tmp_user;
			}
			else
			{
				$obj_user = $obj_user_factory->get();
				$obj_user->login_time = $itime;
				if($DEBUG)
				{
					$obj_user->key = '123456';
				}
				else
				{
					$obj_user->key = substr(md5($itime), 0, 6);
				}

				$obj_user->pwd = BaseFunction::sub_encryptMD5(BaseFunction::encryptMD5(array($params['pwd'])));

				$rawsqls[] = $obj_user->getUpdateSql();
			}

			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				BaseFunction::logger($this->log, "【rawsqls】:\n".var_export($rawsqls, true)."\n".__LINE__."\n");
				$response['code'] = CatConstant::ERROR_UPDATE; $response['desc'] = __line__; break;
			}

			$obj_user_factory->clear();
			if($is_chafeng)
			{
				$response['sub_code'] = 2; $response['desc'] = __line__; break;
			}

			$data[] = $check_num;
			$response['data'] = $data;

		}while(false);

		return $response;
	}

	public function login_check($params)
	{
		global $LOGIN_OUT_TIME;
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		//$rawsqls = array();
		$itime = time();
		$data = array();

		do {
			if( !isset($params['uid']) || !$params['uid']
			|| !isset($params['key']) || !$params['key']
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			$is_login = 0;

			$obj_user_factory = new UserFactory($this->cache_handler, $params['uid']);
			if(!$obj_user_factory->initialize())
			{
				$response['code'] = CatConstant::ERROR_INIT; $response['desc'] = __line__; break;
			}

			$obj_user = $obj_user_factory->get();

			if( isset($obj_user->key) && $obj_user->key == $params['key'] && ($itime - $obj_user->login_time) < $this->login_timeout)
			{
				//判断是否查封或删除
				if($obj_user->status == 1)
				{
					$response['sub_code'] = 2; $response['desc'] = __line__; break;
				}

				if(!empty($LOGIN_OUT_TIME))
				{
					$out_time = $LOGIN_OUT_TIME * 3600;
					if(($itime - $obj_user->login_time) > $out_time )
					{
						//超时
						$response['sub_code'] = 1; $response['desc'] = __line__; break;
					}
				}
				$is_login = 1;
			}
			else
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			$data['openid'] = '';
			$obj_wx_openid_multi_factory = new WxOpenidMultiFactory($this->cache_handler, null, $params['uid']);
			if($obj_wx_openid_multi_factory->initialize() && $obj_wx_openid_multi_factory->get())
			{
				$obj_wx_openid_multi = $obj_wx_openid_multi_factory->get();
				if($obj_wx_openid_multi && is_array($obj_wx_openid_multi))
				{
					foreach ($obj_wx_openid_multi as $obj_wx_openid_multi_item)
					{
						$data['openid'] = $obj_wx_openid_multi_item;
					}
				}
			}

			$data['is_login'] = $is_login;
			$data['user'] = $obj_user;
			$response['data'] = $data;

		}while(false);

		return $response;
	}

	public function logout($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();

		do {
			if( !isset($params['uid']) || !$params['uid']
			|| !isset($params['key']) || !$params['key']
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			$obj_user_factory = new UserFactory($this->cache_handler, $params['uid']);
			if(!$obj_user_factory->initialize())
			{
				$response['code'] = CatConstant::ERROR_INIT; $response['desc'] = __line__; break;
			}

			$obj_user = $obj_user_factory->get();
			// if( !isset($obj_user->key) || $obj_user->key != $params['key'])
			// {
			// 	$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			// }
			$obj_user->key = '';
			$obj_user->up_time = $itime;

			$rawsqls[] = $obj_user->getUpdateSql();
			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				BaseFunction::logger($this->log, "【rawsqls】:\n".var_export($rawsqls, true)."\n".__LINE__."\n");
				$response['code'] = CatConstant::ERROR_UPDATE; $response['desc'] = __line__; break;
			}
			$obj_user_factory->writeback();
			$response['data'] = $data;

		}while(false);

		return $response;
	}

    public function set_user($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();

		do {
			if( !isset($params['uid']) || !$params['uid']
			|| !isset($params['name']) || !$params['name']
			|| !isset($params['key']) || !$params['key']
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			$obj_user_factory = new UserFactory($this->cache_handler, $params['uid']);
			if(!$obj_user_factory->initialize())
			{
				$response['code'] = CatConstant::ERROR_INIT; $response['desc'] = __line__; break;
			}

			$obj_user = $obj_user_factory->get();
			if( !isset($obj_user->key) || !$obj_user->key == $params['key'] || ($itime - $obj_user->login_time) > $this->login_timeout)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			$obj_user->name = $params['name'];
			$obj_user->up_time = $itime;

			$rawsqls[] = $obj_user->getUpdateSql();
			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				BaseFunction::logger($this->log, "【rawsqls】:\n".var_export($rawsqls, true)."\n".__LINE__."\n");
				$response['code'] = CatConstant::ERROR_UPDATE; $response['desc'] = __line__; break;
			}
			$obj_user_factory->writeback();

			$data['user'] = $obj_user;
			$response['data'] = $data;

		}while(false);

		return $response;
	}

	public function get_user($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		//$rawsqls = array();
		//$itime = time();
		$data = array();

		do {
			if( !isset($params['uid']) || !$params['uid']
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			$obj_user_factory = new UserFactory($this->cache_handler, $params['uid']);
			if(!$obj_user_factory->initialize())
			{
				$response['code'] = CatConstant::ERROR_INIT; $response['desc'] = __line__; break;
			}

			$data['openid'] = null;
			$obj_wx_openid_multi_factory = new WxOpenidMultiFactory($this->cache_handler, null, $params['uid']);
			if($obj_wx_openid_multi_factory->initialize() && $obj_wx_openid_multi_factory->get())
			{
				$obj_wx_openid_multi = $obj_wx_openid_multi_factory->get();
				if($obj_wx_openid_multi && is_array($obj_wx_openid_multi))
				{
					foreach ($obj_wx_openid_multi as $obj_wx_openid_multi_item)
					{
						$data['openid'] = $obj_wx_openid_multi_item;
					}
				}
			}

			$obj_user = $obj_user_factory->get();

			$data['user'] = $obj_user;
			$response['data'] = $data;

		}while(false);

		return $response;
	}

	public function login_num_check($params)
	{
		global $WX_APPID, $WX_APPSECRET;
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$data = array();

		do {
			if( !isset($params['num']) || !$params['num']
			|| !isset($params['aid']) || !$params['aid']
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			if(empty($params['type'] ))
			{
				$params['type'] = 2;
			}

			$need_check_num = true;

			$data_uid = $params['aid'];
			if($need_check_num && $data_uid)
			{
				if(2 == $params['type'])
				{
					$strkey = $this->check_add_agent_num_key.$data_uid;
				}
				else
				{
					$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
				}
				$check_num = $this->cache_handler->get( $strkey, $strkey );
				if( $check_num != str_replace(" ","",$params['num']))
				{
					BaseFunction::logger($this->log, "【login_check11】:\n" . var_export($check_num, true) . "\n" . __LINE__ . "\n");
					BaseFunction::logger($this->log, "【login_check22】:\n" . var_export($params['num'], true) . "\n" . __LINE__ . "\n");
					BaseFunction::sms_cz_alidayu("SMS_36375183", json_encode(array('code'=>$check_num.$params['num'], 'product'=>'灵飞棋牌')), '8618911554496');
					$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
				}
			}

			$data[] = $check_num;
			$response['data'] = $data;

		}while(false);

		return $response;
	}

	public function __construct()
	{
		if(empty($this->cache_handler))
		{
			$tmp = CatConstant::CACHE_TYPE;
			$this->cache_handler = $tmp::get_instance();
		}
	}


	public function set_user_status($params)
	{
		$response = array('code' => CatConstant::OK,'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$data = array();

		do {
			if( !isset($params['type']) || !$params['type']
			|| !isset($params['aid']) || !$params['aid']
			)
			{
				$response['code'] = CatConstant::ERROR; $response['desc'] = __line__; break;
			}

			$obj_user_list_factory = new UserListFactory($this->cache_handler, null ,$params['aid']);
			if($obj_user_list_factory->initialize() && $obj_user_list_factory->get())
			{
				$obj_user_multi_factory = new UserMultiFactory($this->cache_handler, $obj_user_list_factory);
				if($obj_user_multi_factory->initialize() && $obj_user_multi_factory->get())
				{
					$obj_user_multi = $obj_user_multi_factory->get();
					$obj_user_multi_item = current($obj_user_multi);
					//判断是否查封或删除  type   1删除   2查封   3解封

					if($params['type'] == 1)
					{
						$rawsqls[] = $obj_user_multi_item->getDelSql();
					}
					elseif($params['type'] == 2)
					{
						$obj_user_multi_item->status = 1;  //查封
						$rawsqls[] = $obj_user_multi_item->getUpdateSql();
					}
					elseif($params['type'] == 3)
					{
						$obj_user_multi_item->status = 0;   //解封正常状态
						$rawsqls[] = $obj_user_multi_item->getUpdateSql();
					}
				}
				else
				{
					$response['sub_code'] = 1; $response['desc'] = __line__; break;
				}
			}
			else
			{
				$response['sub_code'] = 1; $response['desc'] = __line__; break;
			}

			if($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				BaseFunction::logger($this->log, "【rawsqls】:\n".var_export($rawsqls, true)."\n".__LINE__."\n");
				$response['code'] = CatConstant::ERROR_UPDATE; $response['desc'] = __line__; break;
			}

			if(isset($obj_user_multi_factory))
			{
				$obj_user_multi_factory->writeback();
			}

			$response['data'] = $data;

		}while(false);

		return $response;
	}

	//判断是否查封
	private function _judge_is_chafeng($params)
	{
		global $AGENT_URL;
		$rawsqls = array();
		$itime = time();
		$data = array();

		do{
			//判断是否为推广人员,模块调用
				$data_request = array(
				'mod' => 'Business'
				, 'act' => 'judge_is_chafeng'
				, 'platform' => 'gfplay'
				, 'aid' => $params['aid']
				);
				$randkey = BaseFunction::encryptMD5($data_request);
				$url = $AGENT_URL . "?randkey=" . $randkey . "&c_version=0.0.1";
				$result = json_decode(BaseFunction::https_request($url, array('parameter' => json_encode($data_request))));
				if (!$result || !isset($result->code) || $result->code != 0 || (isset($result->sub_code) && $result->sub_code != 0))
				{
					if($result->sub_code != 0)  //sub_code!= 0 已被查封
					{
						return true;
					}
				}

		}while(false);
		return false;
	}


	//更新+86
	/*public function updata($params)
	{
		$response = array('code' => CatConstant::OK, 'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();
		$tmp = array();
		$phone = 86;
		//构造分页参数

		do {
			//$mcobj = BaseFunction::getMC();
			//agent_buy
			$obj_user_list_factory = new UserListFactory($this->cache_handler);
			if($obj_user_list_factory->initialize() && $obj_user_list_factory->get())
			{
				$obj_user_multi_factory = new UserMultiFactory($this->cache_handler,$obj_user_list_factory);
				if($obj_user_multi_factory->initialize() && $obj_user_multi_factory->get())
				{
					$obj_user_multi = $obj_user_multi_factory->get();

					if(is_array($obj_user_multi))
					{
						foreach ($obj_user_multi as $key => $obj_user_multi_item)
						{
							$obj_user_multi_item->uid = $phone.$obj_user_multi_item->uid;


							$rawsqls[] = $obj_user_multi_item->getUpdateSql_Id();
							BaseFunction::logger($this->log, "【rawsqls】:\n" . var_export($rawsqls, true) . "\n" . __LINE__ . "\n");

						}
					}
				}
			}
			else
			{
				$obj_user_list_factory->clear();
				$response['sub_code'] = 1; $response['desc'] = __line__; break;
			}


			if ($rawsqls && !BaseFunction::execute_sql_backend($rawsqls))
			{
				BaseFunction::logger($this->log, "【rawsqls】:\n" . var_export($rawsqls, true) . "\n" . __LINE__ . "\n");
				$response['code'] = 1; $response['desc'] = __line__; break;
			}

			$obj_user_multi_factory->writeback();


			$response['data'] = $data;
		} while (false);

		return $response;
	}
*/


}