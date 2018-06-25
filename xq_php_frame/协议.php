<?php  
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

exit();

//华南测试地址(内网/公网)，如果是服务端调用可以用内网地址
http://10.25.139.58/user_php70/index.php
http://120.76.211.70//user_php70/index.php

////////////////////////////////////////////////

//协议规则
urlencode的格式用户信息（源格式json的）

//生成 randkey 函数
function encryptMD5($data)
{
	$content = '';
	if(!$data || !is_array($data))
	{
		return $content;
	}
	ksort($data);
	foreach ($data as $key => $value);
	{
		$content = $content.$key.$value;
	}
	if(!$content)
	{
		return $content;
	}
	
	return sub_encryptMD5($content);
}

function sub_encryptMD5($content)
{
	global $RPC_KEY;
	$content = $content.$RPC_KEY;
	$content = md5($content);
	if( strlen($content) > 10 )
	{
		$content = substr($content, 0, 10);
	}
	return $content;
}

//例子
$data = array('mod'=>'Business', 'act'=>'login', 'platform'=>'tocar', 'uid'=>'13671301110');
$randkey = encryptMD5($data);
$_REQUEST = array('randkey'=>$randkey, 'c_version'=>'0.0.1', 'parameter'=>json_encode($data) );


#send check number
request:
	randkey
	c_version
	parameter 
		mod: 'Business'
		act: 'send_num'
		platform:'tocar'
		uid	//帐号
		type	1	// 1 登录并图形验证   2 其他功能的验证码不包括图形验证
		authcode:	//（微信版可以空）图形校验，图形地址：用户模块URL路径/authcode.php
response:
	code //是否成功 0成功
	desc	//描述
	sub_code	//出错类型 0 成功 1 一分钟内不可重复操作 2 图形验证错误
	sub_desc	//sub_code 描述	
	data:

#register & login     
request:
	randkey
	c_version
	parameter 
		mod: 'Business'
		act: 'login'
		platform: 'tocar'
		uid	//帐号（如果code不为空，则此项可为空）
		num	//校验码（如果code不为空，则此项可为空）
		code	//微信授权码（可以空，空则代表非微信客户端）
		openid	//微信openid（可以空）
response:
	code //是否成功 0成功
	desc	//描述
	sub_code	//出错类型 0 成功 1 还没绑定手机号
	sub_desc	//sub_code 描述	
	data:
		user	//用户对象
		openid	//微信openid

# 密码登录   
request:
	randkey
	c_version
	parameter 
		mod: 'Business'
		act: 'login_pwd'
		platform: 'tocar'
		uid	//帐号
		pwd //密码
response:
	code //是否成功 0成功
	desc	//描述
	sub_code	//出错类型 0 成功 
	sub_desc	//sub_code 描述	
	data:
		user	//用户对象
//修改密码
request:
	randkey
	c_version
	parameter 
		mod: 'Business'
		act: 'chmod_login_pwd'
		platform: 'tocar'
		num //短信验证码
		uid	//帐号
		pwd //密码
response:
	code //是否成功 0成功
	desc	//描述
	sub_code	//出错类型 0 成功 1 还没绑定手机号
	sub_desc	//sub_code 描述	
	data:
		

	
#Login status check
request:
	randkey
	c_version
	parameter 
		mod: 'Business'
		act: 'login_check'
		platform: 'tocar'
		uid	//帐号
		key	//登陆用的
response:
	code //是否成功 0成功
	desc	//描述
	sub_code	//出错类型 0 成功
	sub_desc	//sub_code 描述	
	data:
		is_login
		user
		openid


#logout
request:
	randkey
	c_version
	parameter 
		mod: 'Business'
		act: 'logout'
		platform: 'tocar'
		uid	//帐号
		key	//登陆用的
response:
	code //是否成功 0成功
	desc	//描述
	sub_code	//出错类型 0 成功
	sub_desc	//sub_code 描述	
	data:


#Set user content (name ...)
request:
	randkey
	c_version
	parameter 
		mod: 'Business'
		act: 'set_user'
		platform: 'tocar'
		name: ''	//用户姓名
		uid	//帐号
		key	//登陆用的
response:
	code //是否成功 0成功
	desc	//描述
	sub_code	//出错类型 0 成功
	sub_desc	//sub_code 描述	
	data:
		user
		
		
#Get user info
request:
	randkey
	c_version
	parameter 
		mod: 'Business'
		act: 'get_user'
		platform: 'tocar'
		uid	//帐号
response:
	code //是否成功 0成功
	desc	//描述
	sub_code	//出错类型 0 成功
	sub_desc	//sub_code 描述	
	data:
		user
		openid

		
request:
	randkey
	c_version
	parameter 
		mod: 'Business'
		act: 'login_num_check'
		platform: 'tocar'
		aid	//帐号
		num //短信验证码
		type //验证类型 1登录验证 2其他验证
response:
	code //是否成功 0成功
	desc	//描述
	sub_code	//出错类型 0 成功
	sub_desc	//sub_code 描述	
	data:
	
