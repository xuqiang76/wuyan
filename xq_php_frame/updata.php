<?php 
/****
兔卡科技
@author:tangchunxin
****/
exit();
    function encryptMD5($data)
	{
		$content = '';
		if(!$data || !is_array($data))
		{
			return $content;
		}
		ksort($data);
		foreach ($data as $key => $value)
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

	$data_receive = array(
	    'mod'=>'Business'
		, 'act'=>'updata'
		, 'platform'=>'tocar'
		, 'key'=>'NCBDpay'

	);
	$randkey = encryptMD5($data_receive);
	$_REQUEST = array('randkey'=>$randkey, 'c_version'=>'0.0.1', 'parameter'=>json_encode($data_receive) );

	require ("./index.php");


?>