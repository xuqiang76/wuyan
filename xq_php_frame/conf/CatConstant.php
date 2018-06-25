<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat\conf;

class CatConstant {

	const C_VERSION = '0.0.1';
	const CONF_VERSION = '0.0.1';
	const SECRET = 'Keep it simple stupid!';
	const CDKEY = 'God bless you!';
	const LOG_FILE = './log/user.log';
	const CACHE_TYPE = '\bigcat\inc\CatMemcache';
	const C_VERSION_CHECK = true;

	const OK = 0;
	const ERROR = 1;
	const ERROR_MC = 2;
	const ERROR_INIT = 3;
	const ERROR_UPDATE = 4;
	const ERROR_VERIFY = 5;
	const ERROR_ARGUMENT = 6;
	const ERROR_VERSION = 7;

	const MODELS = array('Business' => '\bigcat\controller\Business');
	const UNCHECK_C_CERSION_ACT = array('Business' => ['get_conf']);
	const UNCHECK_VERIFIED_ACT = array('Business' => ['get_conf', 'send_num', 'login', 'login_check', 'login_pwd', 'updata', 'chmod_login_pwd', 'logout', 'set_user', 'get_user', 'login_num_check']);

	const SUB_DESC = array(
		'Business_login_check' => array('sub_code_1' => '登录超时', 'sub_code_2' => '该账号已被查封'),

	);

}
