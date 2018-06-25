<?php
/**
 * @author xuqiang76@163.com
 * @final 20160929
 */

namespace bigcat;

if(!isset($_GET['test_key']) || $_GET['test_key'] != 'ncbdtocar' )
{
    exit('exit');
}

$data_receive = array(
    'mod'=>'Business'
	, 'act'=>'login'
	, 'platform'=>'tocar'
	, 'uid'=>'11112223333'
	, 'num'=>'1234'
);

/*$data_receive = array(
    'mod'=>'Business'
	, 'act'=>'send_num'
	, 'platform'=>'tocar'
	, 'uid'=>'11112223333'
);*/

//$data_receive = array_merge($_GET, $_POST, $_COOKIE, $_REQUEST );

$_REQUEST = array('randkey'=>$randkey, 'c_version'=>'0.0.1', 'parameter'=>json_encode($data_receive) );

require ("./index.php");

